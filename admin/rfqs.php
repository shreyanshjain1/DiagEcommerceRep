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
?>

<h1>RFQs</h1>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">
  <?php
    $tabs = ['submitted'=>'Submitted','quoted'=>'Quoted','closed'=>'Closed','draft'=>'Drafts','all'=>'All'];
    foreach($tabs as $k=>$label){
      $active = ($status===$k) ? 'btn' : 'btn secondary';
      echo '<a class="'.$active.'" href="'.url('admin/rfqs.php?status='.$k).'">'.e($label).'</a>';
    }
  ?>
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
</table>

<?php require_once __DIR__.'/footer.php'; ?>
