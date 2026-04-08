<?php require_once __DIR__.'/header.php';

$oid = (int)($_GET['id'] ?? 0);
$ord = $pdo->prepare("SELECT * FROM orders WHERE id=:id");
$ord->execute([':id'=>$oid]);
$o = $ord->fetch();
if(!$o){ echo '<div class="alert error">Order not found.</div>'; require_once __DIR__.'/footer.php'; exit; }

$items = $pdo->prepare("SELECT oi.qty,oi.unit_price,p.name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:id");
$items->execute([':id'=>$oid]);
$rows = $items->fetchAll();
$subtotal = $o['subtotal']; $ship = $o['shipping_fee']; $total = $o['total'];
?>
<h1 class="m0">Order</h1>
<p class="mt8"><span class="badge badge-ok"><?php echo e($o['order_number']); ?></span></p>

<div class="order-wrap mt12">
  <!-- Left -->
  <div class="order-card">
    <div class="p">
      <h2 class="order-title">Order <?php echo e($o['order_number']); ?></h2>
      <p class="order-sub">Status: <strong><?php echo e($o['status']); ?></strong> • Date: <?php echo e($o['created_at']); ?></p>

      <div class="grid cols-2">
        <div class="card">
          <div class="p">
            <h3 class="m0">Customer</h3>
            <p class="mt8">
              <strong><?php echo e($o['company'] ?: '—'); ?></strong><br>
              <?php echo e($o['name']); ?><br>
              <?php echo e($o['email']); ?><br>
              <?php echo e($o['phone']); ?>
            </p>
            <h3 class="mt16 m0">Shipping</h3>
            <p class="mt8">
              <?php echo e($o['address_line1']); ?><br>
              <?php echo e($o['address_line2']); ?><br>
              <?php echo e($o['city']); ?>, <?php echo e($o['province']); ?> <?php echo e($o['postal_code']); ?>
            </p>
          </div>
        </div>

        <div class="card total-box">
          <div class="p">
            <h3>Totals</h3>
            <p class="m0">Subtotal: ₱<?php echo number_format($subtotal,2); ?></p>
            <p class="m0">Shipping: ₱<?php echo number_format($ship,2); ?></p>
            <p class="sum mt8">Total: ₱<?php echo number_format($total,2); ?></p>
            <p class="mt12">
              <a class="btn" href="<?php echo url('admin/order-invoice.php?id='.$oid); ?>" target="_blank">View / Print Invoice</a>
              <a class="btn ghost" href="<?php echo url('admin/orders.php'); ?>">Back to Orders</a>
            </p>
          </div>
        </div>
      </div>

      <h3 class="mt16">Items</h3>
      <table class="table">
        <tr><th>Product</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo e($r['name'] ?? 'Product'); ?></td>
          <td><?php echo e($r['qty']); ?></td>
          <td>₱<?php echo number_format($r['unit_price'],2); ?></td>
          <td>₱<?php echo number_format($r['unit_price']*$r['qty'],2); ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <!-- Right (quick actions) -->
  <div class="card">
    <div class="p">
      <h3 class="m0">Quick Actions</h3>
      <form class="mt12" action="<?php echo url('admin_actions/order.php'); ?>" method="post">
        <?php require_once __DIR__.'/../config/csrf.php'; csrf_field(); ?>
        <input type="hidden" name="order_id" value="<?php echo e($o['id']); ?>">
        <label>Status</label>
        <select name="status">
          <?php foreach(['Pending','Processing','Shipped','Completed','Cancelled'] as $st): ?>
          <option value="<?php echo e($st); ?>" <?php echo $o['status']===$st?'selected':''; ?>><?php echo e($st); ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn mt12" type="submit">Update Status</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
