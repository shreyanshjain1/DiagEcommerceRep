<?php
require_once __DIR__.'/header.php';

$k = [
  'rfqs' => (int)$pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn(),
  'submitted' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='submitted'")->fetchColumn(),
  'quoted' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='quoted'")->fetchColumn(),
  'closed' => (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='closed'")->fetchColumn(),
  'quoted_value' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM quotes WHERE status='quoted'")->fetchColumn(),
  'new_inquiries' => (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='New'")->fetchColumn(),
  'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=5")->fetchColumn(),
];

$months = [];
for($i=5;$i>=0;$i--){
  $ym = date('Y-m', strtotime("-{$i} months"));
  $months[$ym] = 0;
}
$st = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c
                    FROM quotes
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY ym ORDER BY ym");
$st->execute();
while($r=$st->fetch()){
  $months[$r['ym']] = (int)$r['c'];
}
$max = max($months ?: [0]);

$recentRfqs = $pdo->query("SELECT id,quote_number,company,total,status,created_at FROM quotes ORDER BY updated_at DESC LIMIT 8")->fetchAll();
$recentInq = $pdo->query("SELECT id,company,name,subject,status,created_at FROM inquiries ORDER BY id DESC LIMIT 8")->fetchAll();
?>

<h1>Dashboard</h1>
<div class="muted" style="margin-top:-4px;margin-bottom:14px">RFQ-first operations dashboard for catalogue requests, quotation work, and inquiry handling.</div>

<div class="admin-stats">
  <div class="stat"><div class="k">RFQs</div><div class="v"><?php echo number_format($k['rfqs']); ?></div></div>
  <div class="stat"><div class="k">Submitted</div><div class="v"><?php echo number_format($k['submitted']); ?></div></div>
  <div class="stat"><div class="k">Quoted</div><div class="v"><?php echo number_format($k['quoted']); ?></div></div>
  <div class="stat"><div class="k">Closed</div><div class="v"><?php echo number_format($k['closed']); ?></div></div>
  <div class="stat"><div class="k">Quoted Value</div><div class="v">₱<?php echo number_format($k['quoted_value'],2); ?></div></div>
  <div class="stat"><div class="k">New Inquiries</div><div class="v"><?php echo number_format($k['new_inquiries']); ?></div></div>
  <div class="stat"><div class="k">Low Stock</div><div class="v"><?php echo number_format($k['low_stock']); ?></div></div>
</div>

<div class="grid" style="grid-template-columns: 1.35fr .65fr; gap:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div>
        <div style="font-weight:950;font-size:16px">RFQ volume (last 6 months)</div>
        <div class="muted">Tracks quotation requests received by month</div>
      </div>
      <a class="btn ghost" href="<?php echo url('admin/rfqs.php'); ?>">Open RFQs</a>
    </div>
    <div style="height:220px;margin-top:14px">
      <svg viewBox="0 0 600 220" width="100%" height="220" role="img" aria-label="RFQ volume chart">
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
          <rect x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $barW; ?>" height="<?php echo $h; ?>" rx="10" fill="rgba(37,99,235,.25)" stroke="rgba(37,99,235,.55)" />
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
      <a class="btn" href="<?php echo url('admin/rfqs.php?status=submitted'); ?>">Review submitted RFQs</a>
      <a class="btn secondary" href="<?php echo url('admin/products-new.php'); ?>">Add new product</a>
      <a class="btn ghost" href="<?php echo url('admin/inquiries.php'); ?>">Review inquiries</a>
    </div>
    <div style="margin-top:16px" class="muted">
      Tip: keep RFQ statuses updated so your pipeline stays clean and customers get faster follow-ups.
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px">
  <div class="card p">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div style="font-weight:950">Recent RFQs</div>
      <a class="link" href="<?php echo url('admin/rfqs.php'); ?>">Open</a>
    </div>
    <div style="overflow:auto;margin-top:10px">
      <table class="table">
        <thead><tr><th>RFQ</th><th>Company</th><th>Status</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php foreach($recentRfqs as $r): ?>
            <tr>
              <td><a href="<?php echo url('admin/rfq-view.php?id='.$r['id']); ?>"><?php echo e($r['quote_number']); ?></a><br><span class="muted" style="font-size:12px"><?php echo e(date('Y-m-d', strtotime($r['created_at']))); ?></span></td>
              <td><?php echo e($r['company'] ?: '—'); ?></td>
              <td><span class="badge"><?php echo e(ucfirst($r['status'])); ?></span></td>
              <td style="text-align:right">₱<?php echo number_format((float)$r['total'],2); ?></td>
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

<div class="card p" style="margin-top:16px">
  <div style="font-weight:950;margin-bottom:8px">Legacy order module</div>
  <div class="muted">The legacy online order flow remains in the codebase for backward compatibility and historical records, but the live customer journey is RFQ-first. Use the RFQ module as the primary operational path.</div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
