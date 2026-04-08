<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/settings.php';

$me = null;
$initial = 'U';

if (!empty($_SESSION['user_id'])) {
  $stmt = $pdo->prepare("SELECT id, name, email, company, default_payment_method, vat_tin FROM users WHERE id=:id");
  $stmt->execute([':id' => $_SESSION['user_id']]);
  $me = $stmt->fetch();
  if ($me) {
    $initial = strtoupper(mb_first_char(trim($me['name'] ?: $me['email'])));
  }
}

/**
 * SEO variables 
 * Official homepage: https://pharmastar.org/ecom/index.php
 * <span class="name">Pharma<span>star</span> Diagnostics</span>
 */
if (!isset($SEO_TITLE)) {
  $SEO_TITLE = "Pharmastar Diagnostics | Medical Diagnostics & Laboratory Solutions Philippines";
}
if (!isset($SEO_DESC)) {
  $SEO_DESC  = "Pharmastar Diagnostics (Pharmastar Int'l Trading Corp) supplies medical diagnostics, laboratory analyzers, reagents, and diagnostic technology solutions in the Philippines.";
}
if (!isset($SEO_CANONICAL)) {
  // Default canonical: current URL
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $SEO_CANONICAL = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'pharmastar.org') . ($_SERVER['REQUEST_URI'] ?? '/ecom/index.php');
}

/**
 * Compare count
 * - Uses compare_all() if available (recommended)
 * - Falls back to compare_count() if your project already has it
 */
$cmpCount = 0;
if (function_exists('compare_all')) {
  $cmp = compare_all();
  $cmpCount = is_array($cmp) ? count($cmp) : 0;
} elseif (function_exists('compare_count')) {
  $cmpCount = (int)compare_count();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">

<?php
// SEO head include (this should output <title>, meta description, canonical, OG + schema)
include __DIR__ . "/seo_head.php";
?>

<script>document.documentElement.classList.add('js');</script>
<link rel="stylesheet" href="<?php echo asset('assets/css/styles.css'); ?>">
<script defer src="<?php echo asset('assets/js/main.js'); ?>"></script>
<!-- Analytics: add your preferred script here if needed -->
</head>
<body>
<div class="lead-ribbon">
  <div class="container">
    <?php
      $hotline = (string)setting('contact_hotline', '+639691229230');
      $whatsapp = (string)setting('contact_whatsapp', '+639453462354');
      $contactEmail = (string)setting('contact_email', $ADMIN_EMAIL);
    ?>
    <span>Hotline: <a href="tel:<?php echo e($hotline); ?>"><?php echo e($hotline); ?></a></span>
    <span>WhatsApp: <a href="<?php echo e(wa_link($whatsapp)); ?>" target="_blank" rel="noopener"><?php echo e($whatsapp); ?></a></span>
    <span>Email: <a href="mailto:<?php echo e($contactEmail); ?>"><?php echo e($contactEmail); ?></a></span>
  </div>
</div>

<header class="header">
  <div class="container navbar">
    <a class="brand" href="<?php echo url('index.php'); ?>">
      <img class="logo" src="<?php echo asset('assets/img/logo.png'); ?>" alt="Pharmastar Logo">
      
    </a>

    <div class="search" style="position:relative">
      <form action="<?php echo url('pages/products.php'); ?>" method="get">
        <input
          data-search
          data-suggest="<?php echo url('actions/search_suggest.php'); ?>"
          type="text"
          name="q"
          placeholder="Search devices, reagents, SKUs..."
          value="<?php echo e($_GET['q'] ?? ''); ?>"
        >
        <button type="submit">Search</button>
      </form>
    </div>

    <button class="nav-toggle" type="button" aria-label="Open menu" data-nav-toggle>
      <span></span><span></span><span></span>
    </button>

    <nav class="navlinks" id="siteNav">
      <a href="<?php echo url('index.php'); ?>">Home</a>
      <a href="<?php echo url('pages/products.php'); ?>">Products</a>
      <a href="<?php echo url('pages/inquiry.php'); ?>">Inquiry</a>

      <!-- ✅ Compare badge only shows when there are items -->
      <a href="<?php echo url('pages/compare.php'); ?>">
        Compare<?php if($cmpCount > 0): ?> <span class="badge" id="compareBadge"><?php echo (int)$cmpCount; ?></span><?php else: ?> <span class="badge" id="compareBadge" style="display:none">0</span><?php endif; ?>
      </a>

      <a href="<?php echo url('pages/cart.php'); ?>">RFQ<span class="badge"><?php echo e($_SESSION['cart_badge'] ?? 0); ?></span></a>
      <a href="<?php echo url('pages/about.php'); ?>">About</a>
      <a href="<?php echo url('pages/contact.php'); ?>">Contact</a>

      <?php if (is_admin()): ?>
        <a href="<?php echo url('admin/index.php'); ?>" style="font-weight:800">Admin</a>
      <?php endif; ?>

      <?php if ($me): ?>
        <div class="profile-menu">
          <button class="profile-btn" type="button"><span class="avatar-circle"><?php echo e($initial); ?></span></button>
          <div class="profile-dropdown" role="menu">
            <div class="profile-header">
              <div class="avatar-circle lg"><?php echo e($initial); ?></div>
              <div class="profile-meta">
                <div class="profile-name"><?php echo e($me['name'] ?: 'User'); ?></div>
                <div class="profile-email"><?php echo e($me['email']); ?></div>
              </div>
            </div>
            <a role="menuitem" href="<?php echo url('pages/profile.php'); ?>">View Profile</a>
            <a role="menuitem" href="<?php echo url('pages/quotes.php'); ?>">My RFQs</a>
            <?php if (is_admin()): ?>
              <a role="menuitem" href="<?php echo url('admin/index.php'); ?>">Admin</a>
            <?php endif; ?>
            <a role="menuitem" href="<?php echo url('actions/auth.php'); ?>?action=logout">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?php echo url('pages/login.php'); ?>">Login</a>
        <a href="<?php echo url('pages/signup.php'); ?>">Signup</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">

<script>
/**
 * Optional: live-update Compare badge after AJAX toggles on products.php
 * If products.php fires window.dispatchEvent(new CustomEvent('compare:count', {detail:{count:n}}));
 * then this will update the badge.
 */
(function(){
  const badge = document.getElementById('compareBadge');
  if(!badge) return;

  window.addEventListener('compare:count', function(ev){
    const n = (ev && ev.detail && typeof ev.detail.count !== 'undefined') ? parseInt(ev.detail.count,10) : 0;
    if (!Number.isFinite(n) || n <= 0) {
      badge.style.display = 'none';
      badge.textContent = '0';
    } else {
      badge.style.display = '';
      badge.textContent = String(n);
    }
  });
})();
</script>
