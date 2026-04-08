<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

$ids = array_map('intval', wishlist_all());
if (!$ids) {
  echo '<h1>Wishlist</h1>';
  echo '<div class="empty"><img src="'.asset('assets/img/empty.svg').'" alt=""><div>No saved items yet.</div><div style="margin-top:10px"><a class="btn" href="'.url('pages/products.php').'">Browse Products</a></div></div>';
  require_once __DIR__.'/../includes/footer.php';
  exit;
}

$in = implode(',', $ids);
$rows = $pdo->query("SELECT p.id,p.name,p.brand,p.sku,p.price,p.stock,
  (SELECT image_path FROM product_images i WHERE i.product_id=p.id ORDER BY i.sort_order LIMIT 1) AS img
  FROM products p WHERE p.is_active=1 AND p.id IN ($in)")->fetchAll();
?>

<div class="breadcrumbs">Home » Wishlist</div>
<h1>Wishlist</h1>

<div class="products-grid">
  <?php foreach ($rows as $p): ?>
    <div class="product-card">
      <div class="product-thumb">
        <img src="<?php echo asset($p['img'] ? ltrim($p['img'],'/') : 'assets/no-image.png'); ?>" alt="<?php echo e($p['name']); ?>">
      </div>
      <div class="product-body">
        <div class="product-title"><?php echo e($p['name']); ?></div>
        <div class="muted"><?php echo e($p['brand']); ?> <?php if(!empty($p['sku'])): ?><span class="sep">•</span><?php echo e($p['sku']); ?><?php endif; ?></div>
        <div class="product-price">₱<?php echo number_format((float)$p['price'],2); ?></div>

        <div class="product-actions" style="flex-wrap:wrap">
          <a class="btn" href="<?php echo url('pages/product.php?id='.$p['id']); ?>">View</a>

          <form action="<?php echo url('actions/wishlist.php'); ?>" method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?php echo e($p['id']); ?>">
            <input type="hidden" name="return" value="<?php echo e(url('pages/wishlist.php')); ?>">
            <button class="btn secondary" type="submit">Remove</button>
          </form>

          <form action="<?php echo url('actions/cart.php'); ?>" method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?php echo e($p['id']); ?>">
            <input type="hidden" name="qty" value="1">
            <button class="btn" type="submit">Add to Cart</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
