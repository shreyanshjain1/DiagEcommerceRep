<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

require_admin();
ensure_post(); csrf_validate();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if(!$id || !in_array($status, ['New','In Progress','Closed'], true)){ die('Invalid'); }

$pdo->prepare("UPDATE inquiries SET status=:s WHERE id=:id")->execute([':s'=>$status, ':id'=>$id]);

header('Location: '.url('admin/inquiry-view.php?id='.$id));
