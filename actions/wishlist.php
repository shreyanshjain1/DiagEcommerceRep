<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

ensure_post();
csrf_validate();

$action = (string)($_POST['action'] ?? '');
$pid = (int)($_POST['product_id'] ?? 0);
$return = (string)($_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? url('pages/products.php')));

if ($pid <= 0) {
  redirect($return);
}

if ($action === 'add') {
  wishlist_add($pid);
} elseif ($action === 'remove') {
  wishlist_remove($pid);
} elseif ($action === 'toggle') {
  if (wishlist_has($pid)) wishlist_remove($pid); else wishlist_add($pid);
}

redirect($return);
