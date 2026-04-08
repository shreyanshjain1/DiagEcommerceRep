<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

$token = $_GET['token'] ?? '';
?>
<h1>Reset Password</h1>
<form class="form" action="<?php echo url('actions/password.php'); ?>" method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="action" value="reset">
  <input type="hidden" name="token" value="<?php echo e($token); ?>">
  <label>New Password</label>
  <input type="password" name="password" minlength="8" required>
  <button class="btn" type="submit" style="margin-top:10px">Update Password</button>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
