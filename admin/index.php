<?php
require_once __DIR__.'/header.php';

// KPIs
$k = [
  'orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'revenue' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'Cancelled'")->fetchColumn(),
  'pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn(),
  'inquiries' => (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='New'")->fetchColumn(),
  'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=5")->fetchColumn(),
];

// Revenue by month (last 6 months incl current)
$months = [];
for($i=5;$i>=0;$i--){
  $ym = date('Y-m', strtotime("-{$i} months"));
  $months[$ym] = 0.0;
}
$st = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COALESCE(SUM(total),0) s
                    FROM orders
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      AND status<>'Cancelled'
                    GROUP BY ym ORDER BY ym");
$st->execute();
while($r=$st->fetch()){
  $months[$r['ym']] = (float)$r['s'];
}
$max = max($months ?: [0]);

$recentOrders = $pdo->query("SELECT id,order_number,company,total,status,created_at FROM orders ORDER BY id DESC LIMIT 8")->fetchAll();
$recentInq = $pdo->query("SELECT id,company,name,subject,status,created_at FROM inquiries ORDER BY id DESC LIMIT 8")->fetchAll();
?>

<h1>Dashboard</h1>

<div class="admin-stats">
  <div class="stat"><div class="k">Orders</div><div class="v"><?php echo number_format($k['orders']); ?></div></div>
  <div class="stat"><div class="k">Revenue</div><div class="v">₱<?php echo number_format($k['revenue'],2); ?></div></div>
  <div class="stat"><div class="k">Pending</div><div class="v"><?php echo number_format($k['pending']); ?></div></div>
  <div class="stat"><div class="k">New Inquiries</div><div class="v"><?php echo number_format($k['inquiries']); ?></div></div>
  <div class="stat"><div class="k">Low Stock</div><div class="v"><?php echo number_format($k['low_stock']); ?></div></div>
</div>

<div class="grid" style="grid-template-columns: 1.35fr .65fr; gap:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div>
        <div style="font-weight:950;font-size:16px">Revenue trend (last 6 months)</div>
        <div class="muted">Excludes cancelled orders</div>
      </div>
      <a class="btn ghost" href="<?php echo url('admin/orders.php'); ?>">View orders</a>
    </div>
    <div style="height:220px;margin-top:14px">
      <svg viewBox="0 0 600 220" width="100%" height="220" role="img" aria-label="Revenue chart">
        <line x1="20" y1="200" x2="580" y2="200" stroke="rgba(2,6,23,.15)" />
        <?php
          $i=0;
          $count = count($months);
          $barW = (560/($count*1.0)) - 14;
          foreach($months as $ym=>$val):
            $x = 30 + $i*(560/$count);
            $h = $max>0 ? (160 * ($val/$max)) : 0;
            $y = 200 - $h;
        ?>
          <rect x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $barW; ?>" height="<?php echo $h; ?>" rx="10" fill="rgba(22,163,74,.35)" stroke="rgba(22,163,74,.65)" />
          <text x="<?php echo $x + ($barW/2); ?>" y="214" text-anchor="middle" font-size="11" fill="rgba(2,6,23,.55)"><?php echo e(date('M', strtotime($ym.'-01'))); ?></text>
        <?php $i++; endforeach; ?>
      </svg>
    </div>
  </div>

  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div style="font-weight:950;font-size:16px">Quick actions</div>
    </div>
    <div style="display:grid;gap:10px;margin-top:12px">
      <a class="btn" href="<?php echo url('admin/products-new.php'); ?>">Add new product</a>
      <a class="btn secondary" href="<?php echo url('admin/products.php'); ?>">Manage products</a>
      <a class="btn ghost" href="<?php echo url('admin/inquiries.php'); ?>">Review inquiries</a>
    </div>
    <div style="margin-top:16px" class="muted">
      Tip: Keep stock updated to avoid quotation delays.
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div style="font-weight:950">Recent orders</div>
      <a class="link" href="<?php echo url('admin/orders.php'); ?>">Open</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>Order</th><th>Company</th><th>Status</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php foreach($recentOrders as $o): ?>
            <tr>
              <td><a href="<?php echo url('admin/order-view.php?id='.$o['id']); ?>"><?php echo e($o['order_number']); ?></a><br><span class="muted" style="font-size:12px"><?php echo e(date('Y-m-d', strtotime($o['created_at']))); ?></span></td>
              <td><?php echo e($o['company'] ?: '—'); ?></td>
              <td><span class="badge"><?php echo e($o['status']); ?></span></td>
              <td style="text-align:right">₱<?php echo number_format((float)$o['total'],2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div style="font-weight:950">Recent inquiries</div>
      <a class="link" href="<?php echo url('admin/inquiries.php'); ?>">Open</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>Subject</th><th>Status</th><th>When</th></tr></thead>
        <tbody>
          <?php foreach($recentInq as $i): ?>
            <tr>
              <td><a href="<?php echo url('admin/inquiry-view.php?id='.$i['id']); ?>"><?php echo e($i['subject']); ?></a><br><span class="muted" style="font-size:12px"><?php echo e($i['company'] ?: $i['name']); ?></span></td>
              <td><span class="badge"><?php echo e($i['status']); ?></span></td>
              <td class="muted"><?php echo e(date('Y-m-d', strtotime($i['created_at']))); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
