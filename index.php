<?php
require_once __DIR__.'/includes/helpers.php';

// SEO for OFFICIAL homepage
$SEO_TITLE = "Pharmastar Diagnostics | Division of Pharmastar Int'l Trading Corp | RFQ & Products";
$SEO_DESC  = "Pharmastar Diagnostics — a division of Pharmastar Int'l Trading Corp. Browse diagnostic machines, analyzers, reagents, and consumables in the Philippines. Request a quotation (RFQ) online.";
$SEO_CANONICAL = "https://pharmastar.org/ecom/index.php";

require_once __DIR__.'/includes/header.php';

// Data (safe: show page even if DB is not yet imported)
$cats = [];
$featured = [];
$brandRows = [];
$stats = ['products'=>0,'categories'=>0,'brands'=>0];

try {
  $cats = $pdo->query("SELECT id,name,slug FROM categories ORDER BY sort_order ASC, name ASC LIMIT 12")->fetchAll();

  $featured = $pdo->query("SELECT p.id,p.name,p.brand,p.sku,
    (SELECT image_path FROM product_images i WHERE i.product_id=p.id ORDER BY i.sort_order LIMIT 1) AS img
    FROM products p
    WHERE p.is_active=1
    ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

  $brandRows = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand<>'' ORDER BY brand LIMIT 12")->fetchAll();

  $stats = [
    'products' => (int)$pdo->query("SELECT COUNT(*) c FROM products WHERE is_active=1")->fetch()['c'],
    'categories' => (int)$pdo->query("SELECT COUNT(*) c FROM categories")->fetch()['c'],
    'brands' => (int)$pdo->query("SELECT COUNT(DISTINCT brand) c FROM products WHERE brand IS NOT NULL AND brand<>''")->fetch()['c'],
  ];
} catch (Throwable $e) {
  // ignore – likely DB not imported yet
}

function cat_icon(string $slug): string {
  $s = strtolower($slug);
  if (str_contains($s, 'hema')) return '🩸';
  if (str_contains($s, 'chem')) return '🧪';
  if (str_contains($s, 'immun')) return '🧬';
  if (str_contains($s, 'elect')) return '⚡';
  if (str_contains($s, 'urine') || str_contains($s, 'urinal')) return '🧫';
  if (str_contains($s, 'reagent') || str_contains($s, 'consum')) return '📦';
  return '🏥';
}
?>

<!-- SEO snippet helper block (helps Google show “division of…” in the search snippet) -->
<section class="section reveal">
  <div class="card" style="padding:18px">
    <h1 style="margin:0 0 10px">Pharmastar Diagnostics</h1>
    <p class="muted" style="margin:0; line-height:1.6">
      <strong>Pharmastar Diagnostics</strong> is a division of <strong>Pharmastar Int’l Trading Corp</strong>.
      This platform is for browsing diagnostic machines, laboratory analyzers, reagents, and consumables in the Philippines,
      and submitting <strong>RFQ / quotation requests</strong> online for faster processing and after-sales support.
    </p>
  </div>
</section>

<section class="hero reveal">
  <div class="hero-grid">
    <div>
      <div class="kicker">Philippine diagnostics supplier</div>
      <h2 class="hero-title">Professional laboratory analyzers & reagents—delivered with dependable support.</h2>
      <p class="hero-sub">Browse Erba and Agappe instruments, reagents, and consumables. Request a quotation in minutes and get fast assistance from our team.</p>

      <div class="hero-cta">
        <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse Products</a>
        <a class="btn secondary" href="<?php echo url('pages/inquiry.php'); ?>">Request a Quote</a>
      </div>

      <div class="hero-stats">
        <div class="stat">
          <div class="stat-num"><?php echo number_format($stats['products']); ?>+</div>
          <div class="stat-label">Active items</div>
        </div>
        <div class="stat">
          <div class="stat-num"><?php echo number_format($stats['brands']); ?>+</div>
          <div class="stat-label">Brands</div>
        </div>
        <div class="stat">
          <div class="stat-num"><?php echo number_format($stats['categories']); ?>+</div>
          <div class="stat-label">Categories</div>
        </div>
      </div>
    </div>

    <div class="hero-card reveal">
      <div class="hero-card-top">
        <div class="pill">Fast Quotations</div>
        <div class="pill">Warranty Support</div>
        <div class="pill">Nationwide Delivery</div>
      </div>
      <div class="hero-card-body">
        <div class="mini-grid">
          <div class="mini">
            <div class="mini-icon">🧾</div>
            <div>
              <div class="mini-title">Saved Quotes</div>
              <div class="mini-sub">Build a quote and reuse it anytime.</div>
            </div>
          </div>
          <div class="mini">
            <div class="mini-icon">🔎</div>
            <div>
              <div class="mini-title">Smart Search</div>
              <div class="mini-sub">Find by model, brand, or SKU.</div>
            </div>
          </div>
          <div class="mini">
            <div class="mini-icon">🤝</div>
            <div>
              <div class="mini-title">After-Sales</div>
              <div class="mini-sub">We help you with setup and support.</div>
            </div>
          </div>
          <div class="mini">
            <div class="mini-icon">⚡</div>
            <div>
              <div class="mini-title">Compare</div>
              <div class="mini-sub">Compare specs side-by-side.</div>
            </div>
          </div>
        </div>

        <div class="hero-brands">
          <?php foreach($brandRows as $b): ?>
            <span class="chip"><?php echo e($b['brand']); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section reveal">
  <div class="section-title">
    <div>
      <h2>Shop by Category</h2>
      <p>Find instruments and consumables by workflow</p>
    </div>
    <a class="link" href="<?php echo url('pages/products.php'); ?>">View all</a>
  </div>
  <div class="grid cols-4">
    <?php foreach($cats as $c): ?>
      <a class="card cat-card" href="<?php echo url('pages/products.php?category='.$c['id']); ?>">
        <div class="p">
          <div class="cat-icon"><?php echo e(cat_icon($c['slug'] ?? '')); ?></div>
          <div class="cat-name"><?php echo e($c['name']); ?></div>
          <div class="muted">Browse items</div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section reveal">
  <div class="section-title">
    <div>
      <h2>Featured Products</h2>
      <p>Popular models and fast-moving items</p>
    </div>
    <a class="link" href="<?php echo url('pages/products.php'); ?>">Browse catalog</a>
  </div>

  <div class="products-grid">
    <?php foreach($featured as $p): ?>
      <div class="product-card">
        <div class="product-thumb">
          <img src="<?php echo asset($p['img'] ? ltrim($p['img'],'/') : 'assets/no-image.png'); ?>" alt="<?php echo e($p['name']); ?>">
        </div>
        <div class="product-body">
          <div class="product-title"><?php echo e($p['name']); ?></div>
          <div class="muted"><?php echo e($p['brand']); ?> <?php if(!empty($p['sku'])): ?><span class="sep">•</span><?php echo e($p['sku']); ?><?php endif; ?></div>
          <div class="product-price"><span class="pill">RFQ Only</span></div>
          <div class="product-actions" style="flex-wrap:wrap">
            <a class="btn" href="<?php echo url('pages/product.php?id='.$p['id']); ?>">View</a>
            <a class="btn secondary" href="<?php echo url('pages/inquiry.php?subject='.rawurlencode('Quotation request: '.$p['name']).'&product_id='.$p['id']); ?>">Inquiry</a>
            <a class="btn ghost" href="<?php echo url('pages/compare.php?toggle='.$p['id']); ?>">Compare</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section reveal">
  <div class="cta">
    <div>
      <h2 class="cta-title">Need a quotation for your lab or hospital?</h2>
      <p class="cta-sub">Send your requirements—model, quantity, location—and we’ll reply with pricing and availability.</p>
    </div>
    <div class="cta-actions">
      <a class="btn" href="<?php echo url('pages/inquiry.php'); ?>">Send Inquiry</a>
      <a class="btn secondary" href="<?php echo url('pages/products.php'); ?>">Explore Products</a>
    </div>
  </div>
</section>

<?php require_once __DIR__.'/includes/footer.php'; ?>
