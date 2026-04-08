<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';

$uid = current_user_id();
if(!$uid){
  echo '<div class="alert error">Please login to view your workspace.</div>';
  require_once __DIR__.'/../includes/footer.php';
  exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, phone, company, created_at FROM users WHERE id=:id");
$stmt->execute([':id'=>$uid]);
$user = $stmt->fetch();

$stats = [
  'total_rfqs' => 0,
  'draft_rfqs' => 0,
  'submitted_rfqs' => 0,
  'quoted_rfqs' => 0,
  'closed_rfqs' => 0,
  'quoted_value' => 0,
  'line_items' => 0,
];

$statsSql = "SELECT
    COUNT(*) AS total_rfqs,
    SUM(CASE WHEN q.status='draft' THEN 1 ELSE 0 END) AS draft_rfqs,
    SUM(CASE WHEN q.status='submitted' THEN 1 ELSE 0 END) AS submitted_rfqs,
    SUM(CASE WHEN q.status='quoted' THEN 1 ELSE 0 END) AS quoted_rfqs,
    SUM(CASE WHEN q.status='closed' THEN 1 ELSE 0 END) AS closed_rfqs,
    COALESCE(SUM(CASE WHEN q.status='quoted' THEN q.total ELSE 0 END),0) AS quoted_value,
    COALESCE(SUM(qi.qty),0) AS line_items
  FROM quotes q
  LEFT JOIN quote_items qi ON qi.quote_id = q.id
  WHERE q.user_id = :uid";
$statsStmt = $pdo->prepare($statsSql);
$statsStmt->execute([':uid' => $uid]);
$statsRow = $statsStmt->fetch();
if ($statsRow) {
  $stats = array_merge($stats, $statsRow);
}

$recentStmt = $pdo->prepare(
  "SELECT q.id, q.quote_number, q.status, q.total, q.valid_until, q.updated_at, q.created_at,
          COUNT(qi.id) AS item_count,
          COALESCE(SUM(qi.qty),0) AS total_qty
   FROM quotes q
   LEFT JOIN quote_items qi ON qi.quote_id = q.id
   WHERE q.user_id = :uid
   GROUP BY q.id
   ORDER BY q.updated_at DESC
   LIMIT 5"
);
$recentStmt->execute([':uid' => $uid]);
$recentRfqs = $recentStmt->fetchAll();

$recentProductsStmt = $pdo->prepare(
  "SELECT p.id, p.name, p.slug, p.brand, p.sku,
          COUNT(*) AS request_count,
          MAX(q.updated_at) AS last_requested_at
   FROM quote_items qi
   INNER JOIN quotes q ON q.id = qi.quote_id
   LEFT JOIN products p ON p.id = qi.product_id
   WHERE q.user_id = :uid AND p.id IS NOT NULL
   GROUP BY p.id
   ORDER BY last_requested_at DESC, request_count DESC
   LIMIT 4"
);
$recentProductsStmt->execute([':uid' => $uid]);
$recentProducts = $recentProductsStmt->fetchAll();

function workspace_stat($value): string {
  if (is_numeric($value)) {
    return number_format((float)$value, 0);
  }
  return (string)$value;
}
?>

<div class="workspace-hero">
  <div>
    <span class="workspace-kicker">Customer workspace</span>
    <h1>Your B2B RFQ Workspace</h1>
    <p>Track quotations, monitor response progress, and jump back into products that matter to your team.</p>
    <div class="workspace-actions">
      <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse Products</a>
      <a class="btn secondary" href="<?php echo url('pages/quotes.php'); ?>">Open My RFQs</a>
      <a class="btn ghost" href="<?php echo url('actions/auth.php'); ?>?action=logout">Logout</a>
    </div>
  </div>
  <div class="workspace-hero-card">
    <div class="workspace-hero-top">
      <span class="status-chip">Account</span>
      <span class="status-chip muted"><?php echo e($user['company'] ?: 'Customer Account'); ?></span>
    </div>
    <div class="workspace-hero-grid">
      <div>
        <div class="workspace-mini-label">Primary contact</div>
        <div class="workspace-mini-value"><?php echo e($user['name']); ?></div>
        <div class="workspace-mini-sub"><?php echo e($user['email']); ?></div>
      </div>
      <div>
        <div class="workspace-mini-label">Phone</div>
        <div class="workspace-mini-value"><?php echo e($user['phone'] ?: 'Not provided'); ?></div>
        <div class="workspace-mini-sub">Member since <?php echo e(date('M d, Y', strtotime((string)$user['created_at']))); ?></div>
      </div>
    </div>
  </div>
</div>

<div class="workspace-stats-grid">
  <div class="workspace-stat-card">
    <div class="workspace-stat-label">Total RFQs</div>
    <div class="workspace-stat-value"><?php echo workspace_stat($stats['total_rfqs']); ?></div>
    <div class="workspace-stat-sub">All requests created under your account</div>
  </div>
  <div class="workspace-stat-card">
    <div class="workspace-stat-label">Quoted RFQs</div>
    <div class="workspace-stat-value"><?php echo workspace_stat($stats['quoted_rfqs']); ?></div>
    <div class="workspace-stat-sub">Requests currently carrying a quotation</div>
  </div>
  <div class="workspace-stat-card">
    <div class="workspace-stat-label">Quoted Value</div>
    <div class="workspace-stat-value">₱<?php echo number_format((float)$stats['quoted_value'], 0); ?></div>
    <div class="workspace-stat-sub">Total value of active quoted requests</div>
  </div>
  <div class="workspace-stat-card">
    <div class="workspace-stat-label">Requested Units</div>
    <div class="workspace-stat-value"><?php echo workspace_stat($stats['line_items']); ?></div>
    <div class="workspace-stat-sub">Combined quantity requested across RFQs</div>
  </div>
</div>

<div class="workspace-section-grid">
  <section class="workspace-panel workspace-panel-lg">
    <div class="workspace-section-head">
      <div>
        <h2>Recent RFQ Activity</h2>
        <p>Your latest quotation requests and current progress.</p>
      </div>
      <a class="btn secondary" href="<?php echo url('pages/quotes.php'); ?>">View All</a>
    </div>

    <?php if(!$recentRfqs): ?>
      <div class="workspace-empty">
        <strong>No RFQs yet.</strong>
        <p>Start by browsing products and adding items to your RFQ list.</p>
        <a class="btn" href="<?php echo url('pages/products.php'); ?>">Start an RFQ</a>
      </div>
    <?php else: ?>
      <div class="workspace-rfq-list">
        <?php foreach($recentRfqs as $rfq): ?>
          <article class="workspace-rfq-item">
            <div class="workspace-rfq-main">
              <div class="workspace-rfq-topline">
                <strong><?php echo e($rfq['quote_number']); ?></strong>
                <span class="status-chip status-<?php echo e($rfq['status']); ?>"><?php echo e(ucfirst($rfq['status'])); ?></span>
              </div>
              <div class="workspace-rfq-meta">
                <span><?php echo (int)$rfq['item_count']; ?> lines</span>
                <span><?php echo (int)$rfq['total_qty']; ?> units requested</span>
                <span>Updated <?php echo e(date('M d, Y', strtotime((string)$rfq['updated_at']))); ?></span>
                <?php if(!empty($rfq['valid_until'])): ?><span>Valid until <?php echo e(date('M d, Y', strtotime((string)$rfq['valid_until']))); ?></span><?php endif; ?>
              </div>
            </div>
            <div class="workspace-rfq-side">
              <div class="workspace-rfq-total">₱<?php echo number_format((float)$rfq['total'], 2); ?></div>
              <div class="workspace-rfq-actions">
                <a class="btn secondary" href="<?php echo url('pages/rfq-view.php?id='.$rfq['id']); ?>">Open RFQ</a>
                <a class="btn ghost" href="<?php echo url('actions/quote.php?action=reorder&id='.$rfq['id']); ?>">Reorder</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <aside class="workspace-panel workspace-panel-sm">
    <div class="workspace-section-head compact">
      <div>
        <h2>Account Snapshot</h2>
        <p>Quick view of your current request pipeline.</p>
      </div>
    </div>

    <div class="pipeline-stack">
      <div class="pipeline-row"><span>Draft</span><strong><?php echo (int)$stats['draft_rfqs']; ?></strong></div>
      <div class="pipeline-row"><span>Submitted</span><strong><?php echo (int)$stats['submitted_rfqs']; ?></strong></div>
      <div class="pipeline-row"><span>Quoted</span><strong><?php echo (int)$stats['quoted_rfqs']; ?></strong></div>
      <div class="pipeline-row"><span>Closed</span><strong><?php echo (int)$stats['closed_rfqs']; ?></strong></div>
    </div>

    <div class="workspace-divider"></div>

    <div class="workspace-quick-links">
      <a href="<?php echo url('pages/quotes.php'); ?>">
        <strong>Quotation Center</strong>
        <span>Review all RFQs and customer-side actions</span>
      </a>
      <a href="<?php echo url('pages/cart.php'); ?>">
        <strong>RFQ Cart</strong>
        <span>Review your pending request basket</span>
      </a>
      <a href="<?php echo url('pages/inquiry.php'); ?>">
        <strong>Send an Inquiry</strong>
        <span>Ask for support, specs, or commercial details</span>
      </a>
    </div>
  </aside>
</div>

<section class="workspace-panel" style="margin-top:18px;">
  <div class="workspace-section-head">
    <div>
      <h2>Recently Requested Products</h2>
      <p>Products your account has requested most recently.</p>
    </div>
    <a class="btn secondary" href="<?php echo url('pages/products.php'); ?>">Explore Catalog</a>
  </div>

  <?php if(!$recentProducts): ?>
    <div class="workspace-empty compact">
      <p>No recent product requests yet.</p>
    </div>
  <?php else: ?>
    <div class="workspace-product-grid">
      <?php foreach($recentProducts as $p): ?>
        <a class="workspace-product-card" href="<?php echo url('pages/product.php?slug='.rawurlencode((string)$p['slug'])); ?>">
          <div class="workspace-product-brand"><?php echo e($p['brand'] ?: 'Product'); ?></div>
          <div class="workspace-product-name"><?php echo e($p['name']); ?></div>
          <div class="workspace-product-meta">
            <span><?php echo e($p['sku'] ?: 'No SKU'); ?></span>
            <span><?php echo (int)$p['request_count']; ?> request events</span>
          </div>
          <div class="workspace-product-foot">Last requested <?php echo e(date('M d, Y', strtotime((string)$p['last_requested_at']))); ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
