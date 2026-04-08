<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
?>

<div class="page-head">
  <h1>Orders Disabled</h1>
  <p class="muted">This website currently works on an RFQ (Request for Quotation) basis. Online checkout / ordering is disabled.</p>
</div>

<div class="card p">
  <p style="margin-top:0">Use the RFQ flow to request pricing, submit requirements, and track quotation progress.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse products</a>
    <a class="btn secondary" href="<?php echo url('pages/cart.php'); ?>">Open RFQ</a>
    <a class="btn ghost" href="<?php echo url('pages/quotes.php'); ?>">My RFQs</a>
  </div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
