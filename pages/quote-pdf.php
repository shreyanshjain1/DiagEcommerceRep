<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/settings.php';

$uid = current_user_id();
if(!$uid){ http_response_code(403); exit('Login required'); }

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=:id AND user_id=:u");
$st->execute([':id'=>$id, ':u'=>$uid]);
$q = $st->fetch();
if(!$q){ http_response_code(404); exit('RFQ not found'); }

$items = $pdo->prepare("
  SELECT qi.qty, qi.unit_price, p.name, p.brand, p.sku
  FROM quote_items qi
  LEFT JOIN products p ON p.id=qi.product_id
  WHERE qi.quote_id=:qid
  ORDER BY qi.id ASC
");
$items->execute([':qid'=>$id]);
$list = $items->fetchAll();

$isQuoted = (($q['status'] ?? '') === 'quoted');
$subtotal = 0.0;
foreach($list as $it){ $subtotal += ((int)$it['qty']) * ((float)($it['unit_price'] ?? 0)); }
$shipping = (float)($q['shipping_fee'] ?? 0);
$over = (float)($q['overhead_charge'] ?? 0);
$other = (float)($q['other_expenses'] ?? 0);
$inst = (float)($q['installation_expenses'] ?? 0);
$grand = (float)($q['total'] ?? ($subtotal + $shipping + $over + $other + $inst));

$site = (string)setting('site_name', $SITE_NAME);
$hotline = (string)setting('contact_hotline', '+639691229230');
$email = (string)setting('contact_email', $ADMIN_EMAIL);
$wa = (string)setting('contact_whatsapp', '+639453462354');

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $isQuoted ? 'Quotation' : 'RFQ'; ?> <?php echo e($q['quote_number']); ?></title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:24px}
  .top{display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
  .brand{font-size:18px;font-weight:800}
  .muted{color:#555}
  table{width:100%;border-collapse:collapse;margin-top:16px}
  th,td{border:1px solid #ddd;padding:10px;font-size:13px;vertical-align:top}
  th{background:#f7f7f7;text-align:left}
  .right{text-align:right}
  .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#eef7ee;font-size:12px}
  .notes{white-space:pre-line;border:1px solid #ddd;padding:10px;border-radius:8px;margin-top:10px}
  @media print { a{display:none} }
</style>
</head>
<body>
  <div class="top">
    <div>
      <div class="brand"><?php echo e($site); ?></div>
      <div class="muted">Hotline: <?php echo e($hotline); ?> • WhatsApp: <?php echo e($wa); ?> • Email: <?php echo e($email); ?></div>
    </div>
    <div style="text-align:right">
      <div><strong>RFQ #:</strong> <?php echo e($q['quote_number']); ?></div>
      <div><strong>Status:</strong> <span class="pill"><?php echo e($q['status']); ?></span></div>
      <div class="muted"><?php echo e($q['created_at']); ?></div>
    </div>
  </div>

  <?php if($isQuoted): ?>
  <h2 style="margin-top:22px;margin-bottom:6px">Official Quotation</h2>
  <div class="muted" style="font-size:13px">Pricing and charges are indicated below. Please reference the quotation number for your Purchase Order.</div>
  <?php else: ?>
  <h2 style="margin-top:22px;margin-bottom:6px">Request for Quotation (RFQ)</h2>
  <div class="muted" style="font-size:13px">This document lists requested items and quantities. Pricing will be provided in the official quotation.</div>
  <?php endif; ?>

  <h3 style="margin-top:18px;margin-bottom:6px">Customer</h3>
  <div style="font-size:13px">
    <div><strong>Name:</strong> <?php echo e($q['name']); ?></div>
    <?php if(!empty($q['company'])): ?><div><strong>Company:</strong> <?php echo e($q['company']); ?></div><?php endif; ?>
    <div><strong>Email:</strong> <?php echo e($q['email']); ?></div>
    <?php if(!empty($q['phone'])): ?><div><strong>Phone:</strong> <?php echo e($q['phone']); ?></div><?php endif; ?>
  </div>

  <h3 style="margin-top:18px;margin-bottom:6px">Items</h3>
  <table>
    <?php if($isQuoted): ?>
      <tr><th>Product</th><th style="width:80px">Qty</th><th style="width:140px" class="right">Unit</th><th style="width:160px" class="right">Line Total</th></tr>
    <?php else: ?>
      <tr><th>Product</th><th style="width:90px">Qty</th></tr>
    <?php endif; ?>
    <?php foreach($list as $it): ?>
      <tr>
        <td>
          <strong><?php echo e($it['name'] ?: 'Unknown product'); ?></strong><br>
          <span class="muted"><?php echo e($it['brand'] ?: ''); ?><?php if($it['sku']): ?> • <?php echo e($it['sku']); ?><?php endif; ?></span>
        </td>
        <?php if($isQuoted): ?>
          <?php
            $qty = (int)$it['qty'];
            $unit = (float)($it['unit_price'] ?? 0);
            $line = $qty * $unit;
          ?>
          <td><?php echo $qty; ?></td>
          <td class="right">₱<?php echo number_format($unit,2); ?></td>
          <td class="right">₱<?php echo number_format($line,2); ?></td>
        <?php else: ?>
          <td><?php echo (int)$it['qty']; ?></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if($isQuoted): ?>
  <h3 style="margin-top:18px;margin-bottom:6px">Totals</h3>
  <table>
    <tr><th class="right">Subtotal</th><td class="right">₱<?php echo number_format($subtotal,2); ?></td></tr>
    <tr><th class="right">Overhead Charge</th><td class="right">₱<?php echo number_format($over,2); ?></td></tr>
    <tr><th class="right">Other Expenses</th><td class="right">₱<?php echo number_format($other,2); ?></td></tr>
    <tr><th class="right">Installation Expenses</th><td class="right">₱<?php echo number_format($inst,2); ?></td></tr>
    <tr><th class="right">Shipping</th><td class="right">₱<?php echo number_format($shipping,2); ?></td></tr>
    <tr><th class="right" style="font-size:15px">Grand Total</th><td class="right" style="font-size:15px"><strong>₱<?php echo number_format($grand,2); ?></strong></td></tr>
  </table>

  <h3 style="margin-top:18px;margin-bottom:6px">Terms</h3>
  <div class="notes">
    <div><strong>Valid Until:</strong> <?php echo e($q['valid_until'] ?? '—'); ?></div>
    <div><strong>Lead Time:</strong> <?php echo e($q['lead_time'] ?? '—'); ?></div>
    <div><strong>Warranty:</strong> <?php echo e($q['warranty'] ?? '—'); ?></div>
    <div style="margin-top:8px"><strong>Payment Terms:</strong><br><?php echo nl2br(e($q['payment_terms'] ?? '—')); ?></div>
  </div>
<?php endif; ?>

  <h3 style="margin-top:18px;margin-bottom:6px">Notes</h3>
  <div class="notes"><?php echo e($q['notes'] ?: '—'); ?></div>

  <div class="muted" style="font-size:12px;margin-top:16px">
    For follow-up, contact us via WhatsApp or email and reference RFQ #<?php echo e($q['quote_number']); ?>.
  </div>

  <div style="margin-top:18px">
    <a href="#" onclick="window.print();return false;">Print / Save as PDF</a>
  </div>
</body>
</html>
