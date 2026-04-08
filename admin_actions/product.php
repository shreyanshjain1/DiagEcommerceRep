<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

require_admin();
ensure_post(); csrf_validate();

$pid = (int)($_POST['product_id'] ?? 0);
$price = is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : null;
$stock = is_numeric($_POST['stock'] ?? '') ? (int)$_POST['stock'] : null;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

if(!$pid || $price===null || $stock===null){ die('Invalid'); }

$pdo->prepare("UPDATE products SET price=:p, stock=:s, is_active=:a WHERE id=:id")
    ->execute([':p'=>$price, ':s'=>$stock, ':a'=>$is_active, ':id'=>$pid]);

header('Location: '.url('admin/products.php'));
