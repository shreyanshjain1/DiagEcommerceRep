<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/settings.php';

$uid = current_user_id();
if (!$uid) { header('Location: ' . url('pages/login.php')); exit; }

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=:id AND user_id=:u");
$st->execute([':id' => $id, ':u' => $uid]);
$q = $st->fetch();
if (!$q) {
  echo '<div class="alert error">RFQ not found.</div>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$items = $pdo->prepare("SELECT qi.qty, qi.unit_price, p.name, p.brand, p.sku
                        FROM quote_items qi
                        LEFT JOIN products p ON p.id = qi.product_id
                        WHERE qi.quote_id = :qid
                        ORDER BY qi.id ASC");
$items->execute([':qid' => $id]);
$list = $items->fetchAll();

$isQuoted = (($q['status'] ?? '') === 'quoted');
$subtotal = 0.0;
foreach ($list as $it) {
  $subtotal += ((int)$it['qty']) * ((float)($it['unit_price'] ?? 0));
}
$shipping = (float)($q['shipping_fee'] ?? 0);
$over = (float)($q['overhead_charge'] ?? 0);
$other = (float)($q['other_expenses'] ?? 0);
$inst = (float)($q['installation_expenses'] ?? 0);
$grand = (float)($q['total'] ?? ($subtotal + $shipping + $over + $other + $inst));

$wa = (string)setting('contact_whatsapp', '09453462354');
$msg = 'Hi! Following up on RFQ ' . $q['quote_number'] . '.';
$status = strtolower((string)($q['status'] ?? 'draft'));
$statusClass = 'submitted';
if ($status === 'quoted') $statusClass = 'quoted';
if ($status === 'closed') $statusClass = 'closed';
?>

<div class="page-head">
  <div>
    <span class="kicker">RFQ Workspace</span>
    <h1><?php echo e($q['quote_number']); ?></h1>
    <div class="muted" style="margin-top:8px;max-width:760px;line-height:1.6">This refreshed RFQ view uses a stronger product-style layout with a clearer status journey, cleaner summary blocks, and better recruiter-facing UI polish.</div>
    <div class="rfq-stepper">
      <span class="rfq-step <?php echo in_array($status, ['draft','submitted','quoted','closed'], true) ? 'active' : ''; ?>">1. Submitted</span>
      <span class="rfq-step <?php echo in_array($status, ['quoted','closed'], true) ? 'active' : ''; ?>">2. Quoted</span>
      <span class="rfq-step <?php echo $status === 'closed' ? 'active' : ''; ?>">3. Closed</span>
    </div>
  </div>
  <div>
    <span class="tag <?php echo e($statusClass); ?>"><?php echo e(ucfirst($q['status'])); ?></span>
  </div>
</div>

<div class="rfq-shell">
  <section class="surface rfq-items-card">
    <div class="admin-panel-head">
      <div>
        <h3 class="admin-panel-title">Requested Items</h3>
        <p class="admin-panel-sub">Cleaner tabular presentation with line totals when the admin has already sent pricing.</p>
      </div>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Product</th>
          <th style="width:90px">Qty</th>
          <?php if ($isQuoted): ?>
            <th style="width:140px">Unit</th>
            <th style="width:160px">Line Total</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $it): ?>
          <tr>
            <td>
              <strong><?php echo e($it['name'] ?: 'Unknown product'); ?></strong>
              <div class="muted"><?php echo e($it['brand'] ?: ''); ?><?php if (!empty($it['sku'])): ?> · <?php echo e($it['sku']); ?><?php endif; ?></div>
            </td>
            <td><?php echo (int)$it['qty']; ?></td>
            <?php if ($isQuoted): ?>
              <?php $unit = (float)($it['unit_price'] ?? 0); $line = ((int)$it['qty']) * $unit; ?>
              <td>₱<?php echo number_format($unit, 2); ?></td>
              <td>₱<?php echo number_format($line, 2); ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($isQuoted): ?>
      <div class="rfq-totals">
        <div class="info-row"><span class="info-label">Subtotal</span><span class="info-value">₱<?php echo number_format($subtotal, 2); ?></span></div>
        <div class="info-row"><span class="info-label">Overhead Charge</span><span class="info-value">₱<?php echo number_format($over, 2); ?></span></div>
        <div class="info-row"><span class="info-label">Other Expenses</span><span class="info-value">₱<?php echo number_format($other, 2); ?></span></div>
        <div class="info-row"><span class="info-label">Installation</span><span class="info-value">₱<?php echo number_format($inst, 2); ?></span></div>
        <div class="info-row"><span class="info-label">Shipping</span><span class="info-value">₱<?php echo number_format($shipping, 2); ?></span></div>
        <div class="info-row"><span class="info-label">Grand Total</span><span class="info-value">₱<?php echo number_format($grand, 2); ?></span></div>
      </div>

      <div class="rfq-note-box">
        <strong>Quotation Terms</strong>
        <div class="info-list">
          <div class="info-row"><span class="info-label">Valid Until</span><span class="info-value"><?php echo e($q['valid_until'] ?? '—'); ?></span></div>
          <div class="info-row"><span class="info-label">Lead Time</span><span class="info-value"><?php echo e($q['lead_time'] ?? '—'); ?></span></div>
          <div class="info-row"><span class="info-label">Warranty</span><span class="info-value"><?php echo e($q['warranty'] ?? '—'); ?></span></div>
          <div class="info-row"><span class="info-label">Payment Terms</span><span class="info-value"><?php echo nl2br(e($q['payment_terms'] ?? '—')); ?></span></div>
        </div>
      </div>
    <?php else: ?>
      <div class="rfq-note-box muted">This RFQ does not include pricing yet. The admin team will return a formal quotation with commercial terms and pricing details.</div>
    <?php endif; ?>

    <div class="rfq-note-box">
      <strong>Your notes</strong>
      <div class="muted" style="white-space:pre-line;margin-top:8px"><?php echo e($q['notes'] ?: '—'); ?></div>
    </div>
  </section>

  <aside class="surface rfq-actions-card">
    <div class="admin-panel-head">
      <div>
        <h3 class="admin-panel-title">Actions & Summary</h3>
        <p class="admin-panel-sub">Cleaner side rail for downloads, follow-up, and core RFQ metadata.</p>
      </div>
    </div>

    <div class="rfq-actions-stack">
      <a class="btn secondary" href="<?php echo url('pages/quote-pdf.php?id=' . $q['id']); ?>" target="_blank" rel="noopener"><?php echo $isQuoted ? 'Download Quotation PDF' : 'Download RFQ PDF'; ?></a>
      <a class="btn" target="_blank" rel="noopener" href="<?php echo e(wa_link($wa, $msg)); ?>">Follow up on WhatsApp</a>
      <a class="btn ghost" href="<?php echo url('pages/quotes.php'); ?>">Back to My RFQs</a>
    </div>

    <div class="rfq-side-meta">
      <div class="rfq-mini">
        <strong>Status</strong>
        <span class="tag <?php echo e($statusClass); ?>"><?php echo e(ucfirst($q['status'])); ?></span>
      </div>
      <div class="rfq-mini">
        <strong>Submitted</strong>
        <div class="muted"><?php echo e($q['created_at'] ?? '—'); ?></div>
      </div>
      <div class="rfq-mini">
        <strong>Quote Readiness</strong>
        <div class="muted"><?php echo $isQuoted ? 'Commercial pricing is already attached to this RFQ.' : 'Waiting for admin pricing and commercial terms.'; ?></div>
      </div>
    </div>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
