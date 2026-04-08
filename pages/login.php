<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';
?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-error" style="margin-bottom:12px"><?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success" style="margin-bottom:12px"><?php echo e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<h1>Login</h1>
<form class="form" action="<?php echo url('actions/auth.php'); ?>" method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="action" value="login">
  <label>Email</label>
  <input type="email" name="email" required>
  <label>Password</label>
  <input type="password" name="password" required minlength="8">
  <button class="btn" type="submit" style="margin-top:10px">Login</button>
</form>
<p style="margin-top:8px"><a href="<?php echo url('pages/forgot.php'); ?>">Forgot your password?</a></p>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
