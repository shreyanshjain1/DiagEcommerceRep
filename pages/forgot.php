<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

$status = (string)($_GET['status'] ?? '');
$message = (string)($_GET['message'] ?? '');
?>
<h1>Forgot Password</h1>
<?php if ($message): ?>
  <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom:14px;padding:12px 14px;border-radius:10px;border:1px solid <?php echo $status === 'success' ? '#86efac' : '#fca5a5'; ?>;background:<?php echo $status === 'success' ? '#f0fdf4' : '#fef2f2'; ?>;color:#0f172a;">
    <?php echo e($message); ?>
  </div>
<?php endif; ?>
<form class="form" action="<?php echo url('actions/password.php'); ?>" method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="action" value="request">
  <label>Email</label>
  <input type="email" name="email" required>
  <button class="btn" type="submit" style="margin-top:10px">Send Reset Link</button>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
