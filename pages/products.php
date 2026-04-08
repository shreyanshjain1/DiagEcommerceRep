<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/csrf.php';

$q = trim((string)($_GET['q'] ?? ''));
$category = (int)($_GET['category'] ?? 0);
$brand = trim((string)($_GET['brand'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'newest');

$cats = $pdo->query("SELECT id,name FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();
$brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand<>'' ORDER BY brand ASC")->fetchAll();

$where = ["p.is_active=1"];
$params = [];
if ($q !== '') {
  $where[] = "(p.name LIKE :q OR p.sku LIKE :q OR p.brand LIKE :q OR p.long_desc LIKE :q)";
  $params[':q'] = '%' . $q . '%';
}
if ($category > 0) { $where[] = "p.category_id = :cat"; $params[':cat'] = $category; }
if ($brand !== '' && $brand !== 'All') { $where[] = "p.brand = :brand"; $params[':brand'] = $brand; }

$order = "p.created_at DESC";
if ($sort === 'name_asc') $order = "p.name ASC";
if ($sort === 'name_desc') $order = "p.name DESC";

$sql = "SELECT p.id,p.name,p.slug,p.brand,p.sku,p.short_desc,
          (SELECT image_path FROM product_images i WHERE i.product_id=p.id ORDER BY i.sort_order ASC LIMIT 1) AS img
        FROM products p
        WHERE " . implode(" AND ", $where) . "
        ORDER BY $order
        LIMIT 500";
$st = $pdo->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$hasFilters = ($q !== '' || $category > 0 || ($brand !== '' && $brand !== 'All') || ($sort !== '' && $sort !== 'newest'));
$filtersCount = 0;
if ($q !== '') $filtersCount++;
if ($category > 0) $filtersCount++;
if ($brand !== '' && $brand !== 'All') $filtersCount++;
if ($sort !== '' && $sort !== 'newest') $filtersCount++;

/**
 * CSRF token: your csrf.php should already set it.
 * We ensure it exists so AJAX works.
 */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<style>
/* ===== Filter UX: top compact bar + mobile drawer ===== */
.filters-row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px}
.filters-row .left{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.filters-pill{display:inline-flex;align-items:center;gap:8px}
.filters-pill .count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:999px;background:#0ea44f;color:#fff;font-weight:800;font-size:12px;padding:0 7px}
.filters-clear{font-size:13px;color:var(--text);opacity:.75;text-decoration:underline}
.filters-clear:hover{opacity:1}

.filters-topbar{margin-top:14px}
.filters-topbar .card{padding:14px 14px}
.filters-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end}
.filters-grid label{display:block;font-weight:800;margin:0 0 6px 0}
.filters-grid input,.filters-grid select{width:100%}
.filters-grid .btn{white-space:nowrap}

/* Mobile quick search + filter button */
.filters-mobilebar{display:none;margin-top:14px}
.filters-mobilebar .bar{display:flex;gap:10px;align-items:center}
.filters-mobilebar input[type="text"]{flex:1}

/* Drawer */
#filterOverlay{position:fixed;inset:0;background:rgba(0,0,0,.35);opacity:0;pointer-events:none;transition:opacity .18s ease;z-index:9998}
#filterDrawer{position:fixed;top:0;right:0;height:100%;width:min(420px,92vw);background:#fff;z-index:9999;transform:translateX(110%);transition:transform .22s ease;box-shadow:-10px 0 30px rgba(0,0,0,.12);display:flex;flex-direction:column}
#filterDrawer .drawer-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border)}
#filterDrawer .drawer-head h3{margin:0;font-size:18px}
#filterDrawer .drawer-body{padding:14px 16px;overflow:auto}
#filterDrawer .drawer-body label{font-weight:800;margin-top:12px;display:block}
#filterDrawer .drawer-body input,#filterDrawer .drawer-body select{width:100%}
#filterDrawer .drawer-foot{padding:14px 16px;border-top:1px solid var(--border);display:flex;gap:10px;align-items:center;justify-content:space-between}
#filterDrawer .drawer-foot .btn{flex:1}
#filterDrawer .close-btn{border:0;background:transparent;font-size:22px;line-height:1;cursor:pointer;padding:6px 8px;border-radius:10px}
#filterDrawer .close-btn:hover{background:rgba(0,0,0,.06)}

body.filters-open #filterOverlay{opacity:1;pointer-events:auto}
body.filters-open #filterDrawer{transform:translateX(0)}

/* Responsive behavior */
@media (max-width: 980px){
  .filters-topbar{display:none}
  .filters-mobilebar{display:block}
}

/* =========================================================
   Compare icon + RFQ pill
   ========================================================= */
.product-card .product-thumb{position:relative;overflow:hidden;}
.product-card .product-thumb img{display:block;width:100%;height:100%;object-fit:contain;}

.product-card .thumb-badges{
  position:absolute;left:12px;top:12px;z-index:3;pointer-events:none;
}
.product-card .thumb-badges .pill.small{
  display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;
  font-weight:900;font-size:12px;letter-spacing:.3px;color:#0b6b33;
  background:rgba(255,255,255,.85);
  border:1px solid rgba(14,164,79,.35);
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.product-card .thumb-actions{
  position:absolute;right:12px;bottom:12px;z-index:3;
  display:flex;flex-direction:column;gap:8px;
}

.product-card .thumb-actions .icon-btn{
  width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:999px;text-decoration:none;color:var(--text);
  background:rgba(255,255,255,.88);
  border:1px solid rgba(0,0,0,.08);
  box-shadow:0 10px 25px rgba(0,0,0,.10);
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  transition:transform .14s ease, box-shadow .14s ease, opacity .14s ease;
  cursor:pointer;
}
.product-card .thumb-actions .icon-btn:hover{transform:translateY(-1px);box-shadow:0 14px 30px rgba(0,0,0,.14);}
.product-card .thumb-actions .icon-btn:active{transform:translateY(0);}
.product-card .thumb-actions .icon-btn svg{width:18px;height:18px;display:block;}

@media (min-width: 981px){
  .product-card .thumb-actions{opacity:0;transform:translateY(6px);transition:opacity .16s ease, transform .16s ease;}
  .product-card:hover .thumb-actions{opacity:1;transform:translateY(0);}
}

@media (max-width: 980px){
  .product-card .thumb-actions .icon-btn{width:38px;height:38px;}
  .product-card .thumb-actions .icon-btn svg{width:17px;height:17px;}
}

/* active state */
.icon-btn.compare.is-active{
  border-color: rgba(14,164,79,.45) !important;
  box-shadow: 0 14px 30px rgba(14,164,79,.18) !important;
}
</style>

<div class="breadcrumbs"><a href="<?php echo url('index.php'); ?>">Home</a> <span>›</span> Products</div>

<div class="page-head">
  <h1>All Products</h1>
  <p class="muted">RFQ-based catalog (no prices shown). Add items to your RFQ list and submit in one request.</p>

  <div class="filters-row">
    <div class="left">
      <div class="filters-pill">
        <button id="openFilters" class="btn secondary" type="button">
          Filters
          <?php if($filtersCount>0): ?><span class="count"><?php echo (int)$filtersCount; ?></span><?php endif; ?>
        </button>
      </div>
      <?php if($hasFilters): ?>
        <a class="filters-clear" href="<?php echo url('pages/products.php'); ?>">Clear all</a>
      <?php endif; ?>
    </div>

    <div class="muted" style="font-size:13px">
      Tip: For reagents/consumables, add the items you need and specify pack size/lot in RFQ notes.
    </div>
  </div>

  <!-- Desktop compact top filter bar -->
  <div class="filters-topbar">
    <div class="card">
      <form class="form" method="get" action="<?php echo url('pages/products.php'); ?>">
        <div class="filters-grid">
          <div>
            <label>Search</label>
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Model, brand, SKU...">
          </div>

          <div>
            <label>Category</label>
            <select name="category">
              <option value="0">All</option>
              <?php foreach($cats as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>" <?php echo ($category===(int)$c['id'])?'selected':''; ?>>
                  <?php echo e($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Brand</label>
            <select name="brand">
              <option value="">All</option>
              <?php foreach($brands as $b): ?>
                <option value="<?php echo e($b['brand']); ?>" <?php echo ($brand===$b['brand'])?'selected':''; ?>>
                  <?php echo e($b['brand']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Sort</label>
            <select name="sort">
              <option value="newest" <?php echo ($sort==='newest')?'selected':''; ?>>Newest</option>
              <option value="name_asc" <?php echo ($sort==='name_asc')?'selected':''; ?>>Name A–Z</option>
              <option value="name_desc" <?php echo ($sort==='name_desc')?'selected':''; ?>>Name Z–A</option>
            </select>
          </div>

          <div>
            <label>&nbsp;</label>
            <button class="btn" type="submit">Apply</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Mobile compact bar: quick search + Filters button -->
  <div class="filters-mobilebar">
    <div class="card" style="padding:12px">
      <form class="form" method="get" action="<?php echo url('pages/products.php'); ?>">
        <div class="bar">
          <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search devices, reagents, SKUs...">
          <input type="hidden" name="category" value="<?php echo (int)$category; ?>">
          <input type="hidden" name="brand" value="<?php echo e($brand); ?>">
          <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
          <button class="btn" type="submit">Search</button>
          <button class="btn secondary" type="button" id="openFiltersMobile">
            Filters<?php if($filtersCount>0): ?> <span class="count" style="margin-left:6px"><?php echo (int)$filtersCount; ?></span><?php endif; ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Drawer + overlay -->
<div id="filterOverlay"></div>

<aside id="filterDrawer" aria-hidden="true">
  <div class="drawer-head">
    <h3>Filters</h3>
    <button class="close-btn" type="button" id="closeFilters" aria-label="Close">✕</button>
  </div>

  <div class="drawer-body">
    <form class="form" method="get" action="<?php echo url('pages/products.php'); ?>" id="drawerFiltersForm">
      <label>Search</label>
      <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Model, brand, SKU...">

      <label>Category</label>
      <select name="category">
        <option value="0">All</option>
        <?php foreach($cats as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ($category===(int)$c['id'])?'selected':''; ?>>
            <?php echo e($c['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Brand</label>
      <select name="brand">
        <option value="">All</option>
        <?php foreach($brands as $b): ?>
          <option value="<?php echo e($b['brand']); ?>" <?php echo ($brand===$b['brand'])?'selected':''; ?>>
            <?php echo e($b['brand']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Sort</label>
      <select name="sort">
        <option value="newest" <?php echo ($sort==='newest')?'selected':''; ?>>Newest</option>
        <option value="name_asc" <?php echo ($sort==='name_asc')?'selected':''; ?>>Name A–Z</option>
        <option value="name_desc" <?php echo ($sort==='name_desc')?'selected':''; ?>>Name Z–A</option>
      </select>

      <div class="mt16 muted" style="font-size:13px;line-height:1.5">
        <strong>Tip:</strong> For reagents/consumables, add the items you need and specify your preferred pack size/lot in the RFQ notes.
      </div>
    </form>
  </div>

  <div class="drawer-foot">
    <?php if($hasFilters): ?>
      <a class="btn ghost" href="<?php echo url('pages/products.php'); ?>">Clear</a>
    <?php else: ?>
      <button class="btn ghost" type="button" id="closeFilters2">Close</button>
    <?php endif; ?>
    <button class="btn" type="submit" form="drawerFiltersForm">Apply</button>
  </div>
</aside>

<script>
(function(){
  const body = document.body;
  const openBtn = document.getElementById('openFilters');
  const openBtnM = document.getElementById('openFiltersMobile');
  const closeBtn = document.getElementById('closeFilters');
  const closeBtn2 = document.getElementById('closeFilters2');
  const overlay = document.getElementById('filterOverlay');

  function open(){
    body.classList.add('filters-open');
    document.getElementById('filterDrawer').setAttribute('aria-hidden','false');
  }
  function close(){
    body.classList.remove('filters-open');
    document.getElementById('filterDrawer').setAttribute('aria-hidden','true');
  }

  if(openBtn) openBtn.addEventListener('click', open);
  if(openBtnM) openBtnM.addEventListener('click', open);
  if(closeBtn) closeBtn.addEventListener('click', close);
  if(closeBtn2) closeBtn2.addEventListener('click', close);
  if(overlay) overlay.addEventListener('click', close);

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') close();
  });
})();
</script>

<section class="content">
  <?php if(!$products): ?>
    <div class="card p">No products found. Try a different search or clear filters.</div>
  <?php else: ?>
    <div class="products-grid">
      <?php foreach($products as $p): ?>
        <div class="product-card reveal">
          <div class="product-thumb">
            <img src="<?php echo asset($p['img'] ? ltrim($p['img'],'/') : 'assets/no-image.png'); ?>" alt="<?php echo e($p['name']); ?>">

            <div class="thumb-badges">
              <span class="pill small">RFQ</span>
            </div>

            <!-- ✅ IMPORTANT: button + class + data-product-id (NO href redirect) -->
            <div class="thumb-actions">
              <button class="icon-btn compare js-compare-toggle"
                      type="button"
                      title="Compare"
                      aria-label="Compare"
                      data-product-id="<?php echo (int)$p['id']; ?>">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M7 7h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M15 3l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M17 17H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M9 21l-4-4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="product-body">
            <div class="product-title"><?php echo e($p['name']); ?></div>
            <div class="muted">
              <?php echo e($p['brand'] ?: ''); ?>
              <?php if(!empty($p['sku'])): ?><span class="sep">•</span><?php echo e($p['sku']); ?><?php endif; ?>
            </div>
            <?php if(!empty($p['short_desc'])): ?>
              <div class="muted" style="font-size:13px;line-height:1.35"><?php echo e($p['short_desc']); ?></div>
            <?php endif; ?>

            <div class="product-actions">
              <a class="btn" href="<?php echo url('pages/product.php?id='.$p['id']); ?>">View</a>

              <?php if(current_user_id()): ?>
                <form action="<?php echo url('actions/cart.php'); ?>" method="post" style="margin:0">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="action" value="add">
                  <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                  <input type="hidden" name="qty" value="1">
                  <button class="btn secondary" type="submit">Add to RFQ</button>
                </form>
              <?php else: ?>
                <a class="btn secondary" href="<?php echo url('pages/login.php'); ?>">Login to RFQ</a>
              <?php endif; ?>

              <a class="btn ghost" href="<?php echo url('pages/inquiry.php?subject='.rawurlencode('RFQ: '.$p['name']).'&product_id='.$p['id']); ?>">Inquiry</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Toast UI -->
<style>
.toast-stack{position:fixed;right:16px;bottom:16px;display:flex;flex-direction:column;gap:10px;z-index:99999}
.toast{background:rgba(20,20,20,.92);color:#fff;padding:10px 12px;border-radius:14px;box-shadow:0 12px 35px rgba(0,0,0,.25);display:flex;gap:10px;align-items:center;max-width:min(360px,90vw)}
.toast .dot{width:10px;height:10px;border-radius:999px;background:#0ea44f;flex:0 0 10px}
.toast .msg{font-size:13px;line-height:1.35}
.toast .btnx{margin-left:auto;background:transparent;border:0;color:#fff;opacity:.85;cursor:pointer;font-size:18px;line-height:1}
.toast .btnx:hover{opacity:1}
</style>

<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<script>
(function(){
  function toast(msg){
    const stack = document.getElementById('toastStack');
    if(!stack) return;

    const t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = '<span class="dot"></span><div class="msg"></div><button class="btnx" aria-label="Close">✕</button>';
    t.querySelector('.msg').textContent = msg;

    const close = () => { if(t && t.parentNode) t.parentNode.removeChild(t); };
    t.querySelector('.btnx').addEventListener('click', close);

    stack.appendChild(t);
    setTimeout(close, 2600);
  }

  async function postForm(url, data){
    const body = new URLSearchParams();
    Object.keys(data).forEach(k => body.append(k, data[k]));
    const res = await fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body
    });
    return res;
  }

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.js-compare-toggle');
    if(!btn) return;

    const pid = btn.getAttribute('data-product-id');
    if(!pid) return;

    btn.disabled = true;

    try{
      const res = await postForm('<?php echo url("actions/compare_toggle.php"); ?>', {
        product_id: pid,
        csrf_token: '<?php echo e($csrfToken); ?>'
      });

      const text = await res.text();
      let json = null;
      try { json = JSON.parse(text); } catch(err) {}

      if(!res.ok || !json || !json.ok){
        toast('Could not update compare. Please try again.');
      }else{
        toast(json.added ? 'Added to Compare' : 'Removed from Compare');
        window.dispatchEvent(new CustomEvent('compare:count', { detail: { count: json.count } }));
        if(json.added) btn.classList.add('is-active');
        else btn.classList.remove('is-active');
      }
    }catch(err){
      toast('Network error. Please try again.');
    }finally{
      btn.disabled = false;
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
