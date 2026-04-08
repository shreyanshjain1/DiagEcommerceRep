<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();
ensure_post();
csrf_validate(url('admin/products-new.php?err=csrf'));

function ensure_dir(string $rel): void {
  $abs = realpath(__DIR__ . '/..') . '/' . trim($rel, '/');
  if (!is_dir($abs)) @mkdir($abs, 0775, true);
}

function save_upload(array $f, string $dirRel, array $allowedExt = ['jpg','jpeg','png','webp','pdf']): ?string {
  $name = (string)($f['name'] ?? '');
  $err  = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);

  if (empty($f['tmp_name']) || $err !== UPLOAD_ERR_OK) {
    if ($err !== UPLOAD_ERR_NO_FILE) {
      error_log('Upload failed (new product): name=' . $name . ' error=' . $err . ' size=' . (string)($f['size'] ?? ''));
    }
    return null;
  }

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext === '' || !in_array($ext, $allowedExt, true)) {
    error_log('Upload blocked (new product ext not allowed): ' . $name);
    return null;
  }

  $dirRel = trim($dirRel, '/');
  ensure_dir($dirRel);

  $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($name, PATHINFO_FILENAME));
  if ($safeBase === '') $safeBase = 'file';
  $file = $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;

  $destAbs = realpath(__DIR__ . '/..') . '/' . $dirRel . '/' . $file;
  if (!@move_uploaded_file($f['tmp_name'], $destAbs)) {
    error_log('move_uploaded_file failed (new product): ' . ($f['name'] ?? '') . ' => ' . $destAbs);
    return null;
  }

  return $dirRel . '/' . $file;
}

$category_id = (int)($_POST['category_id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$sku = trim((string)($_POST['sku'] ?? ''));
$brand = trim((string)($_POST['brand'] ?? ''));
$supplier_id = (int)($_POST['supplier_id'] ?? 0);
$vendor_sku = trim((string)($_POST['vendor_sku'] ?? ''));
$availability_status = trim((string)($_POST['availability_status'] ?? 'in_stock'));
$unit_of_measure = trim((string)($_POST['unit_of_measure'] ?? ''));
$pack_size = trim((string)($_POST['pack_size'] ?? ''));
$moq = max(1, (int)($_POST['moq'] ?? 1));
$lead_time_days = ($_POST['lead_time_days'] ?? '') === '' ? null : max(0, (int)$_POST['lead_time_days']);
$lead_time_note = trim((string)($_POST['lead_time_note'] ?? ''));
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);

// New-product form uses status select; edit form uses is_active
$status = (string)($_POST['status'] ?? 'active');
$is_active = 1;
if (isset($_POST['is_active'])) {
  $is_active = ((string)$_POST['is_active'] === '0') ? 0 : 1;
} else {
  $is_active = ($status === 'inactive') ? 0 : 1;
}

// Backward compatible field names
$short_desc = trim((string)($_POST['short_desc'] ?? ($_POST['short_description'] ?? '')));
$long_desc  = trim((string)($_POST['long_desc'] ?? ($_POST['description'] ?? '')));

// Slug
$slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
if ($slug === '') $slug = 'product-' . date('YmdHis');

// Specs
$specs = [];
$keys = $_POST['specs_key'] ?? ($_POST['spec_key'] ?? []);
$vals = $_POST['specs_val'] ?? ($_POST['spec_val'] ?? []);
if (is_array($keys) && is_array($vals)) {
  $n = min(count($keys), count($vals));
  for ($i = 0; $i < $n; $i++) {
    $k = trim((string)$keys[$i]);
    $v = trim((string)$vals[$i]);
    if ($k !== '' && $v !== '') $specs[$k] = $v;
  }
}
$specs_json = json_encode($specs, JSON_UNESCAPED_UNICODE);
$allowedAvailability = ['in_stock','low_stock','out_of_stock','backorder','preorder','discontinued'];
if (!in_array($availability_status, $allowedAvailability, true)) $availability_status = 'in_stock';

try {
  if (!$pdo->inTransaction()) {
    $pdo->beginTransaction();
  }

  $pdo->prepare("INSERT INTO products(category_id,supplier_id,name,slug,sku,vendor_sku,brand,availability_status,unit_of_measure,pack_size,moq,lead_time_days,lead_time_note,short_desc,long_desc,specs_json,price,stock,is_active,created_at,updated_at)
                 VALUES (:c,:supplier_id,:n,:slug,:sku,:vendor_sku,:b,:availability_status,:uom,:pack_size,:moq,:lead_time_days,:lead_time_note,:sd,:ld,:sj,:p,:s,:a,NOW(),NOW())")
      ->execute([
        ':c'=>$category_id, ':supplier_id'=>($supplier_id > 0 ? $supplier_id : null), ':n'=>$name, ':slug'=>$slug, ':sku'=>$sku, ':vendor_sku'=>$vendor_sku, ':b'=>$brand,
        ':availability_status'=>$availability_status, ':uom'=>$unit_of_measure, ':pack_size'=>$pack_size, ':moq'=>$moq, ':lead_time_days'=>$lead_time_days, ':lead_time_note'=>$lead_time_note,
        ':sd'=>$short_desc, ':ld'=>$long_desc, ':sj'=>$specs_json, ':p'=>$price, ':s'=>$stock, ':a'=>$is_active
      ]);

  $pid = (int)$pdo->lastInsertId();

  // Images
  if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
      $file = [
        'name' => $_FILES['images']['name'][$i] ?? '',
        'type' => $_FILES['images']['type'][$i] ?? '',
        'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
        'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size' => $_FILES['images']['size'][$i] ?? 0,
      ];
      $rel = save_upload($file, 'uploads/products', ['jpg','jpeg','png','webp']);
      if ($rel) {
        $pdo->prepare("INSERT INTO product_images(product_id,image_path,sort_order) VALUES (?,?,?)")
            ->execute([$pid, $rel, 0]);
      }
    }
  }

  // Docs (PDF)
  if (!empty($_FILES['docs']) && is_array($_FILES['docs']['name'])) {
    $titles = $_POST['docs_titles'] ?? [];
    $singleTitle = trim((string)($_POST['doc_title'] ?? ''));
    $singleLabel = trim((string)($_POST['doc_label'] ?? ''));

    for ($i = 0; $i < count($_FILES['docs']['name']); $i++) {
      $file = [
        'name' => $_FILES['docs']['name'][$i] ?? '',
        'type' => $_FILES['docs']['type'][$i] ?? '',
        'tmp_name' => $_FILES['docs']['tmp_name'][$i] ?? '',
        'error' => $_FILES['docs']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size' => $_FILES['docs']['size'][$i] ?? 0,
      ];

      $rel = save_upload($file, 'uploads/docs', ['pdf']);
      if ($rel) {
        $t = '';
        if (is_array($titles) && isset($titles[$i])) $t = trim((string)$titles[$i]);
        if ($t === '' && $singleTitle !== '') $t = $singleTitle;
        if ($t === '') $t = 'Brochure';

        $lbl = ($singleLabel !== '' ? $singleLabel : $t);

        $pdo->prepare("INSERT INTO documents(product_id,title,label,file_path,created_at) VALUES (?,?,?,?,NOW())")
            ->execute([$pid, $t, $lbl, $rel]);
      }
    }
  }

  $pdo->commit();
  header('Location: ' . url('admin/products.php?ok=created'));
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('Product create error: ' . $e->getMessage());
  header('Location: ' . url('admin/products-new.php?err=create_failed'));
  exit;
}
