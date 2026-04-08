<?php require_once __DIR__ . '/header.php'; require_once __DIR__ . '/../config/csrf.php';

$users = $pdo->query("SELECT id,name,email,phone,company,role,is_active,last_login_at,created_at FROM users ORDER BY created_at DESC LIMIT 300")->fetchAll();
$ok = (string)($_GET['ok'] ?? '');
$err = (string)($_GET['err'] ?? '');
$tempReset = $_SESSION['admin_users_temp_password'] ?? null;
unset($_SESSION['admin_users_temp_password']);

$messages = [
  'saved' => ['success', 'User access updated.'],
  'password_reset' => ['success', 'Temporary password generated below. Share it securely with the user.'],
  'invalid_user' => ['error', 'Invalid user selected.'],
  'not_found' => ['error', 'User not found.'],
  'invalid_role' => ['error', 'Invalid role selected.'],
  'self_deactivate' => ['error', 'You cannot deactivate your own admin account.'],
  'self_demote' => ['error', 'You cannot remove your own admin role.'],
  'self_reset_use_profile' => ['error', 'Do not reset your own password from here while logged in.'],
  'unknown_action' => ['error', 'Unknown user action.'],
];
?>
<h1>Users</h1>
<div class="muted" style="margin-bottom:12px">This screen now supports role changes, account activation control, and temporary password resets directly from the admin UI.</div>
<?php if ($ok && isset($messages[$ok])): [$type, $message] = $messages[$ok]; ?>
  <div class="alert <?php echo $type; ?>" style="margin-bottom:12px"><?php echo e($message); ?></div>
<?php endif; ?>
<?php if ($err && isset($messages[$err])): [$type, $message] = $messages[$err]; ?>
  <div class="alert <?php echo $type; ?>" style="margin-bottom:12px"><?php echo e($message); ?></div>
<?php endif; ?>
<?php if (is_array($tempReset) && !empty($tempReset['password'])): ?>
  <div class="alert success" style="margin-bottom:14px;padding:14px;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:12px">
    <strong>Temporary password generated</strong><br>
    User: <?php echo e((string)($tempReset['email'] ?? '')); ?><br>
    Password: <code><?php echo e((string)($tempReset['password'] ?? '')); ?></code>
  </div>
<?php endif; ?>
<div style="overflow:auto">
<table class="table">
  <tr><th>User</th><th>Contact</th><th>Access</th><th>Last Login</th><th>Joined</th><th>Actions</th></tr>
  <?php foreach($users as $u): ?>
    <tr>
      <td>
        <strong><?php echo e($u['name']); ?></strong><br>
        <span class="muted">ID #<?php echo (int)$u['id']; ?></span>
        <?php if (!empty($u['company'])): ?><br><span class="muted"><?php echo e($u['company']); ?></span><?php endif; ?>
      </td>
      <td>
        <?php echo e($u['email']); ?><br>
        <span class="muted"><?php echo e($u['phone'] ?: 'No phone'); ?></span>
      </td>
      <td>
        <form method="post" action="<?php echo url('admin_actions/user_manage.php'); ?>" style="display:grid;gap:8px;min-width:190px">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="save_user">
          <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
          <label>
            <span class="muted">Role</span><br>
            <select name="role">
              <option value="customer" <?php echo $u['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
              <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
          </label>
          <label>
            <span class="muted">Status</span><br>
            <select name="is_active">
              <option value="1" <?php echo (int)$u['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
              <option value="0" <?php echo (int)$u['is_active'] !== 1 ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </label>
          <button class="btn btn-sm" type="submit">Save Access</button>
        </form>
      </td>
      <td><?php echo e($u['last_login_at'] ?: 'Never'); ?></td>
      <td><?php echo e($u['created_at']); ?></td>
      <td>
        <form method="post" action="<?php echo url('admin_actions/user_manage.php'); ?>" onsubmit="return confirm('Generate a temporary password for this user?');" style="display:inline-block">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
          <button class="btn btn-outline btn-sm" type="submit">Reset Password</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
