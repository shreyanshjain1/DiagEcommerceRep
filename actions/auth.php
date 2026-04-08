<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if($_SERVER['REQUEST_METHOD']==='POST'){ csrf_validate(); }

if($action==='signup' && $_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $password = $_POST['password'] ?? '';
    $terms = isset($_POST['terms']);
    if(!$email || strlen($password)<8 || !$terms){ die('Invalid input'); }
    $exists = $pdo->prepare("SELECT id FROM users WHERE email=:e");
    $exists->execute([':e'=>$email]);
    if($exists->fetch()){ die('Email already exists'); }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,phone,company,role,created_at) VALUES(:n,:e,:h,:p,:c,'customer',NOW())");
    $stmt->execute([':n'=>$name,':e'=>$email,':h'=>$hash,':p'=>$phone,':c'=>$company]);
    $_SESSION['user_id']=$pdo->lastInsertId();
    $_SESSION['role']='customer';
    header('Location: '.url('index.php')); exit;
}

if($action==='login' && $_SERVER['REQUEST_METHOD']==='POST'){
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    if(!$email || strlen($password)<8){ die('Invalid credentials'); }
    $stmt = $pdo->prepare("SELECT id,password_hash,role FROM users WHERE email=:e");
    $stmt->execute([':e'=>$email]);
    $u=$stmt->fetch();
    if(!$u || !password_verify($password,$u['password_hash'])){ die('Invalid credentials'); }
    $_SESSION['user_id']=$u['id'];
    $_SESSION['role']=$u['role'] ?? 'customer';
    header('Location: '.url('index.php')); exit;
}

if($action==='logout'){
    session_destroy();
    header('Location: '.url('index.php')); exit;
}

http_response_code(400);
echo 'Bad request';
