<?php
require_once __DIR__.'/header.php';

$status = $_GET['status'] ?? 'submitted';
$allowed = ['submitted','draft','quoted','closed','all'];
if (!in_array($status,$allowed,true)) $status = 'submitted';

$where = '';
$params = [];
if ($status !== 'all') {
  $where = 'WHERE q.status = :st';
  $params[':st'] = $status;
}

$sql = "SELECT q.id,q.quote_number,q.status,q.total,q.name,q.company,q.email,q.phone,q.created_at,
               (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id=q.id) AS item_count
        FROM quotes q
        $where
        ORDER BY q.updated_at DESC
        LIMIT 500";
$st = $pdo->prepare($sql);
$st->execute($params);
$rfqs = $st->fetchAll();

$counts = ['draft'=>0,'submitted'=>0,'quoted'=>0,'closed'=>0];
foreach ($rfqs as $row) {
  if (isset($counts[$row['status']])) $counts[$row['status']]++;
}
$totalValue = 0;
foreach ($rfqs as $row) $totalValue += (float)$row['total'];
function rfq_badge_class(string $status): string {
  return match($status) {
    'closed' => 'badge-ok',
    'quoted' => 'badge',
    'submitted' => 'badge-warn',
    default => 'badge-bad',
  };
}
?>

<div class="admin-page-head">
  <div>
    <h1 class="admin-page-title">RFQ workspace</h1>
    <p class="admin-page-sub">Review incoming quote requests, track pipeline movement, and jump into the next action fast.</p>
  </div>
  <div class="admin-page-actions">
    <span class="admin-chip success"><?php echo count($rfqs); ?> visible records</span>
    <span class="admin-chip muted">Shown from newest activity first</span>
  </div>
</div>

<div class="admin-stats-grid admin-stats-grid-tight">
  <div class="admin-stat-card"><div class="admin-stat-label">Submitted</div><div class="admin-stat-value"><?php echo (int)$counts['submitted']; ?></div><div class="admin-stat-meta">Waiting for pricing or review</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Quoted</div><div class="admin-stat-value"><?php echo (int)$counts['quoted']; ?></div><div class="admin-stat-meta">Commercial response already sent</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Closed</div><div class="admin-stat-value"><?php echo (int)$counts['closed']; ?></div><div class="admin-stat-meta">Completed or archived workflow</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Visible value</div><div class="admin-stat-value">₱<?php echo number_format($totalValue,0); ?></div><div class="admin-stat-meta">Combined visible quote total</div></div>
</div>

<div class="admin-filter-bar">
  <div class="admin-filter-tabs">
    <?php
      $tabs = ['submitted'=>'Submitted','quoted'=>'Quoted','closed'=>'Closed','draft'=>'Drafts','all'=>'All'];
      foreach($tabs as $k=>$label){
        $cls = ($status===$k) ? 'admin-tab active' : 'admin-tab';
        echo '<a class="'.$cls.'" href="'.url('admin/rfqs.php?status='.$k).'">'.e($label).'</a>';
      }
    ?>
  </div>
</div>

<div class="admin-table-shell">
  <div class="admin-table-headline">
    <div>
      <h2 class="admin-panel-title">Request queue</h2>
      <p class="admin-panel-sub">Cleaner operations view for recruiters and future internal reviewers.</p>
    </div>
  </div>
  <table class="table table-premium">
    <tr>
      <th>RFQ</th>
      <th>Customer</th>
      <th>Status</th>
      <th>Items</th>
      <th>Total</th>
      <th>Created</th>
      <th></th>
    </tr>
    <?php foreach($rfqs as $r): ?>
      <tr>
        <td>
          <div class="admin-table-primary"><?php echo e($r['quote_number']); ?></div>
          <div class="admin-table-meta">Request record</div>
        </td>
        <td>
          <div class="admin-table-primary"><?php echo e($r['name']); ?></div>
          <?php if(!empty($r['company'])): ?><div class="admin-table-meta"><?php echo e($r['company']); ?></div><?php endif; ?>
          <div class="admin-table-meta"><?php echo e($r['email']); ?><?php if($r['phone']): ?> • <?php echo e($r['phone']); ?><?php endif; ?></div>
        </td>
        <td><span class="badge <?php echo rfq_badge_class((string)$r['status']); ?>"><?php echo e(ucfirst($r['status'])); ?></span></td>
        <td><span class="admin-metric-pill"><?php echo (int)$r['item_count']; ?> items</span></td>
        <td><strong>₱<?php echo number_format((float)$r['total'],2); ?></strong></td>
        <td>
          <div class="admin-table-primary"><?php echo e(date('M d, Y', strtotime($r['created_at']))); ?></div>
          <div class="admin-table-meta"><?php echo e(date('g:i A', strtotime($r['created_at']))); ?></div>
        </td>
        <td class="admin-table-actions-cell"><a class="btn" href="<?php echo url('admin/rfq-view.php?id='.$r['id']); ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
