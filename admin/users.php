<?php require_once __DIR__.'/header.php';

$users = $pdo->query("SELECT id,name,email,phone,role,created_at FROM users ORDER BY created_at DESC LIMIT 300")->fetchAll();
?>
<h1>Users</h1>
<table class="table">
  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th></tr>
  <?php foreach($users as $u): ?>
    <tr>
      <td><?php echo e($u['name']); ?></td>
      <td><?php echo e($u['email']); ?></td>
      <td><?php echo e($u['phone']); ?></td>
      <td><span class="tag"><?php echo e($u['role']); ?></span></td>
      <td><?php echo e($u['created_at']); ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<p style="color:#6b7280;margin-top:10px">
  To promote a user to admin, change their role in the database:<br>
  <code>UPDATE users SET role='admin' WHERE email='user@example.com';</code>
</p>
<?php require_once __DIR__.'/footer.php'; ?>
