<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';

$uid = current_user_id();
if(!$uid){ die('Login required'); }

$id = (int)($_GET['id'] ?? 0);
$ord = $pdo->prepare("SELECT * FROM orders WHERE id=:id AND user_id=:u");
$ord->execute([':id'=>$id, ':u'=>$uid]);
$o = $ord->fetch();
if(!$o){ die('PO not found'); }

$items = $pdo->prepare("SELECT oi.qty,oi.unit_price,p.name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:id");
$items->execute([':id'=>$o['id']]);
$rows = $items->fetchAll();

$pending = (strtolower($o['status'])==='pending');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>PO <?php echo htmlspecialchars($o['order_number']); ?></title>
<style>
  :root{ --ink:#0f172a; --muted:#6b7280; --line:#e5e7eb; --accent:#16a34a; }
  @page { size: A4; margin: 18mm; }
  *{box-sizing:border-box}
  body{font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color:var(--ink)}
  .wrap{max-width:800px;margin:0 auto;position:relative}
  .watermark{
    position:fixed; inset:0; display:flex; justify-content:center; align-items:center;
    font-size:80px; font-weight:900; color:rgba(234,179,8,.18); transform:rotate(-18deg); pointer-events:none;
  }
  .top{display:flex;justify-content:space-between;align-items:center}
  .brand{display:flex;align-items:center;gap:12px}
  .brand img{height:36px}
  h1{margin:0;font-size:28px}
  .small{color:var(--muted)}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px}
  .box{border:1px solid var(--line);border-radius:12px;padding:12px}
  .box h3{margin:0 0 6px}
  table{width:100%;border-collapse:separate;border-spacing:0;margin-top:16px;border:1px solid var(--line);border-radius:12px;overflow:hidden}
  th,td{padding:10px 12px;border-bottom:1px solid var(--line);text-align:left}
  th{background:#fafafa}
  tr:last-child td{border-bottom:0}
  .totals{display:flex;justify-content:flex-end;margin-top:12px}
  .totals .sum{width:300px;border:1px solid var(--line);border-radius:12px;padding:12px}
  .sum .row{display:flex;justify-content:space-between;margin:6px 0}
  .sum .row.total{font-weight:900}
  .note{margin-top:14px;padding:12px;border:1px dashed var(--line);border-radius:12px;background:#f8fff9}
  .print{margin-top:16px}
  .btn{display:inline-block;background:var(--accent);color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none}
  @media print {.print{display:none}}
</style>
</head>
<body>
<div class="wrap">
  <?php if($pending): ?><div class="watermark">PAYMENT PENDING</div><?php endif; ?>

  <div class="top">
    <div class="brand">
      <img src="<?php echo asset('assets/img/logo.png'); ?>" alt="Logo">
      <div>
        <h1>Purchase Order</h1>
        <div class="small">PO #: <?php echo htmlspecialchars($o['order_number']); ?></div>
      </div>
    </div>
    <div style="text-align:right">
      <strong><?php echo htmlspecialchars($SITE_NAME); ?></strong><br>
      Email: <?php echo htmlspecialchars($ADMIN_EMAIL); ?><br>
      Date: <?php echo htmlspecialchars($o['created_at']); ?>
    </div>
  </div>

  <div class="grid">
    <div class="box">
      <h3>Ordered By</h3>
      <div><strong><?php echo htmlspecialchars($o['company'] ?: ''); ?></strong></div>
      <div><?php echo htmlspecialchars($o['name']); ?></div>
      <div class="small"><?php echo htmlspecialchars($o['email']); ?> • <?php echo htmlspecialchars($o['phone']); ?></div>
    </div>
    <div class="box">
      <h3>Ship To</h3>
      <div><strong><?php echo htmlspecialchars($o['company'] ?: ''); ?></strong></div>
      <div><?php echo htmlspecialchars($o['address_line1']); ?></div>
      <?php if(!empty($o['address_line2'])): ?><div><?php echo htmlspecialchars($o['address_line2']); ?></div><?php endif; ?>
      <div><?php echo htmlspecialchars($o['city'].', '.$o['province'].' '.$o['postal_code']); ?></div>
    </div>
  </div>

  <table>
    <tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['name'] ?? 'Product'); ?></td>
        <td><?php echo (int)$r['qty']; ?></td>
        <td>₱<?php echo number_format($r['unit_price'],2); ?></td>
        <td>₱<?php echo number_format($r['unit_price']*$r['qty'],2); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="totals">
    <div class="sum">
      <div class="row"><span>Subtotal</span><span>₱<?php echo number_format($o['subtotal'],2); ?></span></div>
      <div class="row"><span>Shipping</span><span>₱<?php echo number_format($o['shipping_fee'],2); ?></span></div>
      <div class="row total"><span>Total</span><span>₱<?php echo number_format($o['total'],2); ?></span></div>
    </div>
  </div>

  <div class="note">
    <?php if($pending): ?>
      This Purchase Order is <strong>pending payment</strong>. It is provided for your internal company documentation and is <strong>not</strong> a receipt.
    <?php else: ?>
      This Purchase Order reflects items requested. Refer to your invoice/receipt for payment details.
    <?php endif; ?>
  </div>

  <p class="print"><a class="btn" href="#" onclick="window.print();return false;">Print / Save as PDF</a></p>
</div>
</body>
</html>
