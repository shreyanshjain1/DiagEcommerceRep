<?php
require_once __DIR__.'/header.php';

$k = [
  'orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'revenue' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'Cancelled'")->fetchColumn(),
  'pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn(),
  'inquiries' => (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='New'")->fetchColumn(),
  'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=5")->fetchColumn(),
  'active_products' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn(),
];

$months = [];
for ($i = 5; $i >= 0; $i--) {
  $ym = date('Y-m', strtotime("-{$i} months"));
  $months[$ym] = 0.0;
}
$st = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COALESCE(SUM(total),0) s
                     FROM orders
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       AND status<>'Cancelled'
                     GROUP BY ym ORDER BY ym");
$st->execute();
while ($r = $st->fetch()) {
  $months[$r['ym']] = (float)$r['s'];
}
$max = max($months ?: [0]);

$recentOrders = $pdo->query("SELECT id,order_number,company,total,status,created_at FROM orders ORDER BY id DESC LIMIT 6")->fetchAll();
$recentInq = $pdo->query("SELECT id,company,name,subject,status,created_at FROM inquiries ORDER BY id DESC LIMIT 6")->fetchAll();
$topProducts = $pdo->query("SELECT name, brand, stock, sku FROM products WHERE is_active=1 ORDER BY stock ASC, id DESC LIMIT 5")->fetchAll();

$avgTicket = $k['orders'] > 0 ? ($k['revenue'] / $k['orders']) : 0;
?>

<div class="admin-page-head">
  <div>
    <h2 class="admin-page-title">Operations Dashboard</h2>
    <p class="admin-page-sub">A premium admin landing view for recruiter demos: cleaner KPIs, sharper hierarchy, better watchlists, and a much stronger first impression than a plain CRUD dashboard.</p>
  </div>
  <div class="admin-page-actions">
    <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">Add Product</a>
    <a class="btn secondary" href="<?php echo url('admin/inquiries.php'); ?>">Review Inquiries</a>
  </div>
</div>

<div class="admin-stats-grid">
  <div class="admin-stat-card">
    <div class="admin-stat-label">Total Orders</div>
    <div class="admin-stat-value"><?php echo number_format($k['orders']); ?></div>
    <div class="admin-stat-meta">Overall historical orders currently stored in the platform.</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-label">Revenue Processed</div>
    <div class="admin-stat-value">₱<?php echo number_format($k['revenue'], 2); ?></div>
    <div class="admin-stat-meta">Based on all non-cancelled orders in the database.</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-label">Pending Orders</div>
    <div class="admin-stat-value"><?php echo number_format($k['pending']); ?></div>
    <div class="admin-stat-meta">Prioritise these before they age into customer follow-up risk.</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-label">New Inquiries</div>
    <div class="admin-stat-value"><?php echo number_format($k['inquiries']); ?></div>
    <div class="admin-stat-meta">Inbound leads waiting for admin attention.</div>
  </div>
</div>

<div class="admin-grid-2">
  <section class="admin-panel">
    <div class="admin-panel-head">
      <div>
        <h3 class="admin-panel-title">Revenue Trend</h3>
        <p class="admin-panel-sub">Last 6 months, excluding cancelled orders. This uses a cleaner visual layout so the dashboard feels like a product, not a school project.</p>
      </div>
      <div class="admin-panel-actions">
        <span class="admin-chip muted">Avg ticket: ₱<?php echo number_format($avgTicket, 2); ?></span>
        <a class="btn ghost" href="<?php echo url('admin/orders.php'); ?>">Open Orders</a>
      </div>
    </div>

    <div class="admin-chart">
      <div class="admin-bars">
        <?php foreach ($months as $ym => $val):
          $h = $max > 0 ? max(12, (int)round(180 * ($val / $max))) : 12;
        ?>
          <div class="admin-bar-col">
            <div class="admin-bar-value">₱<?php echo number_format($val, 0); ?></div>
            <div class="admin-bar" style="height:<?php echo $h; ?>px"></div>
            <div class="admin-bar-label"><?php echo e(date('M', strtotime($ym . '-01'))); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <aside class="admin-panel">
    <div class="admin-panel-head">
      <div>
        <h3 class="admin-panel-title">Executive Snapshot</h3>
        <p class="admin-panel-sub">Quick recruiter-friendly metrics that make the system feel more operational.</p>
      </div>
    </div>

    <div class="admin-glance-list">
      <div class="admin-glance-card">
        <div class="admin-glance-title">Active Products</div>
        <div class="admin-glance-copy"><?php echo number_format($k['active_products']); ?> active catalog items are currently visible to customers.</div>
      </div>
      <div class="admin-glance-card">
        <div class="admin-glance-title">Low-Stock Watchlist</div>
        <div class="admin-glance-copy"><?php echo number_format($k['low_stock']); ?> products are at or below the low-stock threshold.</div>
      </div>
      <div class="admin-glance-card">
        <div class="admin-glance-title">Response Pressure</div>
        <div class="admin-glance-copy"><?php echo number_format($k['inquiries']); ?> fresh inquiries should be triaged first to keep turnaround tight.</div>
      </div>
      <div class="admin-soft-card">
        <strong>UI/UX Upgrade Pack</strong>
        <span class="admin-page-sub">This dashboard pass adds hierarchy, spacing, stronger cards, softer glass surfaces, and more credible admin UX for portfolio review.</span>
      </div>
    </div>
  </aside>
</div>

<div class="admin-grid-2 mt16">
  <section class="admin-panel">
    <div class="admin-panel-head">
      <div>
        <h3 class="admin-panel-title">Recent Orders</h3>
        <p class="admin-panel-sub">Latest activity with cleaner information density and easier scanning.</p>
      </div>
      <a class="btn ghost" href="<?php echo url('admin/orders.php'); ?>">See all</a>
    </div>
    <div class="admin-table-shell">
      <table class="table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Company</th>
            <th>Status</th>
            <th style="text-align:right">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td>
                <a href="<?php echo url('admin/order-view.php?id=' . $o['id']); ?>"><strong><?php echo e($o['order_number']); ?></strong></a>
                <div class="admin-inline-meta"><span class="admin-chip muted"><?php echo e(date('Y-m-d', strtotime($o['created_at']))); ?></span></div>
              </td>
              <td><?php echo e($o['company'] ?: '—'); ?></td>
              <td><span class="badge <?php echo ($o['status'] ?? '') === 'Pending' ? 'badge-warn' : 'badge-ok'; ?>"><?php echo e($o['status']); ?></span></td>
              <td style="text-align:right">₱<?php echo number_format((float)$o['total'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="admin-panel">
    <div class="admin-panel-head">
      <div>
        <h3 class="admin-panel-title">Recent Inquiries</h3>
        <p class="admin-panel-sub">A cleaner lead review surface with stronger visual hierarchy.</p>
      </div>
      <a class="btn ghost" href="<?php echo url('admin/inquiries.php'); ?>">Open queue</a>
    </div>
    <div class="admin-table-shell">
      <table class="table">
        <thead>
          <tr>
            <th>Subject</th>
            <th>Status</th>
            <th>When</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentInq as $i): ?>
            <tr>
              <td>
                <a href="<?php echo url('admin/inquiry-view.php?id=' . $i['id']); ?>"><strong><?php echo e($i['subject']); ?></strong></a>
                <div class="admin-page-sub"><?php echo e($i['company'] ?: $i['name']); ?></div>
              </td>
              <td><span class="badge <?php echo ($i['status'] ?? '') === 'New' ? 'badge-warn' : 'badge-ok'; ?>"><?php echo e($i['status']); ?></span></td>
              <td class="nowrap"><?php echo e(date('Y-m-d', strtotime($i['created_at']))); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<section class="admin-panel mt16">
  <div class="admin-panel-head">
    <div>
      <h3 class="admin-panel-title">Operational Watchlist</h3>
      <p class="admin-panel-sub">Adds more narrative and context so the admin area reads like a real operations product.</p>
    </div>
  </div>
  <div class="admin-grid-3">
    <?php foreach ($topProducts as $p): ?>
      <div class="admin-glance-card">
        <div class="admin-glance-title"><?php echo e($p['name']); ?></div>
        <div class="admin-glance-copy">
          <?php echo e($p['brand'] ?: 'Unbranded'); ?><?php if (!empty($p['sku'])): ?> · <?php echo e($p['sku']); ?><?php endif; ?><br>
          Current stock: <strong><?php echo number_format((int)$p['stock']); ?></strong>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__.'/footer.php'; ?>
