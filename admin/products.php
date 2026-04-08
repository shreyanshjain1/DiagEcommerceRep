<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';
require_once __DIR__.'/../includes/helpers.php';

$q = trim((string)($_GET['q'] ?? ''));
$availability = trim((string)($_GET['availability'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($q !== '') {
  $where[] = "(p.name LIKE :q OR p.sku LIKE :q OR p.vendor_sku LIKE :q OR p.brand LIKE :q OR c.name LIKE :q OR s.name LIKE :q)";
  $params[':q'] = '%' . $q . '%';
}
if ($availability !== '' && in_array($availability, ['in_stock','low_stock','out_of_stock','backorder','preorder','discontinued'], true)) {
  $where[] = "p.availability_status = :availability";
  $params[':availability'] = $availability;
}
if ($status !== '' && in_array($status, ['active','inactive'], true)) {
  $where[] = "p.is_active = :is_active";
  $params[':is_active'] = ($status === 'active') ? 1 : 0;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN suppliers s ON s.id=p.supplier_id {$whereSql}");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT p.id,p.name,p.sku,p.vendor_sku,p.brand,p.price,p.stock,p.is_active,p.availability_status,p.pack_size,p.moq,p.lead_time_days,
               c.name AS cat, s.name AS supplier_name
        FROM products p
        JOIN categories c ON c.id=p.category_id
        LEFT JOIN suppliers s ON s.id=p.supplier_id
        {$whereSql}
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT {$perPage} OFFSET {$offset}";
$st = $pdo->prepare($sql);
$st->execute($params);
$prods = $st->fetchAll();

function page_link(array $overrides = []): string {
  $query = array_merge($_GET, $overrides);
  return url('admin/products.php?' . http_build_query($query));
}
?>

<h1>Products</h1>

<div class="toolbar" style="justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap">
  <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">+ New Product</a>
  <form method="get" action="<?php echo url('admin/products.php'); ?>" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <div>
      <label>Search</label>
      <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Name, SKU, brand, supplier">
    </div>
    <div>
      <label>Availability</label>
      <select name="availability">
        <option value="">All</option>
        <?php foreach (['in_stock'=>'In stock','low_stock'=>'Low stock','out_of_stock'=>'Out of stock','backorder'=>'Backorder','preorder'=>'Pre-order','discontinued'=>'Discontinued'] as $key => $label): ?>
          <option value="<?php echo e($key); ?>" <?php echo $availability === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>
    <button class="btn secondary" type="submit">Apply</button>
  </form>
</div>

<div class="card p" style="margin-bottom:16px">
  <div class="muted">Commercial-ready product catalog fields now include supplier, vendor SKU, availability state, pack size, MOQ, and lead time metadata.</div>
</div>

<table class="table">
  <tr>
    <th>Name</th>
    <th>Category</th>
    <th>Supplier</th>
    <th>Commercial</th>
    <th style="width:140px">Price</th>
    <th style="width:140px">Stock</th>
    <th style="width:130px">Active</th>
    <th style="width:90px">Save</th>
    <th style="width:90px">Edit</th>
  </tr>

  <?php foreach($prods as $p): ?>
    <?php $av = product_availability_meta((string)($p['availability_status'] ?? 'in_stock')); ?>
    <tr>
      <form action="<?php echo url('admin_actions/product.php'); ?>" method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">

        <td>
          <strong><?php echo e($p['name']); ?></strong><br>
          <span class="muted">SKU: <?php echo e($p['sku']); ?></span>
          <?php if (!empty($p['vendor_sku'])): ?><br><span class="muted">Vendor SKU: <?php echo e($p['vendor_sku']); ?></span><?php endif; ?>
        </td>
        <td><?php echo e($p['cat']); ?></td>
        <td><?php echo e($p['supplier_name'] ?: '—'); ?></td>
        <td>
          <span class="pill secondary"><?php echo e($av['label']); ?></span><br>
          <span class="muted">
            <?php if (!empty($p['pack_size'])): ?>Pack: <?php echo e($p['pack_size']); ?> · <?php endif; ?>
            MOQ: <?php echo e((int)($p['moq'] ?? 1)); ?>
            <?php if ((int)($p['lead_time_days'] ?? 0) > 0): ?> · Lead: <?php echo e((int)$p['lead_time_days']); ?>d<?php endif; ?>
          </span>
        </td>

        <td><input type="number" step="0.01" name="price" value="<?php echo e($p['price']); ?>"></td>

        <td>
          <input type="number" name="stock" value="<?php echo e($p['stock']); ?>" style="max-width:110px">
        </td>

        <td>
          <select name="is_active">
            <option value="1" <?php echo $p['is_active']?'selected':''; ?>>Yes</option>
            <option value="0" <?php echo !$p['is_active']?'selected':''; ?>>No</option>
          </select>
        </td>

        <td><button class="btn" type="submit">Save</button></td>
        <td><a class="btn secondary" href="<?php echo url('admin/products-edit.php?id='.$p['id']); ?>">Edit</a></td>
      </form>
    </tr>
  <?php endforeach; ?>
</table>

<div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;gap:12px;flex-wrap:wrap">
  <div class="muted">Showing <?php echo e(count($prods)); ?> of <?php echo e($total); ?> products</div>
  <div style="display:flex;gap:8px;align-items:center">
    <?php if ($page > 1): ?>
      <a class="btn ghost" href="<?php echo e(page_link(['page' => $page - 1])); ?>">Previous</a>
    <?php endif; ?>
    <span class="muted">Page <?php echo e($page); ?> of <?php echo e($totalPages); ?></span>
    <?php if ($page < $totalPages): ?>
      <a class="btn ghost" href="<?php echo e(page_link(['page' => $page + 1])); ?>">Next</a>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
