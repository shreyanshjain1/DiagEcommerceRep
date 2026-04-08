<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

$prefSubject = trim((string)($_GET['subject'] ?? ''));
$prefProductId = (int)($_GET['product_id'] ?? 0);

$sent = isset($_GET['sent']);
$err  = $_GET['err'] ?? '';
?>
<h1>Inquiry</h1>

<?php if($sent): ?>
  <div class="alert success">Thanks! Your inquiry was sent to our team.</div>
<?php elseif($err): ?>
  <div class="alert error"><?php echo e($err); ?></div>
<?php endif; ?>

<form class="form" action="<?php echo url('actions/inquiry.php'); ?>" method="post" novalidate>
  <?php csrf_field(); ?>
  <input type="hidden" name="action" value="send">
  <?php if($prefProductId>0): ?>
    <input type="hidden" name="product_id" value="<?php echo e($prefProductId); ?>">
  <?php endif; ?>
  <!-- Honeypot (spam trap) -->
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

  <div class="row">
    <div>
      <label>Company (Hospital / Lab)</label>
      <input type="text" name="company" placeholder="Company / Facility Name">
    </div>
    <div>
      <label>Full Name*</label>
      <input type="text" name="name" required>
    </div>
  </div>

  <div class="row">
    <div>
      <label>Email*</label>
      <input type="email" name="email" required>
    </div>
    <div>
      <label>Phone</label>
      <input type="text" name="phone">
    </div>
  </div>

  <label>Subject*</label>
  <input type="text" name="subject" value="<?php echo e($prefSubject); ?>" placeholder="e.g. Quotation request for Erba XL-200" required>

  <label>Message*</label>
  <textarea name="message" rows="6" placeholder="Tell us what you need… models, quantities, delivery location, etc." required></textarea>

  <button class="btn" type="submit" style="margin-top:10px">Send Inquiry</button>
</form>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
