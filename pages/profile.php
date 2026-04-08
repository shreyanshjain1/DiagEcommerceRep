<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';

$uid = current_user_id();
if(!$uid){ echo '<div class="alert error">Please login to view your profile.</div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

$stmt = $pdo->prepare("SELECT name,email,phone,company,created_at FROM users WHERE id=:id");
$stmt->execute([':id'=>$uid]);
$user = $stmt->fetch();
?>
<h1>Your Profile</h1>
<div class="form">
  <div class="row">
    <div>
      <label>Company</label>
      <input type="text" value="<?php echo e($user['company']); ?>" disabled>
    </div>
    <div>
      <label>Name</label>
      <input type="text" value="<?php echo e($user['name']); ?>" disabled>
    </div>
  </div>
  <div class="row">
    <div>
      <label>Email</label>
      <input type="text" value="<?php echo e($user['email']); ?>" disabled>
    </div>
    <div>
      <label>Phone</label>
      <input type="text" value="<?php echo e($user['phone']); ?>" disabled>
    </div>
  </div>
  <div class="row">
    <div>
      <label>Member Since</label>
      <input type="text" value="<?php echo e($user['created_at']); ?>" disabled>
    </div>
  </div>
  <p style="margin-top:12px">
    <a class="btn" href="<?php echo url('pages/quotes.php'); ?>">View My RFQs</a>
    <a class="btn" href="<?php echo url('actions/auth.php'); ?>?action=logout">Logout</a>
  </p>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
