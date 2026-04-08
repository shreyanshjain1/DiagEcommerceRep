<?php
require_once __DIR__ . '/header.php';

$sql = "SELECT u.id, u.name, u.email, u.phone, u.role, u.company_contact_role, u.created_at,
               ca.company_name, ca.account_code, ca.account_status,
               (SELECT COUNT(*) FROM quotes q WHERE q.user_id = u.id) AS quote_count
        FROM users u
        LEFT JOIN company_accounts ca ON ca.id = u.company_account_id
        ORDER BY u.created_at DESC
        LIMIT 300";
$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Users</h1>
<p style="color:#6b7280;margin-top:-4px">Recruiter-facing upgrade: users are now structured under company accounts with multi-contact roles.</p>
<table class="table">
  <tr>
    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Platform Role</th>
    <th>Company Account</th>
    <th>Contact Role</th>
    <th>Account Status</th>
    <th>RFQs</th>
    <th>Joined</th>
  </tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?php echo e($u['name']); ?></td>
      <td><?php echo e($u['email']); ?></td>
      <td><?php echo e($u['phone']); ?></td>
      <td><span class="tag"><?php echo e($u['role']); ?></span></td>
      <td>
        <?php if (!empty($u['company_name'])): ?>
          <strong><?php echo e($u['company_name']); ?></strong><br>
          <small style="color:#6b7280"><?php echo e($u['account_code']); ?></small>
        <?php else: ?>
          <span style="color:#9ca3af">No company account</span>
        <?php endif; ?>
      </td>
      <td><?php echo e(ucfirst((string)($u['company_contact_role'] ?? 'viewer'))); ?></td>
      <td><?php echo e(!empty($u['account_status']) ? ucfirst(str_replace('_', ' ', (string)$u['account_status'])) : '—'); ?></td>
      <td><?php echo e((string)$u['quote_count']); ?></td>
      <td><?php echo e($u['created_at']); ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<p style="color:#6b7280;margin-top:10px">
  This structure supports B2B customer accounts with multiple company contacts instead of treating every buyer as a standalone record.
</p>
<?php require_once __DIR__ . '/footer.php'; ?>
