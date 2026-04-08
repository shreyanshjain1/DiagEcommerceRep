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

/** Basic guard */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect_back('Invalid request.');
}

/** ✅ CSRF (matches your config/csrf.php exactly) */
csrf_validate(url('pages/signup.php'));

/** Validate fields */
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

/** Required docs */
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
$maxBytes    = 10 * 1024 * 1024; // 10MB

/** Upload directory (inside /ecom/uploads/user_docs) */
$root = realpath(__DIR__ . '/..'); // /ecom
$uploadDirAbs = $root . '/uploads/user_docs';
if (!is_dir($uploadDirAbs)) {
  @mkdir($uploadDirAbs, 0775, true);
}
if (!is_dir($uploadDirAbs) || !is_writable($uploadDirAbs)) {
  redirect_back('Upload folder not writable: /uploads/user_docs. Please set permissions.');
}

try {
  /** Ensure email unique */
  $st = $pdo->prepare("SELECT id FROM users WHERE email=:e LIMIT 1");
  $st->execute([':e' => $email]);
  if ($st->fetch()) {
    redirect_back('Email already registered. Please login instead.');
  }

  /** Read users table columns dynamically */
  $cols = [];
  $q = $pdo->query("SHOW COLUMNS FROM users");
  while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $r['Field'];
  }

  /** Determine password column */
  $passCol = in_array('password_hash', $cols, true) ? 'password_hash' : (in_array('password', $cols, true) ? 'password' : '');
  if ($passCol === '') {
    redirect_back('Users table is missing password column (password or password_hash).');
  }

  $hash = password_hash($pass, PASSWORD_DEFAULT);

  $insertCols = [];
  $insertVals = [];
  $bind = [];

  if (in_array('name', $cols, true))   { $insertCols[] = 'name';   $insertVals[] = ':name';   $bind[':name'] = $name; }
  if (in_array('phone', $cols, true))  { $insertCols[] = 'phone';  $insertVals[] = ':phone';  $bind[':phone'] = $phone; }
  if (in_array('company', $cols, true)){ $insertCols[] = 'company';$insertVals[] = ':company';$bind[':company'] = $company; }
  if (in_array('email', $cols, true))  { $insertCols[] = 'email';  $insertVals[] = ':email';  $bind[':email'] = $email; }

  $insertCols[] = $passCol;
  $insertVals[] = ':pass';
  $bind[':pass'] = $hash;

  if (in_array('created_at', $cols, true)) {
    $insertCols[] = 'created_at';
    $insertVals[] = 'NOW()';
  }

  $pdo->beginTransaction();

  $sql = "INSERT INTO users (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $insertVals) . ")";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($bind);

  $userId = (int)$pdo->lastInsertId();
  if ($userId <= 0) {
    throw new RuntimeException('Could not create user.');
  }

  /** Save docs rows */
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

    $ins = $pdo->prepare("
      INSERT INTO user_documents (user_id, doc_type, file_path, original_name, mime_type, file_size)
      VALUES (:uid, :dt, :fp, :on, :mt, :sz)
    ");
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
  redirect_ok('Signup submitted successfully. Please wait for verification.');

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log("Signup submit error: " . $e->getMessage());
  redirect_back("Signup failed: " . $e->getMessage());
}
