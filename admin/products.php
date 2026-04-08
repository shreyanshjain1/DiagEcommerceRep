<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';

$prods = $pdo->query("SELECT p.id,p.name,p.sku,p.brand,p.price,p.stock,p.is_active,c.name AS cat
                      FROM products p JOIN categories c ON c.id=p.category_id
                      ORDER BY p.created_at DESC, p.id DESC LIMIT 500")->fetchAll();
$active = 0; $low = 0;
foreach ($prods as $p) {
  if (!empty($p['is_active'])) $active++;
  if ((int)$p['stock'] <= 5) $low++;
}
?>

<div class="admin-page-head">
  <div>
    <h1 class="admin-page-title">Product catalog ops</h1>
    <p class="admin-page-sub">Premium product workspace for pricing, stock controls, visibility, and commercial catalog quality.</p>
  </div>
  <div class="admin-page-actions">
    <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">+ New Product</a>
    <a class="btn secondary" href="<?php echo url('pages/products.php'); ?>">View Public Catalog</a>
  </div>
</div>

<div class="admin-stats-grid admin-stats-grid-tight">
  <div class="admin-stat-card"><div class="admin-stat-label">Visible products</div><div class="admin-stat-value"><?php echo count($prods); ?></div><div class="admin-stat-meta">Latest 500 catalog rows shown</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Active</div><div class="admin-stat-value"><?php echo $active; ?></div><div class="admin-stat-meta">Customer-facing listings enabled</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Low stock</div><div class="admin-stat-value"><?php echo $low; ?></div><div class="admin-stat-meta">Products at or below 5 units</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Brands shown</div><div class="admin-stat-value"><?php echo count(array_unique(array_filter(array_map(fn($x)=>$x['brand'],$prods)))); ?></div><div class="admin-stat-meta">Commercial brand spread</div></div>
</div>

<div class="admin-table-shell">
  <div class="admin-table-headline">
    <div>
      <h2 class="admin-panel-title">Catalog control table</h2>
      <p class="admin-panel-sub">Inline pricing and stock edits with a more polished management surface.</p>
    </div>
  </div>
  <table class="table table-premium table-products">
    <tr>
      <th>Name</th>
      <th>SKU</th>
      <th>Brand / Category</th>
      <th>Price</th>
      <th>Stock</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>

    <?php foreach($prods as $p): ?>
      <tr>
        <form action="<?php echo url('admin_actions/product.php'); ?>" method="post">
          <?php csrf_field(); ?>
          <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
          <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">

          <td>
            <div class="admin-table-primary"><?php echo e($p['name']); ?></div>
            <div class="admin-table-meta">Catalog product #<?php echo (int)$p['id']; ?></div>
          </td>
          <td><span class="admin-metric-pill"><?php echo e($p['sku']); ?></span></td>
          <td>
            <div class="admin-table-primary"><?php echo e($p['brand']); ?></div>
            <div class="admin-table-meta"><?php echo e($p['cat']); ?></div>
          </td>
          <td><input type="number" step="0.01" name="price" value="<?php echo e($p['price']); ?>"></td>
          <td>
            <input type="number" name="stock" value="<?php echo e($p['stock']); ?>" style="max-width:110px">
            <?php if((int)$p['stock']<=5): ?><div class="admin-table-meta"><span class="badge badge-warn">Low stock</span></div><?php endif; ?>
          </td>
          <td>
            <select name="is_active">
              <option value="1" <?php echo $p['is_active']?'selected':''; ?>>Active</option>
              <option value="0" <?php echo !$p['is_active']?'selected':''; ?>>Hidden</option>
            </select>
          </td>
          <td class="admin-table-actions-stack">
            <button class="btn" type="submit">Save</button>
            <a class="btn secondary" href="<?php echo url('admin/products-edit.php?id='.$p['id']); ?>">Edit</a>
            <button class="btn danger" type="submit"
              formaction="<?php echo url('admin_actions/product_delete.php'); ?>"
              onclick="return confirm('Delete this product? This will remove its images and docs too.');">Delete</button>
          </td>
        </form>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
