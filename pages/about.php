<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/settings.php';

$SEO_TITLE = "About | Pharmastar Diagnostics — Division of Pharmastar Int'l Trading Corp";
$SEO_DESC  = "Pharmastar Diagnostics is a division of Pharmastar Int'l Trading Corp. This platform helps hospitals, clinics, and laboratories in the Philippines browse diagnostics and request quotations online.";
$SEO_CANONICAL = "https://pharmastar.org/ecom/pages/about.php";

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="card" style="padding:18px">
    <h1 style="margin:0 0 10px">Pharmastar Diagnostics</h1>
    <p class="muted" style="margin:0">
      <strong>Pharmastar Diagnostics</strong> is a division of <strong>Pharmastar Int’l Trading Corp</strong>.
      This platform is built to help hospitals, clinics, laboratories, and partners in the Philippines browse diagnostic solutions and submit
      <strong>RFQ / quotation requests</strong> online for faster processing.
    </p>
  </div>
</section>

<section class="section">
  <div class="grid" style="gap:14px">
    <div class="card" style="padding:18px">
      <h2 style="margin:0 0 8px">What we offer</h2>
      <ul class="muted" style="margin:0; padding-left:18px; line-height:1.6">
        <li>Medical diagnostic machines and laboratory analyzers</li>
        <li>Reagents, consumables, and diagnostic accessories</li>
        <li>Product comparisons, wishlists, and RFQ/quotation requests</li>
        <li>Support for hospitals, clinics, and laboratories nationwide</li>
      </ul>
    </div>

    <div class="card" style="padding:18px">
      <h2 style="margin:0 0 8px">Why this platform</h2>
      <p class="muted" style="margin:0; line-height:1.6">
        Instead of messaging back and forth, you can quickly browse products, shortlist items, and send your requirements through our RFQ flow.
        Our team can respond faster with pricing, availability, lead time, and after-sales support.
      </p>
    </div>
  </div>
</section>

<section class="section">
  <div class="card" style="padding:18px">
    <h2 style="margin:0 0 8px">Quick links</h2>
    <div class="muted" style="display:flex; gap:12px; flex-wrap:wrap">
      <a href="<?php echo url('pages/products.php'); ?>">Browse Products</a>
      <a href="<?php echo url('pages/inquiry.php'); ?>">Submit Inquiry</a>
      <a href="<?php echo url('pages/cart.php'); ?>">RFQ Cart</a>
      <a href="<?php echo url('pages/contact.php'); ?>">Contact</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
