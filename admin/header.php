<?php require_once __DIR__.'/_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin – <?php echo e($SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo asset('assets/css/styles.css'); ?>">
<link rel="stylesheet" href="<?php echo asset('assets/css/admin.css'); ?>">
<link rel="stylesheet" href="<?php echo asset('assets/css/admin-theme.css'); ?>">
</head>
<body class="admin admin-shell-body">
<?php
$self = basename($_SERVER['PHP_SELF'] ?? '');
$nav = [
  ['file' => 'index.php', 'label' => 'Dashboard', 'hint' => 'Overview & KPIs'],
  ['file' => 'rfqs.php', 'label' => 'RFQs', 'hint' => 'Quote pipeline'],
  ['file' => 'inquiries.php', 'label' => 'Inquiries', 'hint' => 'Customer requests'],
  ['file' => 'orders.php', 'label' => 'Legacy Orders', 'hint' => 'Archive workflow'],
  ['file' => 'products.php', 'label' => 'Products', 'hint' => 'Catalog control'],
  ['file' => 'products-new.php', 'label' => 'New Product', 'hint' => 'Create listing'],
  ['file' => 'users.php', 'label' => 'Users', 'hint' => 'Access & roles'],
  ['file' => 'settings.php', 'label' => 'Settings', 'hint' => 'Platform setup'],
];
?>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand-card">
      <a class="admin-brand" href="<?php echo url('admin/index.php'); ?>">
        <span class="admin-brand-mark">P</span>
        <span>
          <strong>Pharmastar Admin</strong>
          <small>RFQ operations workspace</small>
        </span>
      </a>
      <div class="admin-brand-meta">
        <span class="admin-chip success">Recruiter-ready UI</span>
        <span class="admin-chip muted">PHP + MySQL</span>
      </div>
    </div>

    <nav class="admin-nav">
      <div class="admin-nav-label">Workspace</div>
      <?php foreach ($nav as $item): ?>
        <a class="admin-nav-link<?php echo $self === $item['file'] ? ' active' : ''; ?>" href="<?php echo url('admin/' . $item['file']); ?>">
          <span class="admin-nav-copy">
            <strong><?php echo e($item['label']); ?></strong>
            <small><?php echo e($item['hint']); ?></small>
          </span>
          <span class="admin-nav-arrow">›</span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-card">
      <div class="admin-sidebar-card-title">Operations focus</div>
      <ul class="admin-mini-list">
        <li>Prioritise submitted RFQs waiting for pricing</li>
        <li>Keep brochures and specs updated</li>
        <li>Review low-stock and stale activity daily</li>
      </ul>
    </div>
  </aside>

  <div class="admin-content">
    <header class="admin-topbar">
      <div>
        <div class="admin-topbar-kicker">Admin Console</div>
        <h1 class="admin-topbar-title"><?php echo e($SITE_NAME); ?></h1>
      </div>
      <div class="admin-topbar-actions">
        <a class="btn secondary" href="<?php echo url('index.php'); ?>" target="_blank" rel="noopener">View Site</a>
        <a class="btn ghost" href="<?php echo url('actions/auth.php'); ?>?action=logout">Logout</a>
      </div>
    </header>

    <main class="admin-main-panel">
