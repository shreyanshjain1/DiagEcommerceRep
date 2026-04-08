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
$newCount = 0; $progCount = 0; $closedCount = 0;
foreach ($rows as $r) {
  if ($r['status'] === 'New') $newCount++;
  elseif ($r['status'] === 'In Progress') $progCount++;
  elseif ($r['status'] === 'Closed') $closedCount++;
}
function inquiry_badge(string $status): string {
  return match($status) {
    'Closed' => 'badge-ok',
    'In Progress' => 'badge-warn',
    default => 'badge-bad',
  };
}
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-page-title">Inquiry desk</h1>
    <p class="admin-page-sub">Track inbound company and customer requests with a cleaner queue-style admin experience.</p>
  </div>
  <div class="admin-page-actions">
    <span class="admin-chip success"><?php echo count($rows); ?> visible inquiries</span>
  </div>
</div>

<div class="admin-stats-grid admin-stats-grid-tight">
  <div class="admin-stat-card"><div class="admin-stat-label">New</div><div class="admin-stat-value"><?php echo $newCount; ?></div><div class="admin-stat-meta">Fresh requests needing first touch</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">In progress</div><div class="admin-stat-value"><?php echo $progCount; ?></div><div class="admin-stat-meta">Currently being handled</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Closed</div><div class="admin-stat-value"><?php echo $closedCount; ?></div><div class="admin-stat-meta">Resolved or archived items</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Filter</div><div class="admin-stat-value"><?php echo $status ?: 'All'; ?></div><div class="admin-stat-meta">Current queue view</div></div>
</div>

<div class="admin-filter-bar">
  <div class="admin-filter-tabs">
    <a class="admin-tab <?php echo $status==='' ? 'active' : ''; ?>" href="<?php echo url('admin/inquiries.php'); ?>">All</a>
    <a class="admin-tab <?php echo $status==='New' ? 'active' : ''; ?>" href="<?php echo url('admin/inquiries.php?status=New'); ?>">New</a>
    <a class="admin-tab <?php echo $status==='In Progress' ? 'active' : ''; ?>" href="<?php echo url('admin/inquiries.php?status=In+Progress'); ?>">In Progress</a>
    <a class="admin-tab <?php echo $status==='Closed' ? 'active' : ''; ?>" href="<?php echo url('admin/inquiries.php?status=Closed'); ?>">Closed</a>
  </div>
</div>

<div class="admin-table-shell">
  <div class="admin-table-headline">
    <div>
      <h2 class="admin-panel-title">Inquiry queue</h2>
      <p class="admin-panel-sub">A polished support and sales-intake view for the repo surface.</p>
    </div>
  </div>
  <table class="table table-premium">
    <tr><th>Date</th><th>From</th><th>Contact</th><th>Subject</th><th>Status</th><th></th></tr>
    <?php foreach($rows as $q): ?>
      <tr>
        <td>
          <div class="admin-table-primary"><?php echo e(date('M d, Y', strtotime($q['created_at']))); ?></div>
          <div class="admin-table-meta"><?php echo e(date('g:i A', strtotime($q['created_at']))); ?></div>
        </td>
        <td>
          <div class="admin-table-primary"><?php echo e($q['company'] ?: '—'); ?></div>
          <div class="admin-table-meta"><?php echo e($q['name']); ?></div>
        </td>
        <td>
          <div class="admin-table-primary"><?php echo e($q['email']); ?></div>
          <div class="admin-table-meta"><?php echo e($q['phone'] ?: 'No phone on file'); ?></div>
        </td>
        <td>
          <div class="admin-table-primary"><?php echo e($q['subject']); ?></div>
          <div class="admin-table-meta">Inquiry #<?php echo (int)$q['id']; ?></div>
        </td>
        <td><span class="badge <?php echo inquiry_badge((string)$q['status']); ?>"><?php echo e($q['status']); ?></span></td>
        <td class="admin-table-actions-cell"><a class="btn" href="<?php echo url('admin/inquiry-view.php?id='.$q['id']); ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__.'/footer.php'; ?>
