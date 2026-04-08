<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

$err = $_SESSION['flash_error'] ?? '';
$ok  = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>

<h1>Signup</h1>

<?php if($err): ?>
  <div class="card p" style="border:1px solid rgba(220,53,69,.35); background:rgba(220,53,69,.06); margin-bottom:12px">
    <strong style="color:#dc3545">Error:</strong> <?php echo e($err); ?>
  </div>
<?php endif; ?>

<?php if($ok): ?>
  <div class="card p" style="border:1px solid rgba(14,164,79,.35); background:rgba(14,164,79,.06); margin-bottom:12px">
    <strong style="color:#0ea44f">Success:</strong> <?php echo e($ok); ?>
  </div>
<?php endif; ?>

<div class="card p" style="margin-bottom:14px">
  <div class="muted" style="line-height:1.6">
    Please submit your business verification documents. Accepted formats: PDF, JPG, PNG (max 10MB each).
  </div>
</div>

<form class="form" action="<?php echo url('actions/signup_submit.php'); ?>" method="post" enctype="multipart/form-data">
  <?php csrf_field(); ?>
  <input type="hidden" name="action" value="signup">

  <div class="row">
    <div>
      <label>Name</label>
      <input type="text" name="name" required>
    </div>
    <div>
      <label>Phone</label>
      <input type="text" name="phone" required>
    </div>
  </div>

  <label>Company (Hospital / Lab)</label>
  <input type="text" name="company" placeholder="Company / Facility Name" required>

  <label>Email</label>
  <input type="email" name="email" required>

  <label>Password</label>
  <input type="password" name="password" required minlength="8">

  <div class="row">
    <div>
      <label>Company Profile (PDF/JPG/PNG)</label>
      <input type="file" name="doc_company_profile" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
    <div>
      <label>Mayor's Permit (PDF/JPG/PNG)</label>
      <input type="file" name="doc_mayors_permit" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
  </div>

  <div class="row">
    <div>
      <label>SEC Registration (PDF/JPG/PNG)</label>
      <input type="file" name="doc_sec" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
    <div>
      <label>BIR Registration / Certificate (PDF/JPG/PNG)</label>
      <input type="file" name="doc_bir" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
  </div>

  <label style="display:flex;gap:10px;align-items:flex-start">
    <input type="checkbox" name="terms" required style="margin-top:4px">
    <span>
      I agree to the <a href="<?php echo url('pages/terms.php'); ?>" target="_blank" rel="noopener">Terms & Conditions</a>,
      and I confirm that all details and documents submitted are true and authentic.
    </span>
  </label>

  <button class="btn" type="submit" style="margin-top:10px">Create Account</button>
</form>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
