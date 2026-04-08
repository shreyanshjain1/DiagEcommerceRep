<?php require_once __DIR__.'/header.php'; require_once __DIR__.'/../config/csrf.php';

$status = $_GET['status'] ?? '';
$where = '';
$args = [];
if (in_array($status, ['New','In Progress','Closed'], true)) {
  $where = "WHERE status=:s";
  $args[':s'] = $status;
}
$sql = "SELECT id,created_at,company,name,email,phone,subject,status FROM inquiries $where ORDER BY created_at DESC LIMIT 500";
$stmt = $pdo->prepare($sql); $stmt->execute($args);
$rows = $stmt->fetchAll();
?>
<h1>Inquiries</h1>
<div class="toolbar">
  <div>
    <a class="btn ghost" href="<?php echo url('admin/inquiries.php'); ?>">All</a>
    <a class="btn ghost" href="<?php echo url('admin/inquiries.php?status=New'); ?>">New</a>
    <a class="btn ghost" href="<?php echo url('admin/inquiries.php?status=In+Progress'); ?>">In Progress</a>
    <a class="btn ghost" href="<?php echo url('admin/inquiries.php?status=Closed'); ?>">Closed</a>
  </div>
</div>

<table class="table">
  <tr><th>Date</th><th>From</th><th>Contact</th><th>Subject</th><th>Status</th><th></th></tr>
  <?php foreach($rows as $q): ?>
    <tr>
      <td><?php echo e($q['created_at']); ?></td>
      <td><?php echo e($q['company'] ?: '—'); ?><br><?php echo e($q['name']); ?></td>
      <td><?php echo e($q['email']); ?><br><?php echo e($q['phone'] ?: '—'); ?></td>
      <td><?php echo e($q['subject']); ?></td>
      <td><?php echo e($q['status']); ?></td>
      <td>
        <a class="btn" href="<?php echo url('admin/inquiry-view.php?id='.$q['id']); ?>">Open</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require_once __DIR__.'/footer.php'; ?>
