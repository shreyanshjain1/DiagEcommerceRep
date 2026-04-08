<?php require_once __DIR__.'/header.php'; require_once __DIR__.'/../config/csrf.php';

$orders = $pdo->query("SELECT id,order_number,status,total,created_at,name,email FROM orders ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>
<h1>Orders</h1>
<table class="table">
  <tr><th>Order #</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th><th>Action</th></tr>
  <?php foreach($orders as $o): ?>
  <tr>
    <td><?php echo e($o['order_number']); ?></td>
    <td><?php echo e($o['name']); ?><br><span class="tag"><?php echo e($o['email']); ?></span></td>
    <td><?php echo e($o['status']); ?></td>
    <td>₱<?php echo number_format($o['total'],2); ?></td>
    <td><?php echo e($o['created_at']); ?></td>
    <td>
  <form action="<?php echo url('admin_actions/order.php'); ?>" method="post" style="display:flex;gap:6px;align-items:center">
    <?php csrf_field(); ?>
    <input type="hidden" name="order_id" value="<?php echo e($o['id']); ?>">
    <select name="status">
      <?php foreach(['Pending','Processing','Shipped','Completed','Cancelled'] as $st): ?>
        <option value="<?php echo e($st); ?>" <?php echo $o['status']===$st?'selected':''; ?>><?php echo e($st); ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Update</button>
    <a class="btn" href="<?php echo url('admin/order-view.php?id='.$o['id']); ?>">Open</a>
  </form>
</td>

  </tr>
  <?php endforeach; ?>
</table>
<?php require_once __DIR__.'/footer.php'; ?>
