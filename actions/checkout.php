<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

ensure_post(); csrf_validate();
$uid = current_user_id(); if(!$uid) die('Login required');

$fields = ['name','email','phone','company','po_number','vat_tin','address_line1','address_line2','city','province','postal_code','payment_method'];
$data = [];
foreach($fields as $f){ $data[$f] = trim($_POST[$f] ?? ''); }
if(!$data['name'] || !filter_var($data['email'],FILTER_VALIDATE_EMAIL) || !$data['phone'] || !$data['address_line1'] || !$data['city'] || !$data['province'] || !$data['postal_code']) die('Missing fields');

$stmt=$pdo->prepare("SELECT id FROM carts WHERE user_id=:u ORDER BY id DESC LIMIT 1");
$stmt->execute([':u'=>$uid]); $cart_id=$stmt->fetchColumn(); if(!$cart_id) die('Cart empty');

$items=$pdo->prepare("SELECT ci.*, p.stock FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=:cid");
$items->execute([':cid'=>$cart_id]); $rows=$items->fetchAll(); if(!$rows) die('Cart empty');

$subtotal=0; foreach($rows as $r){ if($r['stock']<$r['qty']) die('Insufficient stock'); $subtotal+=$r['qty']*$r['price_at_time']; }
$shipping_fee=150.00; $total=$subtotal+$shipping_fee; $on=order_number();

try{
  $pdo->beginTransaction();

  // persist user prefs
  $pdo->prepare("UPDATE users SET company=:c, vat_tin=:vt, default_payment_method=:pm WHERE id=:id")
      ->execute([':c'=>$data['company']?:null, ':vt'=>$data['vat_tin']?:null, ':pm'=>$data['payment_method'], ':id'=>$uid]);

  $pdo->prepare("INSERT INTO orders(user_id,order_number,status,po_number,vat_tin,payment_method,subtotal,shipping_fee,total,name,company,email,phone,address_line1,address_line2,city,province,postal_code,created_at)
    VALUES(:uid,:on,'Pending',:po,:vat,:pm,:sub,:ship,:tot,:n,:comp,:e,:ph,:a1,:a2,:c,:pr,:pc,NOW())")
    ->execute([':uid'=>$uid, ':on'=>$on, ':po'=>$data['po_number']?:null, ':vat'=>$data['vat_tin']?:null, ':pm'=>$data['payment_method'],
               ':sub'=>$subtotal, ':ship'=>$shipping_fee, ':tot'=>$total, ':n'=>$data['name'], ':comp'=>$data['company'],
               ':e'=>$data['email'], ':ph'=>$data['phone'], ':a1'=>$data['address_line1'], ':a2'=>$data['address_line2'],
               ':c'=>$data['city'], ':pr'=>$data['province'], ':pc'=>$data['postal_code']]);
  $oid=$pdo->lastInsertId();

  $insItem=$pdo->prepare("INSERT INTO order_items(order_id,product_id,qty,unit_price) VALUES(:o,:p,:q,:u)");
  $dec=$pdo->prepare("UPDATE products SET stock=stock-:q WHERE id=:p");
  foreach($rows as $r){ $insItem->execute([':o'=>$oid,':p'=>$r['product_id'],':q'=>$r['qty'],':u'=>$r['price_at_time']]); $dec->execute([':q'=>$r['qty'],':p'=>$r['product_id']]); }
  $pdo->prepare("DELETE FROM cart_items WHERE cart_id=:cid")->execute([':cid'=>$cart_id]);

  $pdo->commit();
  @mail($ADMIN_EMAIL, "New Order $on", "Total: ₱$total\nCompany: ".$data['company']."\nPO#: ".$data['po_number']."\nCustomer: ".$data['name']." <".$data['email'].">\n");
  header('Location: '.url('pages/order-confirmation.php?order='.urlencode($on))); exit;
}catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); die('Order failed: '.e($e->getMessage())); }
