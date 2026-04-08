<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . url('admin/products.php?err=missing_id'));
  exit;
}

function unlink_if_upload(string $path): void {
  $p = ltrim($path, '/');
  if (strpos($p, 'uploads/') !== 0) return;
  $abs = realpath(__DIR__ . '/../' . $p);
  $root = realpath(__DIR__ . '/../uploads');
  if ($abs && $root && strpos($abs, $root) === 0 && is_file($abs)) {
    @unlink($abs);
  }
}

// collect media paths first (FK cascade deletes DB rows)
$imgs = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=:id");
$imgs->execute([':id'=>$id]);
foreach($imgs->fetchAll() as $r){ unlink_if_upload((string)$r['image_path']); }

$docs = $pdo->prepare("SELECT file_path FROM documents WHERE product_id=:id");
$docs->execute([':id'=>$id]);
foreach($docs->fetchAll() as $r){ unlink_if_upload((string)$r['file_path']); }

$pdo->prepare("DELETE FROM products WHERE id=:id")->execute([':id'=>$id]);

header('Location: ' . url('admin/products.php?deleted=1'));
exit;
