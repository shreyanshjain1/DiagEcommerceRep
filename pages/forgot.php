<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';
?>
<h1>Forgot Password</h1>
<form class="form" action="<?php echo url('actions/password.php'); ?>" method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="action" value="request">
  <label>Email</label>
  <input type="email" name="email" required>
  <button class="btn" type="submit" style="margin-top:10px">Send Reset Link</button>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
