<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? 'submitted');
$allowed = ['draft','submitted','quoted','closed'];
if(!in_array($status,$allowed,true)) $status = 'submitted';
$admin_notes = trim((string)($_POST['admin_notes'] ?? ''));

$shipping_fee_in = is_numeric($_POST['shipping_fee'] ?? null) ? (float)$_POST['shipping_fee'] : null;
$overhead_in = is_numeric($_POST['overhead_charge'] ?? null) ? (float)$_POST['overhead_charge'] : null;
$other_in = is_numeric($_POST['other_expenses'] ?? null) ? (float)$_POST['other_expenses'] : null;
$install_in = is_numeric($_POST['installation_expenses'] ?? null) ? (float)$_POST['installation_expenses'] : null;

$valid_until = trim((string)($_POST['valid_until'] ?? ''));
$lead_time = trim((string)($_POST['lead_time'] ?? ''));
$warranty = trim((string)($_POST['warranty'] ?? ''));
$payment_terms = trim((string)($_POST['payment_terms'] ?? ''));

$item_prices = $_POST['item_price'] ?? [];
if (!is_array($item_prices)) $item_prices = [];

try {
  $pdo->beginTransaction();

  // Update item prices (optional)
  $up = $pdo->prepare("UPDATE quote_items SET unit_price=:p WHERE id=:id AND quote_id=:qid");
  foreach($item_prices as $itemId => $price){
    $iid = (int)$itemId;
    if ($iid <= 0) continue;
    $p = is_numeric($price) ? (float)$price : 0.0;
    if ($p < 0) $p = 0.0;
    $up->execute([':p'=>$p, ':id'=>$iid, ':qid'=>$id]);
  }

  // Recompute totals
  $sum = $pdo->prepare("SELECT COALESCE(SUM(qty * unit_price),0) AS subtotal FROM quote_items WHERE quote_id=:qid");
  $sum->execute([':qid'=>$id]);
  $subtotal = (float)($sum->fetch()['subtotal'] ?? 0);

    $ship = $pdo->prepare("SELECT COALESCE(shipping_fee,0) AS ship, COALESCE(overhead_charge,0) AS ov, COALESCE(other_expenses,0) AS ot, COALESCE(installation_expenses,0) AS ins FROM quotes WHERE id=:id");
  $ship->execute([':id'=>$id]);
  $row = $ship->fetch() ?: [];

  $shipping_fee = ($shipping_fee_in !== null && $shipping_fee_in >= 0) ? $shipping_fee_in : (float)($row['ship'] ?? 0);
  $overhead = ($overhead_in !== null && $overhead_in >= 0) ? $overhead_in : (float)($row['ov'] ?? 0);
  $other = ($other_in !== null && $other_in >= 0) ? $other_in : (float)($row['ot'] ?? 0);
  $install = ($install_in !== null && $install_in >= 0) ? $install_in : (float)($row['ins'] ?? 0);

  $total = $subtotal + $shipping_fee + $overhead + $other + $install;

  $st = $pdo->prepare("UPDATE quotes SET status=:s, admin_notes=:n, subtotal=:sub, shipping_fee=:sh, overhead_charge=:ov, other_expenses=:ot, installation_expenses=:ins, valid_until=:vu, lead_time=:lt, warranty=:w, payment_terms=:pt, total=:t, updated_at=NOW() WHERE id=:id");
  $st->execute([
    ':s'=>$status,
    ':n'=>$admin_notes,
    ':sub'=>$subtotal,
    ':sh'=>$shipping_fee,
    ':ov'=>$overhead,
    ':ot'=>$other,
    ':ins'=>$install,
    ':vu'=>($valid_until!=='' ? $valid_until : null),
    ':lt'=>($lead_time!=='' ? $lead_time : null),
    ':w'=>($warranty!=='' ? $warranty : null),
    ':pt'=>($payment_terms!=='' ? $payment_terms : null),
    ':t'=>$total,
    ':id'=>$id
  ]);

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
}

header('Location: '.url('admin/rfq-view.php?id='.$id.'&saved=1'));
exit;
