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

$st = $pdo->prepare("SELECT p.*, c.name AS category_name, s.name AS supplier_name
                     FROM products p
                     LEFT JOIN categories c ON c.id = p.category_id
                     LEFT JOIN suppliers s ON s.id = p.supplier_id
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
<style>
/* ===== Product Page (professional + responsive) ===== */
.product-page{padding:12px 0 22px}
.breadcrumbs{margin:10px 0 16px}

.product-wrap{
  display:grid;
  grid-template-columns: 1.05fr 1fr;
  gap:22px;
  align-items:start;
}
@media(max-width:1024px){ .product-wrap{grid-template-columns:1fr} }

.gallery.card.p, .details.card.p{padding:22px !important}
@media(max-width:640px){ .gallery.card.p, .details.card.p{padding:16px !important} }

/* ---- Image area: NO ZOOM / NO CROPPING ---- */
.gallery-main{
  width:100%;
  height:320px;          /* keeps it compact */
  max-height:320px;
  border:1px solid rgba(229,231,235,.9);
  border-radius:18px;
  background:rgba(255,255,255,.95);
  padding:14px;
  box-shadow:0 10px 24px rgba(2,6,23,.06);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
}
@media(max-width:640px){
  .gallery-main{height:240px;max-height:240px;padding:12px}
}

/* IMPORTANT: contain prevents zoom/crop */
.gallery-main img{
  max-width:100%;
  max-height:100%;
  width:auto;
  height:auto;
  object-fit:contain;
  object-position:center;
  display:block;
  transform:none !important;
}

/* Thumbnails */
.thumbs{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
.thumb{
  width:72px;height:72px;
  border-radius:16px;
  border:1px solid rgba(229,231,235,.9);
  background:#fff;
  cursor:pointer;
  opacity:.9;
  padding:6px;
  display:grid;
  place-items:center;
}
.thumb:hover{opacity:1;border-color:#86efac}
.thumb.active{opacity:1;border-color:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.12)}
.thumb img{
  max-width:100%;
  max-height:100%;
  width:auto;
  height:auto;
  object-fit:contain;
  display:block;
}

.pill-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
.pill.secondary{background:rgba(255,255,255,.75);border-color:rgba(229,231,235,.9);color:#0f172a}

.product-title{margin:0 0 8px;line-height:1.18}
.meta-row{margin-top:6px;color:#475569}

.buy-row{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-top:16px}
.buy-row .qty-wrap{width:140px}
.buy-row .btn{white-space:nowrap}

/* ---- Specs table alignment fix ---- */
.specs{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  margin-top:10px;
  overflow:hidden;
  border:1px solid rgba(229,231,235,.9);
  border-radius:18px;
  background:rgba(255,255,255,.95);
  table-layout:fixed; /* makes columns align consistently */
}
.specs th,.specs td{
  padding:12px 14px;
  border-bottom:1px solid rgba(229,231,235,.9);
  vertical-align:top;
  text-align:left;
  line-height:1.5;
}
.specs th{
  width:34%;
  font-weight:950;
  color:#064e3b;
  background:rgba(236,253,245,.60);
  white-space:normal;
  word-break:break-word;
}
.specs td{
  width:66%;
  color:#0f172a;
  white-space:normal;
  word-break:break-word;
}
.specs tr:last-child th,.specs tr:last-child td{border-bottom:none}

/* Documents */
.doc-list{display:flex;flex-direction:column;gap:10px;margin-top:10px}
.doc{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  padding:12px 14px;
  border:1px solid rgba(229,231,235,.9);
  border-radius:16px;
  background:rgba(255,255,255,.95);
  font-weight:900;
}
.doc:hover{background:rgba(236,253,245,.70)}
.doc .doc-title{color:#0f172a}
.doc .doc-sub{font-weight:800}
</style>

<div class="product-page">

  <div class="breadcrumbs">
    <a href="<?php echo url('index.php'); ?>">Home</a> <span>›</span>
    <a href="<?php echo url('pages/products.php'); ?>">Products</a> <span>›</span>
    <span><?php echo e($p['name']); ?></span>
  </div>

  <div class="product-wrap">

    <div class="gallery card p">
      <div class="gallery-main">
        <img id="mainImg" src="<?php echo $primaryImgAbs; ?>" alt="<?php echo e($p['name']); ?>">
      </div>

      <?php if (count($images) > 1): ?>
        <div class="thumbs" id="thumbs">
          <?php foreach ($images as $idx => $im): ?>
            <?php $imgAbs = asset(ltrim($im['image_path'], '/')); ?>
            <button type="button"
                    class="thumb<?php echo $idx === 0 ? ' active' : ''; ?>"
                    data-img="<?php echo e($imgAbs); ?>"
                    aria-label="View image <?php echo (int)($idx + 1); ?>">
              <img src="<?php echo $imgAbs; ?>" alt="">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="details card p">
      <div class="pill-row">
        <span class="pill">RFQ Only</span>
        <?php if (!empty($p['brand'])): ?><span class="pill secondary"><?php echo e($p['brand']); ?></span><?php endif; ?>
        <?php if (!empty($p['category_name'])): ?><span class="pill secondary"><?php echo e($p['category_name']); ?></span><?php endif; ?>
      </div>

      <h1 class="product-title"><?php echo e($p['name']); ?></h1>

      <div class="meta-row">
        <?php if (!empty($p['sku'])): ?><strong>SKU:</strong> <?php echo e($p['sku']); ?><?php endif; ?>
        <?php if (!empty($p['vendor_sku'])): ?> &nbsp; <strong>Vendor SKU:</strong> <?php echo e($p['vendor_sku']); ?><?php endif; ?>
      </div>

      <?php if (!empty($p['short_desc'])): ?>
        <p class="mt16" style="line-height:1.65"><?php echo e($p['short_desc']); ?></p>
      <?php endif; ?>

      <?php if (!empty($p['long_desc'])): ?>
        <div class="mt16 muted" style="white-space:pre-line;line-height:1.65"><?php echo e($p['long_desc']); ?></div>
      <?php endif; ?>

      <?php $availability = product_availability_meta((string)($p['availability_status'] ?? 'in_stock')); ?>
      <div class="card" style="margin-top:18px;border:1px solid rgba(229,231,235,.9);border-radius:18px;padding:14px 16px;background:rgba(248,250,252,.95)">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
          <span class="pill secondary"><?php echo e($availability['label']); ?></span>
          <?php if (!empty($p['supplier_name'])): ?><span class="pill secondary">Supplier: <?php echo e($p['supplier_name']); ?></span><?php endif; ?>
        </div>
        <div class="muted" style="line-height:1.7">
          <?php if (!empty($p['unit_of_measure'])): ?><strong>Unit:</strong> <?php echo e($p['unit_of_measure']); ?><br><?php endif; ?>
          <?php if (!empty($p['pack_size'])): ?><strong>Pack Size:</strong> <?php echo e($p['pack_size']); ?><br><?php endif; ?>
          <strong>MOQ:</strong> <?php echo e((int)($p['moq'] ?? 1)); ?><br>
          <?php if (!empty($p['lead_time_days'])): ?><strong>Lead Time:</strong> <?php echo e((int)$p['lead_time_days']); ?> day(s)<br><?php endif; ?>
          <?php if (!empty($p['lead_time_note'])): ?><strong>Lead Time Note:</strong> <?php echo e($p['lead_time_note']); ?><br><?php endif; ?>
        </div>
      </div>

      <div class="mt16">
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
        <h3 class="mt24">Key Specifications</h3>
        <table class="specs">
          <?php foreach ($specs as $k => $v): ?>
            <tr>
              <th><?php echo e((string)$k); ?></th>
              <td><?php echo e(is_scalar($v) ? (string)$v : json_encode($v)); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

      <?php if ($documents): ?>
        <h3 class="mt24">Documents</h3>
        <div class="doc-list">
          <?php foreach ($documents as $d): ?>
            <a class="doc" href="<?php echo asset(ltrim($d['file_path'], '/')); ?>" target="_blank" rel="noopener">
              <span class="doc-title"><?php echo e($d['label'] ?: $d['title']); ?></span>
              <span class="doc-sub muted">Open</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="mt24 muted" style="font-size:13px;line-height:1.55">
        <strong>Note:</strong> This is a quotation-request catalog. Pricing, lead time, and availability will be confirmed by our team after you submit your RFQ.
      </div>
    </div>

  </div>

</div>

<script>
(function(){
  const main = document.getElementById('mainImg');
  const thumbs = document.querySelectorAll('.thumbs .thumb');
  thumbs.forEach(btn => {
    btn.addEventListener('click', () => {
      const src = btn.getAttribute('data-img');
      if (main && src) main.src = src;
      thumbs.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
