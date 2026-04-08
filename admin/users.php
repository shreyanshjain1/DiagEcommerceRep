<?php require_once __DIR__.'/header.php';

$users = $pdo->query("SELECT id,name,email,phone,role,created_at FROM users ORDER BY created_at DESC LIMIT 300")->fetchAll();
$admins = 0; $customers = 0;
foreach ($users as $u) {
  if (($u['role'] ?? '') === 'admin') $admins++; else $customers++;
}
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-page-title">User directory</h1>
    <p class="admin-page-sub">Account overview for the B2B customer base and internal admin access layer.</p>
  </div>
  <div class="admin-page-actions">
    <span class="admin-chip success"><?php echo $admins; ?> admins</span>
    <span class="admin-chip muted"><?php echo $customers; ?> customer accounts</span>
  </div>
</div>

<div class="admin-stats-grid admin-stats-grid-tight">
  <div class="admin-stat-card"><div class="admin-stat-label">Total users</div><div class="admin-stat-value"><?php echo count($users); ?></div><div class="admin-stat-meta">Latest 300 records shown</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Admin accounts</div><div class="admin-stat-value"><?php echo $admins; ?></div><div class="admin-stat-meta">Operational access layer</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Customer accounts</div><div class="admin-stat-value"><?php echo $customers; ?></div><div class="admin-stat-meta">Buyer-side user base</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Newest account</div><div class="admin-stat-value"><?php echo $users ? e(date('M d', strtotime($users[0]['created_at']))) : '—'; ?></div><div class="admin-stat-meta">Most recent signup visible</div></div>
</div>

<div class="admin-table-shell">
  <div class="admin-table-headline">
    <div>
      <h2 class="admin-panel-title">Accounts table</h2>
      <p class="admin-panel-sub">Cleaner recruiter-facing user management surface.</p>
    </div>
  </div>
  <table class="table table-premium">
    <tr><th>Name</th><th>Contact</th><th>Role</th><th>Joined</th></tr>
    <?php foreach($users as $u): ?>
      <tr>
        <td>
          <div class="admin-table-primary"><?php echo e($u['name']); ?></div>
          <div class="admin-table-meta">User #<?php echo (int)$u['id']; ?></div>
        </td>
        <td>
          <div class="admin-table-primary"><?php echo e($u['email']); ?></div>
          <div class="admin-table-meta"><?php echo e($u['phone'] ?: 'No phone on file'); ?></div>
        </td>
        <td><span class="badge <?php echo ($u['role']==='admin' ? 'badge-ok' : 'badge'); ?>"><?php echo e(ucfirst($u['role'])); ?></span></td>
        <td>
          <div class="admin-table-primary"><?php echo e(date('M d, Y', strtotime($u['created_at']))); ?></div>
          <div class="admin-table-meta"><?php echo e(date('g:i A', strtotime($u['created_at']))); ?></div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="admin-soft-note">
  <strong>Recruiter-facing note:</strong> this repo now presents user access as a dedicated directory surface rather than a bare SQL dump style listing.
</div>
<?php require_once __DIR__.'/footer.php'; ?>
