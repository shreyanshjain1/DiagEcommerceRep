<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';
?>
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
