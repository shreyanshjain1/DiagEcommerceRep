<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function redirect_back(string $msg): void {
  $_SESSION['flash_error'] = $msg;
  header('Location: ' . url('pages/signup.php'));
  exit;
}

function redirect_ok(string $msg): void {
  $_SESSION['flash_success'] = $msg;
  header('Location: ' . url('pages/login.php'));
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  redirect_back('Invalid request.');
}

csrf_validate(url('pages/signup.php'));

$name    = trim((string)($_POST['name'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$company = trim((string)($_POST['company'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$pass    = (string)($_POST['password'] ?? '');
$terms   = isset($_POST['terms']);

if ($name === '' || $company === '' || $email === '' || $pass === '' || $phone === '') {
  redirect_back('Please complete all required fields.');
}
if (!$terms) {
  redirect_back('Please accept the Terms & Conditions.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  redirect_back('Please enter a valid email address.');
}
if (strlen($pass) < 8) {
  redirect_back('Password must be at least 8 characters.');
}

$requiredDocs = [
  'doc_company_profile' => 'company_profile',
  'doc_mayors_permit'   => 'mayors_permit',
  'doc_sec'             => 'sec',
  'doc_bir'             => 'bir',
];

foreach ($requiredDocs as $input => $type) {
  if (!isset($_FILES[$input]) || (int)($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    redirect_back('Please upload all required documents.');
  }
}

$allowedExt  = ['pdf','jpg','jpeg','png'];
$allowedMime = ['application/pdf','image/jpeg','image/png'];
$maxBytes    = 10 * 1024 * 1024;

$root = realpath(__DIR__ . '/..');
$uploadDirAbs = $root . '/uploads/user_docs';
if (!is_dir($uploadDirAbs)) {
  @mkdir($uploadDirAbs, 0775, true);
}
if (!is_dir($uploadDirAbs) || !is_writable($uploadDirAbs)) {
  redirect_back('Upload folder not writable: /uploads/user_docs. Please set permissions.');
}

try {
  $st = $pdo->prepare('SELECT id FROM users WHERE email=:e LIMIT 1');
  $st->execute([':e' => $email]);
  if ($st->fetch()) {
    redirect_back('Email already registered. Please login instead.');
  }

  $cols = [];
  $q = $pdo->query('SHOW COLUMNS FROM users');
  while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $r['Field'];
  }

  $passCol = in_array('password_hash', $cols, true) ? 'password_hash' : (in_array('password', $cols, true) ? 'password' : '');
  if ($passCol === '') {
    redirect_back('Users table is missing password column (password or password_hash).');
  }

  $hash = password_hash($pass, PASSWORD_DEFAULT);

  $pdo->beginTransaction();

  $companyAccountId = null;
  if (in_array('company_account_id', $cols, true)) {
    $acctSql = "INSERT INTO company_accounts
      (company_name, account_code, primary_email, primary_phone, account_status, created_at, updated_at)
      VALUES (:company_name, :account_code, :primary_email, :primary_phone, 'pending_verification', NOW(), NOW())";
    $acct = $pdo->prepare($acctSql);
    $acct->execute([
      ':company_name' => $company,
      ':account_code' => company_account_code($company),
      ':primary_email' => $email,
      ':primary_phone' => $phone,
    ]);
    $companyAccountId = (int)$pdo->lastInsertId();
  }

  $insertCols = [];
  $insertVals = [];
  $bind = [];

  if (in_array('name', $cols, true)) { $insertCols[] = 'name'; $insertVals[] = ':name'; $bind[':name'] = $name; }
  if (in_array('phone', $cols, true)) { $insertCols[] = 'phone'; $insertVals[] = ':phone'; $bind[':phone'] = $phone; }
  if (in_array('company', $cols, true)) { $insertCols[] = 'company'; $insertVals[] = ':company'; $bind[':company'] = $company; }
  if (in_array('email', $cols, true)) { $insertCols[] = 'email'; $insertVals[] = ':email'; $bind[':email'] = $email; }
  if (in_array('company_account_id', $cols, true)) { $insertCols[] = 'company_account_id'; $insertVals[] = ':company_account_id'; $bind[':company_account_id'] = $companyAccountId; }
  if (in_array('company_contact_role', $cols, true)) { $insertCols[] = 'company_contact_role'; $insertVals[] = ':company_contact_role'; $bind[':company_contact_role'] = 'primary'; }

  $insertCols[] = $passCol;
  $insertVals[] = ':pass';
  $bind[':pass'] = $hash;

  if (in_array('created_at', $cols, true)) {
    $insertCols[] = 'created_at';
    $insertVals[] = 'NOW()';
  }

  $sql = 'INSERT INTO users (' . implode(',', $insertCols) . ') VALUES (' . implode(',', $insertVals) . ')';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($bind);

  $userId = (int)$pdo->lastInsertId();
  if ($userId <= 0) {
    throw new RuntimeException('Could not create user.');
  }

  if ($companyAccountId > 0) {
    $pdo->prepare('UPDATE company_accounts SET created_by = :uid WHERE id = :id')->execute([
      ':uid' => $userId,
      ':id' => $companyAccountId,
    ]);

    $pdo->prepare("INSERT INTO company_account_contacts
      (company_account_id, user_id, contact_role, is_primary, invite_status, created_at, updated_at)
      VALUES (:company_account_id, :user_id, 'primary', 1, 'active', NOW(), NOW())")->execute([
      ':company_account_id' => $companyAccountId,
      ':user_id' => $userId,
    ]);
  }

  foreach ($requiredDocs as $input => $docType) {
    $f = $_FILES[$input];
    $orig = (string)$f['name'];
    $tmp  = (string)$f['tmp_name'];
    $size = (int)$f['size'];

    if ($size <= 0 || $size > $maxBytes) {
      throw new RuntimeException('One document exceeds 10MB limit.');
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
      throw new RuntimeException('Invalid file type. Only PDF/JPG/PNG allowed.');
    }

    $mime = '';
    if (function_exists('finfo_open')) {
      $fi = finfo_open(FILEINFO_MIME_TYPE);
      if ($fi) {
        $mime = (string)finfo_file($fi, $tmp);
        finfo_close($fi);
      }
    }
    if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
      throw new RuntimeException('Invalid document format uploaded.');
    }

    $newName = $userId . '_' . $docType . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destAbs = $uploadDirAbs . '/' . $newName;

    if (!move_uploaded_file($tmp, $destAbs)) {
      throw new RuntimeException('Failed to upload documents.');
    }

    $relPath = 'uploads/user_docs/' . $newName;

    $ins = $pdo->prepare("INSERT INTO user_documents (user_id, doc_type, file_path, original_name, mime_type, file_size)
      VALUES (:uid, :dt, :fp, :on, :mt, :sz)");
    $ins->execute([
      ':uid' => $userId,
      ':dt'  => $docType,
      ':fp'  => $relPath,
      ':on'  => $orig,
      ':mt'  => ($mime ?: null),
      ':sz'  => $size,
    ]);
  }

  $pdo->commit();
  redirect_ok('Signup submitted successfully. Company account created and pending verification.');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  error_log('Signup submit error: ' . $e->getMessage());
  redirect_back('Signup failed: ' . $e->getMessage());
}
