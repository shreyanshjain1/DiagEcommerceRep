<?php
require_once __DIR__.'/header.php';

$status = (string)($_GET['status'] ?? 'submitted');
$allowed = ['submitted','draft','quoted','closed','all'];
if (!in_array($status, $allowed, true)) $status = 'submitted';

$q = trim((string)($_GET['q'] ?? ''));
$page = page_param();
$perPage = 20;

$where = [];
$params = [];
if ($status !== 'all') {
  $where[] = 'q.status = :st';
  $params[':st'] = $status;
}
if ($q !== '') {
  $where[] = '(q.quote_number LIKE :q OR q.name LIKE :q OR q.company LIKE :q OR q.email LIKE :q OR q.phone LIKE :q)';
  $params[':q'] = '%' . $q . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM quotes q $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pager = pagination_meta($total, $perPage, $page);

$sql = "SELECT q.id,q.quote_number,q.status,q.total,q.name,q.company,q.email,q.phone,q.created_at,
               (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id=q.id) AS item_count
        FROM quotes q
        $whereSql
        ORDER BY q.updated_at DESC, q.id DESC
        LIMIT :limit OFFSET :offset";
$st = $pdo->prepare($sql);
foreach ($params as $k => $v) $st->bindValue($k, $v);
$st->bindValue(':limit', $pager['per_page'], PDO::PARAM_INT);
$st->bindValue(':offset', $pager['offset'], PDO::PARAM_INT);
$st->execute();
$rfqs = $st->fetchAll();
?>

<h1>RFQs</h1>
<div class="toolbar" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:end;margin-bottom:12px">
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php
      $tabs = ['submitted'=>'Submitted','quoted'=>'Quoted','closed'=>'Closed','draft'=>'Drafts','all'=>'All'];
      foreach($tabs as $k=>$label){
        $active = ($status===$k) ? 'btn' : 'btn secondary';
        echo '<a class="'.$active.'" href="'.url('admin/rfqs.php?status='.$k).'">'.e($label).'</a>';
      }
    ?>
  </div>
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="status" value="<?php echo e($status); ?>">
    <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search RFQ #, customer, company, email">
    <button class="btn secondary" type="submit">Filter</button>
    <?php if ($q !== ''): ?><a class="btn secondary" href="<?php echo url('admin/rfqs.php?status='.$status); ?>">Clear</a><?php endif; ?>
  </form>
</div>

<div class="muted" style="margin-bottom:12px">
  Showing <?php echo (int)$pager['from']; ?>–<?php echo (int)$pager['to']; ?> of <?php echo (int)$pager['total']; ?> RFQs
</div>

<table class="table">
  <tr>
    <th>RFQ #</th>
    <th>Status</th>
    <th>Customer</th>
    <th>Items</th>
    <th>Total</th>
    <th>Date</th>
    <th></th>
  </tr>
  <?php foreach($rfqs as $r): ?>
    <tr>
      <td><strong><?php echo e($r['quote_number']); ?></strong></td>
      <td><span class="tag"><?php echo e($r['status']); ?></span></td>
      <td>
        <?php echo e($r['name']); ?>
        <?php if(!empty($r['company'])): ?><div class="muted"><?php echo e($r['company']); ?></div><?php endif; ?>
        <div class="muted"><?php echo e($r['email']); ?><?php if($r['phone']): ?> • <?php echo e($r['phone']); ?><?php endif; ?></div>
      </td>
      <td><?php echo (int)$r['item_count']; ?></td>
      <td>₱<?php echo number_format((float)$r['total'],2); ?></td>
      <td><?php echo e($r['created_at']); ?></td>
      <td><a class="btn" href="<?php echo url('admin/rfq-view.php?id='.$r['id']); ?>">Open</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rfqs): ?>
    <tr><td colspan="7" class="muted">No RFQs matched the current filter.</td></tr>
  <?php endif; ?>
</table>

<?php echo pagination_links($pager); ?>

<?php require_once __DIR__.'/footer.php'; ?>
