<?php require_once __DIR__.'/_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin – <?php echo e($SITE_NAME); ?></title>

<!-- Base site CSS -->
<link rel="stylesheet" href="<?php echo asset('assets/css/styles.css'); ?>">

<!-- Admin layout CSS (THIS is what makes the sidebar work) -->
<link rel="stylesheet" href="<?php echo asset('assets/css/admin.css'); ?>">

<!-- Your theme overrides (keep AFTER admin.css) -->
<link rel="stylesheet" href="<?php echo asset('assets/css/admin-theme.css'); ?>">
</head>

<body class="admin" style="background:var(--admin-bg)">
<header class="header">
  <div class="container navbar">
    <a class="brand" href="<?php echo url('admin/index.php'); ?>">
      <span class="name">Admin – <?php echo e($SITE_NAME); ?></span>
    </a>
    <nav class="navlinks">
      <a href="<?php echo url('index.php'); ?>">View Site</a>
      <a href="<?php echo url('actions/auth.php'); ?>?action=logout">Logout</a>
    </nav>
  </div>
</header>

<main class="container admin-layout">
  <aside class="admin-aside">
    <strong>Navigation</strong>
    <a href="<?php echo url('admin/index.php'); ?>">Dashboard</a>
    <a href="<?php echo url('admin/inquiries.php'); ?>">Inquiries</a>
    <a href="<?php echo url('admin/orders.php'); ?>">Orders</a>
    <a href="<?php echo url('admin/rfqs.php'); ?>">RFQs</a>
    <a href="<?php echo url('admin/products.php'); ?>">Products</a>
    <a href="<?php echo url('admin/products-new.php'); ?>">New Product</a>
    <a href="<?php echo url('admin/users.php'); ?>">Users</a>
    <a href="<?php echo url('admin/company-accounts.php'); ?>">Company Accounts</a>
    <a href="<?php echo url('admin/company-addresses.php'); ?>">Company Addresses</a>
    <a href="<?php echo url('admin/settings.php'); ?>">Settings</a>
  </aside>

  <section class="admin-main">
