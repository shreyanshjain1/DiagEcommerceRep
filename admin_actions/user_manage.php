<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate(url('admin/users.php'));

$action = (string)($_POST['action'] ?? '');
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    redirect_with_query('admin/users.php', ['err' => 'invalid_user']);
}

$stmt = $pdo->prepare("SELECT id,name,email,role,is_active,last_login_at,created_at FROM users WHERE id=:id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    redirect_with_query('admin/users.php', ['err' => 'not_found']);
}

$currentAdminId = current_user_id();

if ($action === 'save_user') {
    $role = (string)($_POST['role'] ?? 'customer');
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    if (!in_array($role, ['customer', 'admin'], true)) {
        redirect_with_query('admin/users.php', ['err' => 'invalid_role']);
    }
    if ($currentAdminId === (int)$user['id'] && $isActive !== 1) {
        redirect_with_query('admin/users.php', ['err' => 'self_deactivate']);
    }
    if ($currentAdminId === (int)$user['id'] && $role !== 'admin') {
        redirect_with_query('admin/users.php', ['err' => 'self_demote']);
    }

    $pdo->prepare("UPDATE users SET role=:role, is_active=:is_active, updated_at=NOW() WHERE id=:id LIMIT 1")
        ->execute([':role' => $role, ':is_active' => $isActive, ':id' => $userId]);

    $after = $user;
    $after['role'] = $role;
    $after['is_active'] = $isActive;
    audit_log($pdo, 'user', $userId, 'admin_user_updated', $user, $after, ['source' => 'admin/users.php']);

    redirect_with_query('admin/users.php', ['ok' => 'saved']);
}

if ($action === 'reset_password') {
    if ($currentAdminId === (int)$user['id']) {
        redirect_with_query('admin/users.php', ['err' => 'self_reset_use_profile']);
    }

    $tempPassword = admin_temp_password(12);
    $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash=:password_hash, updated_at=NOW() WHERE id=:id LIMIT 1")
        ->execute([':password_hash' => $hash, ':id' => $userId]);

    audit_log($pdo, 'user', $userId, 'admin_password_reset', ['email' => $user['email']], ['email' => $user['email']], ['source' => 'admin/users.php']);

    $_SESSION['admin_users_temp_password'] = [
        'user_id' => $userId,
        'email' => (string)$user['email'],
        'password' => $tempPassword,
    ];

    redirect_with_query('admin/users.php', ['ok' => 'password_reset']);
}

redirect_with_query('admin/users.php', ['err' => 'unknown_action']);
