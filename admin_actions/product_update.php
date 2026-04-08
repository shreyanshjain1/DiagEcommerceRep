<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate();

function ensure_dir(string $rel): void {
  $abs = realpath(__DIR__ . '/..') . '/' . trim($rel, '/');
  if (!is_dir($abs)) @mkdir($abs, 0775, true);
}

function save_upload(array $f, string $dirRel, array $allowedExt): ?string {
  $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);

  if (empty($f['tmp_name']) || $err !== UPLOAD_ERR_OK) {
    if ($err !== UPLOAD_ERR_NO_FILE) {
      error_log('UPLOAD_FAIL err=' . $err . ' name=' . ($f['name'] ?? '') . ' size=' . (string)($f['size'] ?? ''));
    }
    return null;
  }

  $name = (string)($f['name'] ?? '');
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext === '' || !in_array($ext, $allowedExt, true)) {
    error_log('UPLOAD_BLOCK ext=' . $ext . ' name=' . $name);
    return null;
  }

  $dirRel = trim($dirRel, '/');
  ensure_dir($dirRel);

  $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($name, PATHINFO_FILENAME));
  if ($safeBase === '') $safeBase = 'file';

  $file = $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
  $destAbs = realpath(__DIR__ . '/..') . '/' . $dirRel . '/' . $file;

  if (!@move_uploaded_file($f['tmp_name'], $destAbs)) {
    error_log('MOVE_FAIL name=' . $name . ' dest=' . $destAbs);
    return null;
  }

  return $dirRel . '/' . $file;
}

$pid = (int)($_POST['id'] ?? 0);
if ($pid <= 0) {
  header('Location: ' . url('admin/products.php?err=missing_id'));
  exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$sku  = trim((string)($_POST['sku'] ?? ''));
$brand = trim((string)($_POST['brand'] ?? ''));
$category_id = (int)($_POST['category_id'] ?? 0);
$price = is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : 0.00;
$stock = is_numeric($_POST['stock'] ?? '') ? (int)$_POST['stock'] : 0;

/** FIX: your form uses <select name="is_active"> so it’s ALWAYS set.
    old code (isset) makes it always 1. */
$is_active = (int)($_POST['is_active'] ?? 1);

$short_desc = trim((string)($_POST['short_desc'] ?? ''));
$long_desc  = trim((string)($_POST['long_desc'] ?? ''));

if ($name === '' || $category_id <= 0) {
  header('Location: ' . url('admin/products-edit.php?id=' . $pid . '&err=missing'));
  exit;
}

/** SPECS: your form sends spec_key[] and spec_val[] */
$specs = [];
$keys = $_POST['spec_key'] ?? ($_POST['specs_key'] ?? []);
$vals = $_POST['spec_val'] ?? ($_POST['specs_val'] ?? []);
if (is_array($keys) && is_array($vals)) {
  $n = min(count($keys), count($vals));
  for ($i = 0; $i < $n; $i++) {
    $k = trim((string)$keys[$i]);
    $v = trim((string)($vals[$i] ?? ''));
    if ($k !== '' && $v !== '') $specs[$k] = $v;
  }
}
$specs_json = $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

// DEBUG (so you can see what the server received)
error_log('SPEC_DEBUG product_id=' . $pid . ' keys=' . (is_array($keys) ? count($keys) : 0) . ' saved=' . count($specs));

try {
  $pdo->beginTransaction();

  /** IMPORTANT: remove updated_at=NOW() to avoid failing if DB schema differs */
  $pdo->prepare("UPDATE products
                 SET category_id=:c, name=:n, sku=:sku, brand=:b,
                     short_desc=:sd, long_desc=:ld, specs_json=:sj,
                     price=:p, stock=:s, is_active=:a
                 WHERE id=:id")
      ->execute([
        ':c'=>$category_id, ':n'=>$name, ':sku'=>$sku, ':b'=>$brand,
        ':sd'=>$short_desc, ':ld'=>$long_desc, ':sj'=>$specs_json,
        ':p'=>$price, ':s'=>$stock, ':a'=>$is_active, ':id'=>$pid
      ]);

  // Replace existing images (optional)
  if (!empty($_POST['replace_images'])) {
    $pdo->prepare("DELETE FROM product_images WHERE product_id=:p")->execute([':p'=>$pid]);
  }

  // Append new images
  if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $count = count($_FILES['images']['name']);
    $sortBase = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),-1)+1 FROM product_images WHERE product_id=".(int)$pid)->fetchColumn();
    $ins = $pdo->prepare("INSERT INTO product_images(product_id,image_path,sort_order) VALUES(:p,:path,:ord)");
    $sort = $sortBase;

    for ($i=0; $i<$count; $i++) {
      $file = [
        'name' => $_FILES['images']['name'][$i] ?? '',
        'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
        'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size' => $_FILES['images']['size'][$i] ?? 0,
      ];
      $saved = save_upload($file, 'uploads/products', ['jpg','jpeg','png','webp']);
      if ($saved) {
        $ins->execute([':p'=>$pid, ':path'=>$saved, ':ord'=>$sort]);
        $sort++;
      }
    }
  }

  // Replace existing docs (optional)
  if (!empty($_POST['replace_docs'])) {
    $pdo->prepare("DELETE FROM documents WHERE product_id=:p")->execute([':p'=>$pid]);
  }

  // Append new docs
  if (!empty($_FILES['docs']) && is_array($_FILES['docs']['name'])) {
    $count = count($_FILES['docs']['name']);

    $doc_title = trim((string)($_POST['doc_title'] ?? ''));
    $doc_label = trim((string)($_POST['doc_label'] ?? ''));

    $ins = $pdo->prepare("INSERT INTO documents(product_id,title,label,file_path) VALUES(:p,:t,:l,:path)");

    for ($i=0; $i<$count; $i++) {
      $file = [
        'name' => $_FILES['docs']['name'][$i] ?? '',
        'tmp_name' => $_FILES['docs']['tmp_name'][$i] ?? '',
        'error' => $_FILES['docs']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size' => $_FILES['docs']['size'][$i] ?? 0,
      ];

      $saved = save_upload($file, 'uploads/docs', ['pdf']);
      if ($saved) {
        $t = ($doc_title !== '' ? $doc_title : 'Brochure');
        $l = ($doc_label !== '' ? $doc_label : $t);
        $ins->execute([':p'=>$pid, ':t'=>$t, ':l'=>$l, ':path'=>$saved]);
      }
    }
  }

  $pdo->commit();
  header('Location: ' . url('admin/products-edit.php?id=' . $pid . '&saved=1'));
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('product_update failed: ' . $e->getMessage());
  header('Location: ' . url('admin/products-edit.php?id=' . $pid . '&err=save_failed'));
  exit;
}
