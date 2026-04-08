<?php
require_once __DIR__.'/header.php';

$today = date('Y-m-d');
$sevenDaysAhead = date('Y-m-d', strtotime('+7 days'));
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

$k = [
  'rfqs_total' => (int)$pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn(),
  'rfqs_submitted' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='submitted'")->fetchColumn(),
  'rfqs_quoted' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='quoted'")->fetchColumn(),
  'rfqs_closed' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='closed'")->fetchColumn(),
  'quoted_value' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM quotes WHERE status IN ('quoted','closed')")->fetchColumn(),
  'open_inquiries' => (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status IN ('New','Open')")->fetchColumn(),
  'company_accounts' => (int)$pdo->query("SELECT COUNT(*) FROM company_accounts")->fetchColumn(),
  'active_products' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn(),
  'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=5")->fetchColumn(),
  'stale_submitted' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='submitted' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
  'expiring_soon' => (int)$pdo->prepare("SELECT COUNT(*) FROM quotes WHERE status='quoted' AND valid_until IS NOT NULL AND valid_until BETWEEN ? AND ?") ?: 0,
];
if ($k['expiring_soon'] instanceof PDOStatement) {
  $k['expiring_soon']->execute([$today, $sevenDaysAhead]);
  $k['expiring_soon'] = (int)$k['expiring_soon']->fetchColumn();
}
$k['response_due'] = $k['rfqs_submitted'] + $k['stale_submitted'];

$statusRows = $pdo->query("SELECT status, COUNT(*) c, COALESCE(SUM(total),0) value_total FROM quotes GROUP BY status ORDER BY FIELD(status,'submitted','quoted','closed','draft')")->fetchAll(PDO::FETCH_ASSOC);
$pipeline = [
  'draft' => ['count' => 0, 'value' => 0.0],
  'submitted' => ['count' => 0, 'value' => 0.0],
  'quoted' => ['count' => 0, 'value' => 0.0],
  'closed' => ['count' => 0, 'value' => 0.0],
];
foreach ($statusRows as $row) {
  $status = (string)($row['status'] ?? 'draft');
  if (!isset($pipeline[$status])) continue;
  $pipeline[$status] = [
    'count' => (int)$row['c'],
    'value' => (float)$row['value_total'],
  ];
}
$maxPipelineCount = max(array_map(fn($row) => (int)$row['count'], $pipeline)) ?: 1;

$months = [];
for ($i = 5; $i >= 0; $i--) {
  $ym = date('Y-m', strtotime("-{$i} months"));
  $months[$ym] = ['count' => 0, 'value' => 0.0];
}
$st = $pdo->prepare(
  "SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) qty, COALESCE(SUM(total),0) quoted_value
   FROM quotes
   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
   GROUP BY ym
   ORDER BY ym"
);
$st->execute();
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
  if (!isset($months[$row['ym']])) continue;
  $months[$row['ym']] = [
    'count' => (int)$row['qty'],
    'value' => (float)$row['quoted_value'],
  ];
}
$maxMonthCount = max(array_map(fn($row) => (int)$row['count'], $months)) ?: 1;

