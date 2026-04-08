<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate();

$pid = (int)($_POST['product_id'] ?? 0);
$type = (string)($_POST['type'] ?? '');
$mid = (int)($_POST['id'] ?? 0);

if ($pid <= 0 || $mid <= 0 || !in_array($type, ['image','doc'], true)) {
  header('Location: ' . url('admin/products.php?err=bad_request'));
  exit;
}

function unlink_if_upload(string $path): void {
  $p = ltrim($path, '/');
  // Only delete files under uploads/ for safety.
  if (strpos($p, 'uploads/') !== 0) return;
  $abs = realpath(__DIR__ . '/../' . $p);
  $root = realpath(__DIR__ . '/../uploads');
  if ($abs && $root && strpos($abs, $root) === 0 && is_file($abs)) {
    @unlink($abs);
  }
}

if ($type === 'image') {
  $st = $pdo->prepare("SELECT image_path FROM product_images WHERE id=:id AND product_id=:pid");
  $st->execute([':id'=>$mid, ':pid'=>$pid]);
  $row = $st->fetch();
  if ($row) {
    unlink_if_upload((string)$row['image_path']);
    $pdo->prepare("DELETE FROM product_images WHERE id=:id AND product_id=:pid")->execute([':id'=>$mid, ':pid'=>$pid]);
  }
} else {
  $st = $pdo->prepare("SELECT file_path FROM documents WHERE id=:id AND product_id=:pid");
  $st->execute([':id'=>$mid, ':pid'=>$pid]);
  $row = $st->fetch();
  if ($row) {
    unlink_if_upload((string)$row['file_path']);
    $pdo->prepare("DELETE FROM documents WHERE id=:id AND product_id=:pid")->execute([':id'=>$mid, ':pid'=>$pid]);
  }
}

header('Location: ' . url('admin/products-edit.php?id=' . $pid . '&saved=1'));
exit;
