<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/settings.php';

$uid = current_user_id();
if(!$uid){
  header('Location: ' . url('pages/login.php'));
  exit;
}

$msg = (string)($_GET['msg'] ?? '');
if ($msg === 'submitted') $flash = 'RFQ submitted successfully. Our admin team will contact you soon.';
elseif ($msg === 'draft') $flash = 'RFQ draft saved. You can submit it later.';
else $flash = '';

$status = trim((string)($_GET['status'] ?? ''));
$keyword = trim((string)($_GET['q'] ?? ''));
$allowedStatuses = ['draft','submitted','quoted','closed'];
if (!in_array($status, $allowedStatuses, true)) {
  $status = '';
}

$where = ["q.user_id = :u"];
$params = [':u' => $uid];
if ($status !== '') {
  $where[] = "q.status = :status";
  $params[':status'] = $status;
}
if ($keyword !== '') {
  $where[] = "(q.quote_number LIKE :kw OR q.company LIKE :kw OR q.email LIKE :kw OR q.phone LIKE :kw OR q.name LIKE :kw)";
  $params[':kw'] = '%' . $keyword . '%';
}
$whereSql = implode(' AND ', $where);

$summaryStmt = $pdo->prepare(
  "SELECT
      COUNT(*) AS total_rfqs,
      SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft_rfqs,
      SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) AS submitted_rfqs,
      SUM(CASE WHEN status='quoted' THEN 1 ELSE 0 END) AS quoted_rfqs,
      SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END) AS closed_rfqs,
      COALESCE(SUM(CASE WHEN status='quoted' THEN total ELSE 0 END),0) AS quoted_value
   FROM quotes
   WHERE user_id = :u"
);
$summaryStmt->execute([':u'=>$uid]);
$summary = $summaryStmt->fetch() ?: [];

$st = $pdo->prepare(
  "SELECT q.id, q.quote_number, q.status, q.created_at, q.updated_at, q.total, q.valid_until,
          COUNT(qi.id) AS line_count,
          COALESCE(SUM(qi.qty),0) AS unit_count
   FROM quotes q
   LEFT JOIN quote_items qi ON qi.quote_id = q.id
   WHERE $whereSql
   GROUP BY q.id
   ORDER BY q.updated_at DESC
   LIMIT 200"
);
$st->execute($params);
$rfqs = $st->fetchAll();

function status_link(string $statusValue, string $currentKeyword): string {
  $query = [];
  if ($statusValue !== '') $query['status'] = $statusValue;
  if ($currentKeyword !== '') $query['q'] = $currentKeyword;
  return url('pages/quotes.php' . ($query ? '?' . http_build_query($query) : ''));
}
?>

<div class="customer-page-head">
  <div>
    <span class="workspace-kicker">Quotation center</span>
    <h1>My RFQs</h1>
    <p>Track pricing progress, review active quotation requests, and reopen previous RFQs when needed.</p>
  </div>
  <div class="workspace-actions">
    <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse Products</a>
    <a class="btn secondary" href="<?php echo url('pages/profile.php'); ?>">Back to Workspace</a>
  </div>
</div>

<?php if($flash): ?><div class="alert success"><?php echo e($flash); ?></div><?php endif; ?>

<div class="workspace-stats-grid compact">
  <div class="workspace-stat-card compact"><div class="workspace-stat-label">All RFQs</div><div class="workspace-stat-value"><?php echo (int)($summary['total_rfqs'] ?? 0); ?></div></div>
  <div class="workspace-stat-card compact"><div class="workspace-stat-label">Submitted</div><div class="workspace-stat-value"><?php echo (int)($summary['submitted_rfqs'] ?? 0); ?></div></div>
  <div class="workspace-stat-card compact"><div class="workspace-stat-label">Quoted</div><div class="workspace-stat-value"><?php echo (int)($summary['quoted_rfqs'] ?? 0); ?></div></div>
  <div class="workspace-stat-card compact"><div class="workspace-stat-label">Quoted Value</div><div class="workspace-stat-value">₱<?php echo number_format((float)($summary['quoted_value'] ?? 0), 0); ?></div></div>
</div>

<section class="workspace-panel" style="margin-top:18px;">
  <div class="workspace-section-head">
    <div>
      <h2>Filter RFQs</h2>
      <p>Search by RFQ number, company, contact, email, or phone.</p>
    </div>
  </div>

  <form class="customer-filter-bar" method="get" action="<?php echo url('pages/quotes.php'); ?>">
    <input type="text" name="q" value="<?php echo e($keyword); ?>" placeholder="Search RFQ number, contact, email...">
    <select name="status">
      <option value="">All statuses</option>
      <?php foreach($allowedStatuses as $s): ?>
        <option value="<?php echo e($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo e(ucfirst($s)); ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Apply</button>
    <a class="btn ghost" href="<?php echo url('pages/quotes.php'); ?>">Reset</a>
  </form>

  <div class="workspace-filter-tabs">
    <a class="<?php echo $status === '' ? 'active' : ''; ?>" href="<?php echo e(status_link('', $keyword)); ?>">All</a>
    <a class="<?php echo $status === 'draft' ? 'active' : ''; ?>" href="<?php echo e(status_link('draft', $keyword)); ?>">Draft</a>
    <a class="<?php echo $status === 'submitted' ? 'active' : ''; ?>" href="<?php echo e(status_link('submitted', $keyword)); ?>">Submitted</a>
    <a class="<?php echo $status === 'quoted' ? 'active' : ''; ?>" href="<?php echo e(status_link('quoted', $keyword)); ?>">Quoted</a>
    <a class="<?php echo $status === 'closed' ? 'active' : ''; ?>" href="<?php echo e(status_link('closed', $keyword)); ?>">Closed</a>
  </div>

  <?php if(!$rfqs): ?>
    <div class="workspace-empty">
      <strong>No RFQs matched your filters.</strong>
      <p>Try another search or start a new request from the product catalog.</p>
      <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse products</a>
    </div>
  <?php else: ?>
    <div class="workspace-rfq-list dense">
      <?php foreach($rfqs as $r): ?>
        <article class="workspace-rfq-item customer-center-item">
          <div class="workspace-rfq-main">
            <div class="workspace-rfq-topline">
              <strong><?php echo e($r['quote_number']); ?></strong>
              <span class="status-chip status-<?php echo e($r['status']); ?>"><?php echo e(ucfirst($r['status'])); ?></span>
            </div>
            <div class="workspace-rfq-meta multi">
              <span><?php echo (int)$r['line_count']; ?> lines</span>
              <span><?php echo (int)$r['unit_count']; ?> units</span>
              <span>Created <?php echo e(date('M d, Y', strtotime((string)$r['created_at']))); ?></span>
              <span>Updated <?php echo e(date('M d, Y', strtotime((string)$r['updated_at']))); ?></span>
              <?php if(!empty($r['valid_until'])): ?><span>Valid until <?php echo e(date('M d, Y', strtotime((string)$r['valid_until']))); ?></span><?php endif; ?>
            </div>
          </div>
          <div class="workspace-rfq-side">
            <div class="workspace-rfq-total">₱<?php echo number_format((float)$r['total'], 2); ?></div>
            <div class="workspace-rfq-actions stack">
              <a class="btn secondary" href="<?php echo url('pages/rfq-view.php?id='.$r['id']); ?>">Open RFQ</a>
              <a class="btn ghost" href="<?php echo url('actions/quote.php?action=reorder&id='.$r['id']); ?>">Reorder</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
