<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

$token = (string)($_GET['token'] ?? '');
$status = (string)($_GET['status'] ?? '');
$message = (string)($_GET['message'] ?? '');
?>
<h1>Reset Password</h1>
<?php if ($message): ?>
  <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom:14px;padding:12px 14px;border-radius:10px;border:1px solid <?php echo $status === 'success' ? '#86efac' : '#fca5a5'; ?>;background:<?php echo $status === 'success' ? '#f0fdf4' : '#fef2f2'; ?>;color:#0f172a;">
    <?php echo e($message); ?>
  </div>
<?php endif; ?>
<?php if (!$token): ?>
  <div class="alert alert-error" style="margin-bottom:14px;padding:12px 14px;border-radius:10px;border:1px solid #fca5a5;background:#fef2f2;color:#0f172a;">
    Missing reset token.
  </div>
<?php else: ?>
  <form class="form" action="<?php echo url('actions/password.php'); ?>" method="post">
    <?php csrf_field(); ?>
    <input type="hidden" name="action" value="reset">
    <input type="hidden" name="token" value="<?php echo e($token); ?>">
    <label>New Password</label>
    <input type="password" name="password" minlength="8" required>
    <label style="margin-top:10px">Confirm New Password</label>
    <input type="password" name="confirm_password" minlength="8" required>
    <button class="btn" type="submit" style="margin-top:10px">Update Password</button>
  </form>
<?php endif; ?>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
