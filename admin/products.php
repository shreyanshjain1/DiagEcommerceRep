<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';

$prods = $pdo->query("SELECT p.id,p.name,p.sku,p.brand,p.price,p.stock,p.is_active,c.name AS cat
                      FROM products p JOIN categories c ON c.id=p.category_id
                      ORDER BY p.created_at DESC, p.id DESC LIMIT 500")->fetchAll();
?>

<h1>Products</h1>

<div class="toolbar">
  <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">+ New Product</a>
  <form method="get" action="<?php echo url('pages/products.php'); ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="text" name="q" placeholder="Search on site…">
    <button class="btn secondary" type="submit">Search</button>
  </form>
</div>

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
          <?php if((int)$p['stock']<=5): ?><span class="badge-bad">Low</span><?php endif; ?>
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
</table>

<?php require_once __DIR__.'/footer.php'; ?>
