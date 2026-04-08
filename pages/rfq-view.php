<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/settings.php';

$uid = current_user_id();
if(!$uid){ header('Location: ' . url('pages/login.php')); exit; }

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=:id AND user_id=:u");
$st->execute([':id'=>$id, ':u'=>$uid]);
$q = $st->fetch();
if(!$q){ echo '<div class="alert error">RFQ not found.</div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

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
foreach($list as $it){
  $subtotal += ((int)$it['qty']) * ((float)($it['unit_price'] ?? 0));
}
$shipping = (float)($q['shipping_fee'] ?? 0);
$over = (float)($q['overhead_charge'] ?? 0);
$other = (float)($q['other_expenses'] ?? 0);
$inst = (float)($q['installation_expenses'] ?? 0);
$grand = (float)($q['total'] ?? ($subtotal + $shipping + $over + $other + $inst));

$wa = (string)setting('contact_whatsapp', '09453462354');
$msg = 'Hi! Following up on RFQ ' . $q['quote_number'] . '.';
$history = rfq_history($pdo, (int)$q['id']);
?>

<div class="page-head">
  <h1>RFQ <?php echo e($q['quote_number']); ?></h1>
  <p class="muted">Status: <span class="tag"><?php echo e($q['status']); ?></span></p>
</div>

<div class="grid" style="grid-template-columns:1.2fr .8fr;gap:16px;align-items:start">
  <div class="card p">
    <h3 class="m0">Items</h3>
    <table class="table mt16">
      <?php if($isQuoted): ?>
        <tr><th>Product</th><th style="width:90px">Qty</th><th style="width:140px">Unit</th><th style="width:160px">Line Total</th></tr>
      <?php else: ?>
        <tr><th>Product</th><th style="width:120px">Qty</th></tr>
      <?php endif; ?>
      <?php foreach($list as $it): ?>
<tr>
  <td>
    <?php echo e($it['name'] ?: 'Unknown product'); ?>
    <div class="muted"><?php echo e($it['brand'] ?: ''); ?><?php if($it['sku']): ?> • <?php echo e($it['sku']); ?><?php endif; ?></div>
  </td>
  <?php if($isQuoted): ?>
    <?php
      $qty = (int)$it['qty'];
      $unit = (float)($it['unit_price'] ?? 0);
      $line = $qty * $unit;
    ?>
    <td><?php echo $qty; ?></td>
    <td>₱<?php echo number_format($unit,2); ?></td>
    <td>₱<?php echo number_format($line,2); ?></td>
  <?php else: ?>
    <td><?php echo (int)$it['qty']; ?></td>
  <?php endif; ?>
</tr>
      <?php endforeach; ?>
    </table>

<?php if($isQuoted): ?>
  <div class="mt16" style="display:flex;justify-content:flex-end">
    <div style="min-width:320px">
      <div style="display:flex;justify-content:space-between;padding:6px 0"><span class="muted">Subtotal</span><strong>₱<?php echo number_format($subtotal,2); ?></strong></div>
      <div style="display:flex;justify-content:space-between;padding:6px 0"><span class="muted">Overhead Charge</span><strong>₱<?php echo number_format($over,2); ?></strong></div>
      <div style="display:flex;justify-content:space-between;padding:6px 0"><span class="muted">Other Expenses</span><strong>₱<?php echo number_format($other,2); ?></strong></div>
      <div style="display:flex;justify-content:space-between;padding:6px 0"><span class="muted">Installation Expenses</span><strong>₱<?php echo number_format($inst,2); ?></strong></div>
      <div style="display:flex;justify-content:space-between;padding:6px 0"><span class="muted">Shipping</span><strong>₱<?php echo number_format($shipping,2); ?></strong></div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid #e5e7eb;margin-top:6px"><span>Grand Total</span><strong style="font-size:18px">₱<?php echo number_format($grand,2); ?></strong></div>
    </div>
  </div>

  <div class="mt16" style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;padding:12px">
    <div style="font-weight:800;margin-bottom:8px">Quotation Terms</div>
    <div class="muted" style="line-height:1.65">
      <div><strong>Valid Until:</strong> <?php echo e($q['valid_until'] ?? '—'); ?></div>
      <div><strong>Lead Time:</strong> <?php echo e($q['lead_time'] ?? '—'); ?></div>
      <div><strong>Warranty:</strong> <?php echo e($q['warranty'] ?? '—'); ?></div>
      <div style="margin-top:8px"><strong>Payment Terms:</strong><br><?php echo nl2br(e($q['payment_terms'] ?? '—')); ?></div>
    </div>
  </div>
<?php else: ?>
  <div class="mt16 muted" style="font-size:13px;line-height:1.55">
    This RFQ does not include pricing. Our team will send back a formal quotation with prices, lead time, and availability.
  </div>
<?php endif; ?>

    <div class="mt16">
      <strong>Your notes:</strong><br>
      <div class="muted" style="white-space:pre-line"><?php echo e($q['notes'] ?: '—'); ?></div>
    </div>

    <div class="mt16 muted" style="font-size:13px;line-height:1.55">
      This RFQ does not include pricing. Our team will send back a formal quotation with prices, lead time, and availability.
    </div>
  </div>

  <div class="card p">
    <h3 class="m0">Actions</h3>
    <div class="mt16" style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn secondary" href="<?php echo url('pages/quote-pdf.php?id='.$q['id']); ?>" target="_blank" rel="noopener"><?php echo $isQuoted ? 'Download Quotation PDF' : 'Download RFQ PDF'; ?></a>
      <a class="btn secondary" target="_blank" rel="noopener" href="<?php echo e(wa_link($wa, $msg)); ?>">Chat on WhatsApp</a>
      <a class="btn ghost" href="<?php echo url('pages/quotes.php'); ?>">Back</a>
    </div>
  </div>

  <div class="card p mt16">
    <h3 class="m0">RFQ Timeline</h3>
    <div class="mt16" style="display:flex;flex-direction:column;gap:10px">
      <?php if($history): ?>
        <?php foreach($history as $entry): ?>
          <?php
            $from = trim((string)($entry['from_status'] ?? ''));
            $to = trim((string)($entry['to_status'] ?? ''));
            $title = ucwords(str_replace('_',' ', (string)($entry['event_type'] ?? 'update')));
            if ($from !== '' || $to !== '') {
              $title .= ' · ' . ($from !== '' ? ucfirst($from) : '—') . ' → ' . ($to !== '' ? ucfirst($to) : '—');
            }
          ?>
          <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff">
            <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:12px;color:#64748b;margin-bottom:6px">
              <span><?php echo e($entry['created_at']); ?></span>
              <span><?php echo e($entry['acted_by_name'] ?: 'Team update'); ?></span>
            </div>
            <div style="font-weight:800;margin-bottom:4px"><?php echo e($title); ?></div>
            <div class="muted" style="white-space:pre-line;line-height:1.55"><?php echo e($entry['note'] ?: 'No additional note.'); ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="muted">No timeline activity recorded yet.</div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
