<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/csrf.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo '<div class="alert error">Product not found.</div>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$st = $pdo->prepare("SELECT p.*, c.name AS category_name
                     FROM products p
                     LEFT JOIN categories c ON c.id = p.category_id
                     WHERE p.id = :id AND p.is_active = 1");
$st->execute([':id' => $id]);
$p = $st->fetch();

if (!$p) {
  echo '<div class="alert error">Product not found.</div>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$imgs = $pdo->prepare("SELECT id, image_path FROM product_images WHERE product_id = :id ORDER BY sort_order ASC, id ASC");
$imgs->execute([':id' => $id]);
$images = $imgs->fetchAll();

$docs = $pdo->prepare("SELECT id, title, label, file_path FROM documents WHERE product_id = :id ORDER BY id ASC");
$docs->execute([':id' => $id]);
$documents = $docs->fetchAll();

$specs = [];
if (!empty($p['specs_json'])) {
  $tmp = json_decode((string)$p['specs_json'], true);
  if (is_array($tmp)) $specs = $tmp;
}

$primaryImg = $images[0]['image_path'] ?? 'assets/no-image.png';
$primaryImgAbs = asset(ltrim($primaryImg, '/'));
?>

<div class="breadcrumbs">
  <a href="<?php echo url('index.php'); ?>">Home</a> <span>›</span>
  <a href="<?php echo url('pages/products.php'); ?>">Products</a> <span>›</span>
  <span><?php echo e($p['name']); ?></span>
</div>

<div class="product-hero">
  <section class="surface product-gallery-shell">
    <div class="product-gallery-stage">
      <img id="mainImg" src="<?php echo $primaryImgAbs; ?>" alt="<?php echo e($p['name']); ?>">
    </div>

    <?php if (count($images) > 1): ?>
      <div class="product-thumbs" id="thumbs">
        <?php foreach ($images as $idx => $im): ?>
          <?php $imgAbs = asset(ltrim($im['image_path'], '/')); ?>
          <button type="button" class="product-thumb-btn<?php echo $idx === 0 ? ' active' : ''; ?>" data-img="<?php echo e($imgAbs); ?>" aria-label="View image <?php echo (int)($idx + 1); ?>">
            <img src="<?php echo $imgAbs; ?>" alt="">
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="surface soft product-info-shell">
    <div class="product-pills">
      <span class="product-pill primary">RFQ-first platform</span>
      <?php if (!empty($p['brand'])): ?><span class="product-pill"><?php echo e($p['brand']); ?></span><?php endif; ?>
      <?php if (!empty($p['category_name'])): ?><span class="product-pill"><?php echo e($p['category_name']); ?></span><?php endif; ?>
      <?php if (!empty($p['sku'])): ?><span class="product-pill">SKU: <?php echo e($p['sku']); ?></span><?php endif; ?>
    </div>

    <h1 class="product-heading"><?php echo e($p['name']); ?></h1>

    <?php if (!empty($p['short_desc'])): ?>
      <div class="product-subcopy"><?php echo e($p['short_desc']); ?></div>
    <?php endif; ?>

    <?php if (!empty($p['long_desc'])): ?>
      <div class="product-subcopy"><?php echo nl2br(e($p['long_desc'])); ?></div>
    <?php endif; ?>

    <div class="product-commercial">
      <div class="mini">
        <div class="mini-title">Procurement flow</div>
        <div class="mini-sub">Submit this product to your RFQ list and let the admin team return pricing, availability, and lead time.</div>
      </div>
      <div class="mini">
        <div class="mini-title">Catalog presentation</div>
        <div class="mini-sub">This refreshed layout is tuned for stronger recruiter appeal and better B2B product presentation.</div>
      </div>
    </div>

    <div class="product-action-row">
      <?php if (current_user_id()): ?>
        <form class="buy-row" action="<?php echo url('actions/cart.php'); ?>" method="post">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
          <div class="qty-wrap">
            <label>Quantity</label>
            <input type="number" name="qty" min="1" value="1">
          </div>
          <button class="btn" type="submit">Add to RFQ</button>
          <a class="btn secondary" href="<?php echo url('pages/cart.php'); ?>">View RFQ</a>
          <a class="btn ghost" href="<?php echo url('pages/inquiry.php?subject=' . rawurlencode('RFQ: ' . $p['name']) . '&product_id=' . $p['id']); ?>">Inquiry</a>
        </form>
      <?php else: ?>
        <a class="btn" href="<?php echo url('pages/login.php'); ?>">Login to Request RFQ</a>
      <?php endif; ?>
    </div>

    <?php if ($specs): ?>
      <div class="product-spec-shell">
        <h3 class="m0">Key Specifications</h3>
        <table class="table mt16">
          <tbody>
            <?php foreach ($specs as $k => $v): ?>
              <tr>
                <th style="width:34%">
                  <?php echo e((string)$k); ?>
                </th>
                <td><?php echo e(is_scalar($v) ? (string)$v : json_encode($v)); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if ($documents): ?>
      <div class="product-spec-shell">
        <h3 class="m0">Brochures & Documents</h3>
        <div class="product-doc-list">
          <?php foreach ($documents as $doc): ?>
            <a class="product-doc-item" href="<?php echo asset(ltrim($doc['file_path'], '/')); ?>" target="_blank" rel="noopener">
              <span>
                <strong><?php echo e($doc['title'] ?: 'Product document'); ?></strong><br>
                <span class="muted"><?php echo e($doc['label'] ?: 'Download file'); ?></span>
              </span>
              <span class="btn secondary">Open</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
(function(){
  const main = document.getElementById('mainImg');
  const thumbs = document.querySelectorAll('#thumbs .product-thumb-btn');
  thumbs.forEach(btn => {
    btn.addEventListener('click', () => {
      main.src = btn.getAttribute('data-img');
      thumbs.forEach(t => t.classList.remove('active'));
      btn.classList.add('active');
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
