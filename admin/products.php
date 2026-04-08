<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';

$q = trim((string)($_GET['q'] ?? ''));
$active = (string)($_GET['active'] ?? 'all');
$stock = (string)($_GET['stock'] ?? 'all');
$page = page_param();
$perPage = 20;

$where = [];
$params = [];
if ($q !== '') {
  $where[] = '(p.name LIKE :q OR p.sku LIKE :q OR p.brand LIKE :q OR c.name LIKE :q)';
  $params[':q'] = '%' . $q . '%';
}
if (in_array($active, ['1','0'], true)) {
  $where[] = 'p.is_active = :active';
  $params[':active'] = (int)$active;
} else {
  $active = 'all';
}
if ($stock === 'low') {
  $where[] = 'p.stock <= 5';
} elseif ($stock === 'out') {
  $where[] = 'p.stock <= 0';
} else {
  $stock = 'all';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pager = pagination_meta($total, $perPage, $page);

$sql = "SELECT p.id,p.name,p.sku,p.brand,p.price,p.stock,p.is_active,c.name AS cat
        FROM products p
        JOIN categories c ON c.id=p.category_id
        $whereSql
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $pager['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pager['offset'], PDO::PARAM_INT);
$stmt->execute();
$prods = $stmt->fetchAll();
?>

<h1>Products</h1>

<div class="toolbar" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:end;margin-bottom:12px">
  <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">+ New Product</a>
  <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <label><span class="muted">Search</span><br><input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Name, SKU, brand, category"></label>
    <label><span class="muted">Active</span><br>
      <select name="active">
        <option value="all" <?php echo $active === 'all' ? 'selected' : ''; ?>>All</option>
        <option value="1" <?php echo $active === '1' ? 'selected' : ''; ?>>Active</option>
        <option value="0" <?php echo $active === '0' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </label>
    <label><span class="muted">Stock</span><br>
      <select name="stock">
        <option value="all" <?php echo $stock === 'all' ? 'selected' : ''; ?>>All stock</option>
        <option value="low" <?php echo $stock === 'low' ? 'selected' : ''; ?>>Low stock</option>
        <option value="out" <?php echo $stock === 'out' ? 'selected' : ''; ?>>Out of stock</option>
      </select>
    </label>
    <button class="btn secondary" type="submit">Filter</button>
    <a class="btn secondary" href="<?php echo url('admin/products.php'); ?>">Clear</a>
  </form>
</div>

<div class="muted" style="margin-bottom:12px">Showing <?php echo (int)$pager['from']; ?>–<?php echo (int)$pager['to']; ?> of <?php echo (int)$pager['total']; ?> products</div>

<table class="table">
  <tr>
    <th>Name</th>
    <th>SKU</th>
    <th>Brand</th>
    <th>Category</th>
    <th style="width:140px">Price</th>
    <th style="width:140px">Stock</th>
    <th style="width:130px">Active</th>
    <th style="width:90px">Save</th>
    <th style="width:90px">Edit</th>
    <th style="width:100px">Delete</th>
  </tr>

  <?php foreach($prods as $p): ?>
    <tr>
      <form action="<?php echo url('admin_actions/product.php'); ?>" method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">

        <td><?php echo e($p['name']); ?></td>
        <td><?php echo e($p['sku']); ?></td>
        <td><?php echo e($p['brand']); ?></td>
        <td><?php echo e($p['cat']); ?></td>

        <td><input type="number" step="0.01" name="price" value="<?php echo e($p['price']); ?>"></td>

        <td>
          <input type="number" name="stock" value="<?php echo e($p['stock']); ?>" style="max-width:110px">
          <?php if((int)$p['stock']<=0): ?><span class="badge-bad">Out</span>
          <?php elseif((int)$p['stock']<=5): ?><span class="badge-bad">Low</span><?php endif; ?>
        </td>

        <td>
          <select name="is_active">
            <option value="1" <?php echo $p['is_active']?'selected':''; ?>>Yes</option>
            <option value="0" <?php echo !$p['is_active']?'selected':''; ?>>No</option>
          </select>
        </td>

        <td><button class="btn" type="submit">Save</button></td>
        <td><a class="btn secondary" href="<?php echo url('admin/products-edit.php?id='.$p['id']); ?>">Edit</a></td>

        <td>
          <button class="btn danger" type="submit"
            formaction="<?php echo url('admin_actions/product_delete.php'); ?>"
            onclick="return confirm('Delete this product? This will remove its images and docs too.');">Delete</button>
        </td>
      </form>
    </tr>
  <?php endforeach; ?>
  <?php if (!$prods): ?>
    <tr><td colspan="10" class="muted">No products matched the current filter.</td></tr>
  <?php endif; ?>
</table>

<?php echo pagination_links($pager); ?>

<?php require_once __DIR__.'/footer.php'; ?>
