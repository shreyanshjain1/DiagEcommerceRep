<?php require_once __DIR__.'/header.php'; require_once __DIR__.'/../config/csrf.php';

$status = (string)($_GET['status'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));
$page = page_param();
$perPage = 20;

$where = [];
$args = [];
if (in_array($status, ['New','In Progress','Closed'], true)) {
  $where[] = 'status = :s';
  $args[':s'] = $status;
} else {
  $status = '';
}
if ($q !== '') {
  $where[] = '(company LIKE :q OR name LIKE :q OR email LIKE :q OR phone LIKE :q OR subject LIKE :q)';
  $args[':q'] = '%' . $q . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries $whereSql");
$countStmt->execute($args);
$total = (int)$countStmt->fetchColumn();
$pager = pagination_meta($total, $perPage, $page);

$sql = "SELECT id,created_at,company,name,email,phone,subject,status FROM inquiries $whereSql ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($args as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $pager['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pager['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<h1>Inquiries</h1>
<div class="toolbar" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:end;margin-bottom:12px">
  <div>
    <a class="btn <?php echo $status === '' ? '' : 'ghost'; ?>" href="<?php echo url('admin/inquiries.php'); ?>">All</a>
    <a class="btn <?php echo $status === 'New' ? '' : 'ghost'; ?>" href="<?php echo url('admin/inquiries.php?status=New'); ?>">New</a>
    <a class="btn <?php echo $status === 'In Progress' ? '' : 'ghost'; ?>" href="<?php echo url('admin/inquiries.php?status=In+Progress'); ?>">In Progress</a>
    <a class="btn <?php echo $status === 'Closed' ? '' : 'ghost'; ?>" href="<?php echo url('admin/inquiries.php?status=Closed'); ?>">Closed</a>
  </div>
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?php echo e($status); ?>"><?php endif; ?>
    <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search company, contact, subject">
    <button class="btn secondary" type="submit">Filter</button>
    <a class="btn secondary" href="<?php echo url('admin/inquiries.php'); ?>">Clear</a>
  </form>
</div>

<div class="muted" style="margin-bottom:12px">Showing <?php echo (int)$pager['from']; ?>–<?php echo (int)$pager['to']; ?> of <?php echo (int)$pager['total']; ?> inquiries</div>

<table class="table">
  <tr><th>Date</th><th>From</th><th>Contact</th><th>Subject</th><th>Status</th><th></th></tr>
  <?php foreach($rows as $qrow): ?>
    <tr>
      <td><?php echo e($qrow['created_at']); ?></td>
      <td><?php echo e($qrow['company'] ?: '—'); ?><br><?php echo e($qrow['name']); ?></td>
      <td><?php echo e($qrow['email']); ?><br><?php echo e($qrow['phone'] ?: '—'); ?></td>
      <td><?php echo e($qrow['subject']); ?></td>
      <td><?php echo e($qrow['status']); ?></td>
      <td>
        <a class="btn" href="<?php echo url('admin/inquiry-view.php?id='.$qrow['id']); ?>">Open</a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?>
    <tr><td colspan="6" class="muted">No inquiries matched the current filter.</td></tr>
  <?php endif; ?>
</table>
<?php echo pagination_links($pager); ?>
<?php require_once __DIR__.'/footer.php'; ?>
