<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/settings.php';

$SEO_TITLE = "Contact | Pharmastar Diagnostics (Division of Pharmastar Int'l Trading Corp)";
$SEO_DESC  = "Contact Pharmastar Diagnostics for quotations, product inquiries, and support in the Philippines. Hotline, WhatsApp, and email available.";
$SEO_CANONICAL = "https://pharmastar.org/ecom/pages/contact.php";

require_once __DIR__ . '/../includes/header.php';

$hotline = (string)setting('contact_hotline', '+639691229230');
$whatsapp = (string)setting('contact_whatsapp', '+639453462354');
$contactEmail = (string)setting('contact_email', $ADMIN_EMAIL);
?>

<section class="section">
  <div class="card" style="padding:18px">
    <h1 style="margin:0 0 10px">Contact</h1>
    <p class="muted" style="margin:0; line-height:1.6">
      For quotations (RFQ), product availability, and diagnostic support, contact <strong>Pharmastar Diagnostics</strong> —
      a division of <strong>Pharmastar Int’l Trading Corp</strong>.
    </p>
  </div>
</section>

<section class="section">
  <div class="grid" style="gap:14px">
    <div class="card" style="padding:18px">
      <h2 style="margin:0 0 8px">Reach us</h2>
      <div class="muted" style="display:grid; gap:10px">
        <div>Hotline: <a href="tel:<?php echo e($hotline); ?>"><?php echo e($hotline); ?></a></div>
        <div>WhatsApp: <a href="<?php echo e(wa_link($whatsapp)); ?>" target="_blank" rel="noopener"><?php echo e($whatsapp); ?></a></div>
        <div>Email: <a href="mailto:<?php echo e($contactEmail); ?>"><?php echo e($contactEmail); ?></a></div>
      </div>
    </div>

    <div class="card" style="padding:18px">
      <h2 style="margin:0 0 8px">Fastest way to get pricing</h2>
      <p class="muted" style="margin:0; line-height:1.6">
        Use the RFQ flow so we can respond with pricing, availability, lead time, and alternatives faster.
      </p>
      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap">
        <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse Products</a>
        <a class="btn" href="<?php echo url('pages/cart.php'); ?>">Open RFQ Cart</a>
        <a class="btn" href="<?php echo url('pages/inquiry.php'); ?>">Submit Inquiry</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
