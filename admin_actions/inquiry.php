<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

require_admin();
ensure_post(); csrf_validate();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if(!$id || !in_array($status, ['New','In Progress','Closed'], true)){
  die('Invalid');
}

$beforeStmt = $pdo->prepare("SELECT id, status, subject, email, product_id, created_at FROM inquiries WHERE id=:id");
$beforeStmt->execute([':id'=>$id]);
$before = $beforeStmt->fetch(PDO::FETCH_ASSOC);
if(!$before){
  die('Invalid');
}

$pdo->prepare("UPDATE inquiries SET status=:s WHERE id=:id")->execute([':s'=>$status, ':id'=>$id]);

$afterStmt = $pdo->prepare("SELECT id, status, subject, email, product_id, created_at FROM inquiries WHERE id=:id");
$afterStmt->execute([':id'=>$id]);
$after = $afterStmt->fetch(PDO::FETCH_ASSOC);

audit_log($pdo, 'inquiry', $id, 'inquiry_status_updated', $before, $after, [
  'status_changed' => (($before['status'] ?? null) !== ($after['status'] ?? null)),
]);

header('Location: '.url('admin/inquiry-view.php?id='.$id));
