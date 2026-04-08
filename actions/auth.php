<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
}

function auth_redirect(string $path, string $type, string $message): never {
    $_SESSION['flash_' . $type] = $message;
    header('Location: ' . url($path));
    exit;
}

function finalize_login(array $user): never {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = (string)($user['role'] ?? 'customer');
    $_SESSION['_session_regenerated_at'] = time();
    unset($_SESSION['auth_login_attempts'], $_SESSION['auth_last_attempt_at']);
    header('Location: ' . url('index.php'));
    exit;
}

if ($action === 'signup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $password = $_POST['password'] ?? '';
    $terms = isset($_POST['terms']);

    if (!$email || strlen($password) < 8 || !$terms) {
        auth_redirect('pages/signup.php', 'error', 'Please complete all required fields correctly.');
    }

    $exists = $pdo->prepare("SELECT id FROM users WHERE email=:e LIMIT 1");
    $exists->execute([':e' => $email]);
    if ($exists->fetch()) {
        auth_redirect('pages/signup.php', 'error', 'Email already exists. Please log in instead.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,phone,company,role,created_at) VALUES(:n,:e,:h,:p,:c,'customer',NOW())");
    $stmt->execute([':n' => $name, ':e' => $email, ':h' => $hash, ':p' => $phone, ':c' => $company]);

    finalize_login([
        'id' => $pdo->lastInsertId(),
        'role' => 'customer',
    ]);
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || strlen($password) < 8) {
        auth_redirect('pages/login.php', 'error', 'Invalid credentials.');
    }

    $stmt = $pdo->prepare("SELECT id,password_hash,role FROM users WHERE email=:e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($password, $u['password_hash'])) {
        $_SESSION['auth_login_attempts'] = (int)($_SESSION['auth_login_attempts'] ?? 0) + 1;
        $_SESSION['auth_last_attempt_at'] = time();
        auth_redirect('pages/login.php', 'error', 'Invalid credentials.');
    }

    finalize_login($u);
}

if ($action === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool)($params['secure'] ?? false), (bool)($params['httponly'] ?? true));
    }

    session_destroy();
    header('Location: ' . url('index.php'));
    exit;
}

http_response_code(400);
echo 'Bad request';
