<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';
require_once __DIR__.'/../includes/mailer.php';
require_once __DIR__.'/../includes/settings.php';

$uid = current_user_id(); if(!$uid) die('Login required');
$act = $_GET['action'] ?? $_POST['action'] ?? '';

// Create a quote record from the user's cart.
// RFQ flow:
//  - draft: user saves request
//  - submitted: user sends RFQ to admin for quotation
if($act==='submit'){
  ensure_post();
  csrf_validate(url('pages/cart.php'));

  $isDraft = !empty($_POST['save_draft']);
  $status = $isDraft ? 'draft' : 'submitted';
  $notes = trim((string)($_POST['notes'] ?? ''));

  $stmt=$pdo->prepare("SELECT id FROM carts WHERE user_id=:u ORDER BY id DESC LIMIT 1");
  $stmt->execute([':u'=>$uid]);
  $cart_id=$stmt->fetchColumn();
  if(!$cart_id) die('Cart empty');

  $ci=$pdo->prepare("SELECT ci.*, p.name FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=:cid");
  $ci->execute([':cid'=>$cart_id]); $rows=$ci->fetchAll();
  if(!$rows) die('Cart empty');

  $u = $pdo->prepare("SELECT name,email,phone,company FROM users WHERE id=:id");
  $u->execute([':id'=>$uid]); $me=$u->fetch();

  $sub=0; foreach($rows as $r){ $sub += $r['qty']*$r['price_at_time']; }
  $ship=0.00; $total=$sub+$ship; $qn=quote_number();

  $pdo->beginTransaction();
  $pdo->prepare("INSERT INTO quotes(user_id,quote_number,status,notes,subtotal,shipping_fee,total,company,name,email,phone,created_at,updated_at)
                 VALUES(:u,:qn,:st,:notes,:s,:sh,:t,:c,:n,:e,:p,NOW(),NOW())")
      ->execute([':u'=>$uid,':qn'=>$qn,':st'=>$status,':notes'=>$notes,':s'=>$sub,':sh'=>$ship,':t'=>$total,':c'=>$me['company'],':n'=>$me['name'],':e'=>$me['email'],':p'=>$me['phone']]);
  $qid = $pdo->lastInsertId();
  $qi=$pdo->prepare("INSERT INTO quote_items(quote_id,product_id,qty,unit_price) VALUES(:q,:p,:qty,:u)");
  foreach($rows as $r){ $qi->execute([':q'=>$qid,':p'=>$r['product_id'],':qty'=>$r['qty'],':u'=>$r['price_at_time']]); }

  // Clear cart after submit/draft-save to keep the RFQ cart clean.
  $pdo->prepare("DELETE FROM cart_items WHERE cart_id=:c")->execute([':c'=>$cart_id]);
  $pdo->commit();

  // Email all admins on real submissions (not drafts)
  if (!$isDraft) {
    try {
      notify_admins_rfq((int)$qid);
    } catch (Throwable $e) {
      // do not block user flow if mail fails
      error_log('RFQ notify failed: ' . $e->getMessage());
    }
    // Flash a WhatsApp shortcut message for the user
    $_SESSION['rfq_last_number'] = $qn;
  }

  $msg = $isDraft ? 'draft' : 'submitted';
  header('Location: '.url('pages/quotes.php?msg='.$msg)); exit;
}

// Backwards-compatible: old link used GET save.
if($act==='save'){
  header('Location: '.url('pages/cart.php')); exit;
}

if($act==='reorder'){
  $qid=(int)($_GET['id'] ?? 0);
  $st=$pdo->prepare("SELECT id FROM quotes WHERE id=:id AND user_id=:u");
  $st->execute([':id'=>$qid, ':u'=>$uid]);
  if(!$st->fetch()) die('Not found');

  // create a fresh cart
  $pdo->prepare("INSERT INTO carts(user_id,created_at,updated_at) VALUES(:u,NOW(),NOW())")->execute([':u'=>$uid]);
  $cart_id=$pdo->lastInsertId();

  $rows=$pdo->prepare("SELECT product_id, qty, unit_price FROM quote_items WHERE quote_id=:q");
  $rows->execute([':q'=>$qid]);
  $ins=$pdo->prepare("INSERT INTO cart_items(cart_id,product_id,qty,price_at_time,created_at) VALUES(:c,:p,:q,:u,NOW())");
  foreach($rows as $r){
    if (empty($r['product_id'])) continue;
    $ins->execute([':c'=>$cart_id, ':p'=>$r['product_id'], ':q'=>$r['qty'], ':u'=>$r['unit_price']]);
  }
  header('Location: '.url('pages/cart.php')); exit;
}

http_response_code(400); echo 'Bad request';
