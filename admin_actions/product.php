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

$beforeStmt = $pdo->prepare("SELECT id, name, sku, price, stock, is_active, updated_at FROM products WHERE id=:id");
$beforeStmt->execute([':id'=>$pid]);
$before = $beforeStmt->fetch(PDO::FETCH_ASSOC);
if (!$before) { die('Invalid'); }

$pdo->prepare("UPDATE products SET price=:p, stock=:s, is_active=:a WHERE id=:id")
    ->execute([':p'=>$price, ':s'=>$stock, ':a'=>$is_active, ':id'=>$pid]);

$afterStmt = $pdo->prepare("SELECT id, name, sku, price, stock, is_active, updated_at FROM products WHERE id=:id");
$afterStmt->execute([':id'=>$pid]);
$after = $afterStmt->fetch(PDO::FETCH_ASSOC);

audit_log($pdo, 'product', $pid, 'product_quick_updated', $before, $after);

header('Location: '.url('admin/products.php'));
