<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';
require_once __DIR__.'/../includes/mailer.php';

$action = $_POST['action'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  csrf_validate();
}

function password_redirect(string $page, string $type, string $message): never {
  redirect(url('pages/' . $page . '.php?' . http_build_query([
    'status' => $type,
    'message' => $message,
  ])));
}

function password_reset_redirect(string $token, string $type, string $message): never {
  redirect(url('pages/reset.php?' . http_build_query([
    'token' => $token,
    'status' => $type,
    'message' => $message,
  ])));
}

function password_cleanup(PDO $pdo): void {
  $pdo->exec("DELETE FROM password_resets WHERE expires_at <= NOW() OR used_at IS NOT NULL");
}

function password_issue_token(PDO $pdo, int $userId): string {
  password_cleanup($pdo);
  $token = bin2hex(random_bytes(32));
  $tokenHash = hash('sha256', $token);

  $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id")
      ->execute([':user_id' => $userId]);

  $pdo->prepare(
    "INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
     VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())"
  )->execute([
    ':user_id' => $userId,
    ':token_hash' => $tokenHash,
  ]);

  return $token;
}

function password_find_valid_reset(PDO $pdo, string $token): ?array {
  if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    return null;
  }

  $tokenHash = hash('sha256', $token);
  $stmt = $pdo->prepare(
    "SELECT id, user_id
     FROM password_resets
     WHERE token_hash = :token_hash
       AND expires_at > NOW()
       AND used_at IS NULL
     LIMIT 1"
  );
  $stmt->execute([':token_hash' => $tokenHash]);
  $row = $stmt->fetch();

  return $row ?: null;
}

function password_send_reset_email(string $email, string $link): void {
  global $SITE_NAME;

  $subject = $SITE_NAME . ' Password Reset';
  $html = '<!doctype html><html><body style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;padding:16px">'
    . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">'
    . '<div style="padding:14px 16px;background:#0f172a;color:#ffffff;font-size:18px;font-weight:700">' . e($SITE_NAME) . '</div>'
    . '<div style="padding:16px;color:#0f172a">'
    . '<h2 style="margin:0 0 12px 0">Reset your password</h2>'
    . '<p style="margin:0 0 12px 0;line-height:1.6">We received a request to reset your password. This link will expire in 1 hour and can only be used once.</p>'
    . '<p style="margin:18px 0"><a href="' . e($link) . '" style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:999px;font-weight:700">Reset Password</a></p>'
    . '<p style="margin:0 0 12px 0;line-height:1.6">If the button does not work, copy and paste this link into your browser:</p>'
    . '<p style="margin:0 0 12px 0;word-break:break-all;color:#334155">' . e($link) . '</p>'
    . '<p style="margin:0;color:#64748b;font-size:12px">If you did not request this, you can ignore this email.</p>'
    . '</div></div></body></html>';

  send_mail_basic($email, $subject, $html);
}

if ($action === 'request') {
  $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
  if (!$email) {
    password_redirect('forgot', 'error', 'Please enter a valid email address.');
  }

  $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = :email LIMIT 1");
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch();

  if ($user) {
    $token = password_issue_token($pdo, (int)$user['id']);
    $link = url('pages/reset.php?token=' . urlencode($token));
    password_send_reset_email((string)$user['email'], $link);
  }

  password_redirect('forgot', 'success', 'If the email exists, a reset link has been sent.');
}

if ($action === 'reset') {
  $token = trim((string)($_POST['token'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $confirmPassword = (string)($_POST['confirm_password'] ?? '');

  if ($token === '') {
    password_reset_redirect('', 'error', 'Missing reset token.');
  }

  if (strlen($password) < 8) {
    password_reset_redirect($token, 'error', 'Password must be at least 8 characters long.');
  }

  if (!hash_equals($password, $confirmPassword)) {
    password_reset_redirect($token, 'error', 'Passwords do not match.');
  }

  $resetRow = password_find_valid_reset($pdo, $token);
  if (!$resetRow) {
    password_reset_redirect($token, 'error', 'This reset link is invalid or has expired.');
  }

  $passwordHash = password_hash($password, PASSWORD_DEFAULT);

  $pdo->beginTransaction();
  try {
    $updateUser = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
    $updateUser->execute([
      ':password_hash' => $passwordHash,
      ':user_id' => (int)$resetRow['user_id'],
    ]);

    $markUsed = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id AND used_at IS NULL");
    $markUsed->execute([':id' => (int)$resetRow['id']]);

    $cleanup = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id AND id <> :id");
    $cleanup->execute([
      ':user_id' => (int)$resetRow['user_id'],
      ':id' => (int)$resetRow['id'],
    ]);

    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    password_reset_redirect($token, 'error', 'Unable to update password right now. Please try again.');
  }

  redirect(url('pages/login.php?reset=success'));
}

http_response_code(400);
echo 'Bad request';
