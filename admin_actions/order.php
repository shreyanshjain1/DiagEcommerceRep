<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

require_admin();
ensure_post(); csrf_validate();

$oid = (int)($_POST['order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$allowed = ['Pending','Processing','Shipped','Completed','Cancelled'];
if(!$oid || !in_array($status,$allowed,true)){ die('Invalid'); }

$pdo->prepare("UPDATE orders SET status=:s WHERE id=:id")->execute([':s'=>$status, ':id'=>$oid]);

header('Location: '.url('admin/orders.php'));