$recentRfqs = $pdo->query(
  "SELECT q.id, q.quote_number, q.company, q.name, q.email, q.status, q.total, q.valid_until, q.created_at, q.updated_at
   FROM quotes q
   ORDER BY q.id DESC
   LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

$recentInq = $pdo->query(
  "SELECT id, company, name, subject, status, created_at
   FROM inquiries
   ORDER BY id DESC
   LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$topRequested = $pdo->query(
  "SELECT p.id, p.name, p.sku, p.brand,
          SUM(qi.qty) AS requested_qty,
          COUNT(DISTINCT qi.quote_id) AS rfq_count,
          MAX(q.created_at) AS last_requested_at
   FROM quote_items qi
   INNER JOIN quotes q ON q.id = qi.quote_id
   LEFT JOIN products p ON p.id = qi.product_id
   GROUP BY p.id, p.name, p.sku, p.brand
   ORDER BY requested_qty DESC, rfq_count DESC, p.name ASC
   LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$stockRisks = $pdo->query(
  "SELECT id, name, sku, brand, stock
   FROM products
   WHERE is_active = 1 AND stock <= 5
   ORDER BY stock ASC, name ASC
   LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$companyActivity = $pdo->query(
  "SELECT ca.id, ca.company_name, ca.account_code,
          COUNT(DISTINCT c.user_id) AS contact_count,
          COUNT(DISTINCT q.id) AS rfq_count,
          MAX(q.created_at) AS last_rfq_at
   FROM company_accounts ca
   LEFT JOIN company_account_contacts c ON c.company_account_id = ca.id AND c.invite_status <> 'inactive'
   LEFT JOIN users u ON u.company_account_id = ca.id
   LEFT JOIN quotes q ON q.user_id = u.id
   GROUP BY ca.id, ca.company_name, ca.account_code
   ORDER BY rfq_count DESC, last_rfq_at DESC, ca.company_name ASC
   LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$avgResponseHours = (float)$pdo->query(
  "SELECT COALESCE(AVG(TIMESTAMPDIFF(HOUR, created_at, sent_at)),0)
   FROM quotes
   WHERE status IN ('quoted','closed') AND sent_at IS NOT NULL"
)->fetchColumn();

$quotedConversion = $k['rfqs_total'] > 0 ? (($k['rfqs_quoted'] + $k['rfqs_closed']) / $k['rfqs_total']) * 100 : 0;
$closeRate = $k['rfqs_total'] > 0 ? ($k['rfqs_closed'] / $k['rfqs_total']) * 100 : 0;
?>

<h1>RFQ Operations Dashboard</h1>
<p class="muted" style="margin-top:-4px">Executive snapshot for quotations, inquiries, accounts, and product readiness.</p>

<div class="admin-stats">
  <div class="stat"><div class="k">Total RFQs</div><div class="v"><?php echo number_format($k['rfqs_total']); ?></div></div>
  <div class="stat"><div class="k">Submitted RFQs</div><div class="v"><?php echo number_format($k['rfqs_submitted']); ?></div></div>
  <div class="stat"><div class="k">Quoted RFQs</div><div class="v"><?php echo number_format($k['rfqs_quoted']); ?></div></div>
  <div class="stat"><div class="k">Closed RFQs</div><div class="v"><?php echo number_format($k['rfqs_closed']); ?></div></div>
  <div class="stat"><div class="k">Quoted Value</div><div class="v">₱<?php echo number_format($k['quoted_value'],2); ?></div></div>
  <div class="stat"><div class="k">Open Inquiries</div><div class="v"><?php echo number_format($k['open_inquiries']); ?></div></div>
</div>

<div class="grid" style="grid-template-columns: 1.4fr .6fr; gap:16px; margin-top:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div style="font-weight:950;font-size:16px">RFQ volume trend</div>
        <div class="muted">Last 6 months of quotation activity</div>
      </div>
      <a class="btn ghost" href="<?php echo url('admin/rfqs.php'); ?>">Open RFQs</a>
    </div>
    <div style="height:220px;margin-top:14px">
      <svg viewBox="0 0 600 220" width="100%" height="220" role="img" aria-label="RFQ volume chart">
        <line x1="20" y1="200" x2="580" y2="200" stroke="rgba(2,6,23,.15)" />
        <?php $i = 0; $count = count($months); $barW = (560 / max($count,1)) - 18; foreach ($months as $ym => $row):
          $x = 30 + $i * (560 / max($count,1));
          $h = $maxMonthCount > 0 ? (160 * ($row['count'] / $maxMonthCount)) : 0;
          $y = 200 - $h;
        ?>
          <rect x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $barW; ?>" height="<?php echo $h; ?>" rx="10" fill="rgba(37,99,235,.28)" stroke="rgba(37,99,235,.55)" />
          <text x="<?php echo $x + ($barW / 2); ?>" y="214" text-anchor="middle" font-size="11" fill="rgba(2,6,23,.55)"><?php echo e(date('M', strtotime($ym.'-01'))); ?></text>
          <text x="<?php echo $x + ($barW / 2); ?>" y="<?php echo max(18, $y - 8); ?>" text-anchor="middle" font-size="11" fill="rgba(2,6,23,.7)"><?php echo (int)$row['count']; ?></text>
        <?php $i++; endforeach; ?>
      </svg>
    </div>
  </div>

  <div class="card p">
    <div style="font-weight:950;font-size:16px">Ops health</div>
    <div class="muted" style="margin-top:2px">Fast recruiter-friendly business signals</div>
    <div style="display:grid;gap:12px;margin-top:14px">
      <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">Avg response time</span><strong><?php echo number_format($avgResponseHours,1); ?> hrs</strong></div>
      <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">Quote conversion</span><strong><?php echo number_format($quotedConversion,1); ?>%</strong></div>
      <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">Close rate</span><strong><?php echo number_format($closeRate,1); ?>%</strong></div>
      <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">Company accounts</span><strong><?php echo number_format($k['company_accounts']); ?></strong></div>
      <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">Active products</span><strong><?php echo number_format($k['active_products']); ?></strong></div>
      <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">Low stock items</span><strong><?php echo number_format($k['low_stock']); ?></strong></div>
    </div>
    <div style="display:grid;gap:10px;margin-top:18px">
      <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">Add new product</a>
      <a class="btn secondary" href="<?php echo url('admin/company-accounts.php'); ?>">Review company accounts</a>
      <a class="btn ghost" href="<?php echo url('admin/inquiries.php'); ?>">Review inquiries</a>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns: repeat(4, 1fr); gap:16px; margin-top:16px">
  <?php foreach (['draft' => 'Draft', 'submitted' => 'Submitted', 'quoted' => 'Quoted', 'closed' => 'Closed'] as $statusKey => $statusLabel):
    $row = $pipeline[$statusKey];
    $bar = (int)round(($row['count'] / $maxPipelineCount) * 100);
  ?>
    <div class="card p">
      <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em"><?php echo e($statusLabel); ?></div>
      <div style="font-size:28px;font-weight:950;margin-top:6px"><?php echo number_format($row['count']); ?></div>
      <div class="muted">₱<?php echo number_format($row['value'],2); ?></div>
      <div style="height:8px;background:rgba(148,163,184,.18);border-radius:999px;overflow:hidden;margin-top:14px">
        <div style="height:8px;width:<?php echo $bar; ?>%;background:linear-gradient(90deg, rgba(37,99,235,.9), rgba(16,185,129,.75));border-radius:999px"></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid" style="grid-template-columns: 1.1fr .9fr; gap:16px; margin-top:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div style="font-weight:950">Recent RFQs</div>
        <div class="muted">Latest submissions and quotation activity</div>
      </div>
      <a class="link" href="<?php echo url('admin/rfqs.php'); ?>">Open all</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>RFQ</th><th>Status</th><th style="text-align:right">Value</th></tr></thead>
        <tbody>
          <?php foreach ($recentRfqs as $rfq): ?>
            <tr>
              <td>
                <a href="<?php echo url('admin/rfq-view.php?id='.(int)$rfq['id']); ?>"><?php echo e($rfq['quote_number']); ?></a><br>
                <span class="muted" style="font-size:12px"><?php echo e($rfq['company'] ?: $rfq['name']); ?> · <?php echo e(date('Y-m-d', strtotime($rfq['created_at']))); ?></span>
              </td>
              <td>
                <span class="badge"><?php echo e(ucfirst($rfq['status'])); ?></span><br>
                <span class="muted" style="font-size:12px">Updated <?php echo e(date('Y-m-d', strtotime($rfq['updated_at']))); ?></span>
              </td>
              <td style="text-align:right">
                ₱<?php echo number_format((float)$rfq['total'],2); ?><br>
                <span class="muted" style="font-size:12px"><?php echo !empty($rfq['valid_until']) ? 'Valid until '.e($rfq['valid_until']) : 'No validity set'; ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentRfqs): ?>
            <tr><td colspan="3" class="muted">No RFQs yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card p">
    <div style="font-weight:950">Operational watchlist</div>
    <div style="display:grid;gap:12px;margin-top:12px">
      <div style="border:1px solid rgba(148,163,184,.22);border-radius:16px;padding:14px">
        <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em">Response queue</div>
        <div style="font-size:24px;font-weight:900;margin-top:6px"><?php echo number_format($k['rfqs_submitted']); ?></div>
        <div class="muted">Submitted RFQs waiting for quotation</div>
      </div>
      <div style="border:1px solid rgba(148,163,184,.22);border-radius:16px;padding:14px">
        <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em">Stale RFQs</div>
        <div style="font-size:24px;font-weight:900;margin-top:6px"><?php echo number_format($k['stale_submitted']); ?></div>
        <div class="muted">Submitted more than 7 days ago</div>
      </div>
      <div style="border:1px solid rgba(148,163,184,.22);border-radius:16px;padding:14px">
        <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em">Expiring quotations</div>
        <div style="font-size:24px;font-weight:900;margin-top:6px"><?php echo number_format($k['expiring_soon']); ?></div>
        <div class="muted">Valid until within the next 7 days</div>
      </div>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div style="font-weight:950">Top requested products</div>
        <div class="muted">Based on RFQ line item demand</div>
      </div>
      <a class="link" href="<?php echo url('admin/products.php'); ?>">Manage products</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>Product</th><th style="text-align:right">Qty</th><th style="text-align:right">RFQs</th></tr></thead>
        <tbody>
          <?php foreach ($topRequested as $product): ?>
            <tr>
              <td>
                <a href="<?php echo url('admin/products-edit.php?id='.(int)$product['id']); ?>"><?php echo e($product['name'] ?: 'Unlinked product'); ?></a><br>
                <span class="muted" style="font-size:12px"><?php echo e(($product['brand'] ?: '—') . ' · ' . ($product['sku'] ?: 'No SKU')); ?></span>
              </td>
              <td style="text-align:right"><?php echo number_format((int)$product['requested_qty']); ?></td>
              <td style="text-align:right"><?php echo number_format((int)$product['rfq_count']); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$topRequested): ?>
            <tr><td colspan="3" class="muted">No RFQ product demand data yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div style="font-weight:950">Company account activity</div>
        <div class="muted">Most active B2B customer accounts</div>
      </div>
      <a class="link" href="<?php echo url('admin/company-accounts.php'); ?>">Open accounts</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>Account</th><th style="text-align:right">Contacts</th><th style="text-align:right">RFQs</th></tr></thead>
        <tbody>
          <?php foreach ($companyActivity as $account): ?>
            <tr>
              <td>
                <strong><?php echo e($account['company_name']); ?></strong><br>
                <span class="muted" style="font-size:12px"><?php echo e($account['account_code'] ?: 'No code'); ?><?php echo !empty($account['last_rfq_at']) ? ' · Last RFQ '.e(date('Y-m-d', strtotime($account['last_rfq_at']))) : ''; ?></span>
              </td>
              <td style="text-align:right"><?php echo number_format((int)$account['contact_count']); ?></td>
              <td style="text-align:right"><?php echo number_format((int)$account['rfq_count']); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$companyActivity): ?>
            <tr><td colspan="3" class="muted">No company account activity yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div style="font-weight:950">Recent inquiries</div>
        <div class="muted">Latest customer questions and follow-ups</div>
      </div>
      <a class="link" href="<?php echo url('admin/inquiries.php'); ?>">Open inquiries</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>Subject</th><th>Status</th><th>When</th></tr></thead>
        <tbody>
          <?php foreach ($recentInq as $inq): ?>
            <tr>
              <td><a href="<?php echo url('admin/inquiry-view.php?id='.(int)$inq['id']); ?>"><?php echo e($inq['subject']); ?></a><br><span class="muted" style="font-size:12px"><?php echo e($inq['company'] ?: $inq['name']); ?></span></td>
              <td><span class="badge"><?php echo e($inq['status']); ?></span></td>
              <td class="muted"><?php echo e(date('Y-m-d', strtotime($inq['created_at']))); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentInq): ?>
            <tr><td colspan="3" class="muted">No inquiries yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div style="font-weight:950">Stock risk watchlist</div>
        <div class="muted">Products closest to quotation delays</div>
      </div>
      <a class="link" href="<?php echo url('admin/products.php?stock=low'); ?>">Open products</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>Product</th><th style="text-align:right">Stock</th></tr></thead>
        <tbody>
          <?php foreach ($stockRisks as $product): ?>
            <tr>
              <td>
                <a href="<?php echo url('admin/products-edit.php?id='.(int)$product['id']); ?>"><?php echo e($product['name']); ?></a><br>
                <span class="muted" style="font-size:12px"><?php echo e(($product['brand'] ?: '—') . ' · ' . ($product['sku'] ?: 'No SKU')); ?></span>
              </td>
              <td style="text-align:right"><strong><?php echo number_format((int)$product['stock']); ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$stockRisks): ?>
            <tr><td colspan="2" class="muted">No low stock products right now.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
