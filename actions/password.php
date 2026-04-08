<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

$action = $_POST['action'] ?? '';
if($_SERVER['REQUEST_METHOD']==='POST'){ csrf_validate(); }

if($action==='request'){
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  if(!$email){ die('Invalid'); }
  $u = $pdo->prepare("SELECT id FROM users WHERE email=:e");
  $u->execute([':e'=>$email]);
  $row = $u->fetch();
  if($row){
    $token = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO password_resets(user_id,token,expires_at,created_at) VALUES(:u,:t,DATE_ADD(NOW(), INTERVAL 1 HOUR),NOW())")
        ->execute([':u'=>$row['id'], ':t'=>$token]);
    $link = url('pages/reset.php?token='.$token);
    @mail($email, $SITE_NAME.' Password Reset', "Click to reset your password:\n\n".$link."\n\nValid for 1 hour.");
  }
  echo 'If the email exists, a reset link has been sent.'; exit;
}

if($action==='reset'){
  $token = $_POST['token'] ?? '';
  $pass = $_POST['password'] ?? '';
  if(!$token || strlen($pass)<8){ die('Invalid'); }
  $sel = $pdo->prepare("SELECT pr.user_id FROM password_resets pr WHERE pr.token=:t AND pr.expires_at>NOW()");
  $sel->execute([':t'=>$token]);
  $row = $sel->fetch();
  if(!$row){ die('Token expired/invalid'); }
  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $pdo->beginTransaction();
  try{
    $pdo->prepare("UPDATE users SET password_hash=:h WHERE id=:id")->execute([':h'=>$hash, ':id'=>$row['user_id']]);
    $pdo->prepare("DELETE FROM password_resets WHERE user_id=:id")->execute([':id'=>$row['user_id']]);
    $pdo->commit();
  } catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    die('Failed');
  }
  echo 'Password updated. <a href="'.url('pages/login.php').'">Login</a>'; exit;
}

http_response_code(400);
echo 'Bad request';
