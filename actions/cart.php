<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

ensure_post();
csrf_validate();

$action = (string)($_POST['action'] ?? '');
$uid = current_user_id();
if(!$uid){
  $ret = (string)($_POST['_return'] ?? $_SERVER['HTTP_REFERER'] ?? url('pages/products.php'));
  header('Location: '.url('pages/login.php?return='.rawurlencode($ret)));
  exit;
}

// Ensure a cart exists
$stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id=:u ORDER BY id DESC LIMIT 1");
$stmt->execute([':u'=>$uid]);
$cart_id = (int)$stmt->fetchColumn();
if(!$cart_id){
  $pdo->prepare("INSERT INTO carts(user_id,created_at,updated_at) VALUES(:u,NOW(),NOW())")->execute([':u'=>$uid]);
  $cart_id = (int)$pdo->lastInsertId();
}

function refresh_badge(PDO $pdo, int $cart_id): void {
  $b = $pdo->prepare("SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id=:c");
  $b->execute([':c'=>$cart_id]);
  $_SESSION['cart_badge'] = (int)$b->fetchColumn();
}

if($action==='add'){
  $pid = (int)($_POST['product_id'] ?? 0);
  $qty = max(1, (int)($_POST['qty'] ?? 1));

  $p = $pdo->prepare("SELECT id FROM products WHERE id=:id AND is_active=1");
  $p->execute([':id'=>$pid]);
  if(!$p->fetchColumn()){ die('Invalid product'); }

  $ex = $pdo->prepare("SELECT id,qty FROM cart_items WHERE cart_id=:c AND product_id=:p");
  $ex->execute([':c'=>$cart_id,':p'=>$pid]);
  $it = $ex->fetch();

  if($it){
    $newq = (int)$it['qty'] + $qty;
    $pdo->prepare("UPDATE cart_items SET qty=:q WHERE id=:id")->execute([':q'=>$newq,':id'=>$it['id']]);
  } else {
    // RFQ-only: price_at_time is 0 (pricing will be provided by admin later)
    $pdo->prepare("INSERT INTO cart_items(cart_id,product_id,qty,price_at_time) VALUES(:c,:p,:q,0)")
      ->execute([':c'=>$cart_id,':p'=>$pid,':q'=>$qty]);
  }

  refresh_badge($pdo, $cart_id);
  $ret = (string)($_POST['_return'] ?? url('pages/cart.php'));
  header('Location: '.$ret);
  exit;
}

if($action==='update'){
  $ci = (int)($_POST['cart_item_id'] ?? 0);
  $qty = max(1, (int)($_POST['qty'] ?? 1));

  $pdo->prepare("UPDATE cart_items SET qty=:q WHERE id=:id")->execute([':q'=>$qty,':id'=>$ci]);
  refresh_badge($pdo, $cart_id);
  header('Location: '.url('pages/cart.php'));
  exit;
}

if($action==='remove'){
  $ci = (int)($_POST['cart_item_id'] ?? 0);
  $pdo->prepare("DELETE FROM cart_items WHERE id=:id")->execute([':id'=>$ci]);
  refresh_badge($pdo, $cart_id);
  header('Location: '.url('pages/cart.php'));
  exit;
}

http_response_code(400);
echo 'Bad request';
