<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/settings.php';

$uid = current_user_id();
if(!$uid){
  echo '<div class="card p">Please <a class="btn" href="'.url('pages/login.php').'">login</a> to create an RFQ.</div>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$cart_id = $pdo->prepare("SELECT id FROM carts WHERE user_id=:u ORDER BY id DESC LIMIT 1");
$cart_id->execute([':u'=>$uid]);
$cid = (int)$cart_id->fetchColumn();

$items = [];
if($cid){
  $st = $pdo->prepare("SELECT ci.id AS cart_item_id, ci.qty, p.id AS product_id, p.name, p.sku, p.brand,
            (SELECT image_path FROM product_images i WHERE i.product_id=p.id ORDER BY i.sort_order ASC LIMIT 1) AS img
          FROM cart_items ci
          JOIN products p ON p.id=ci.product_id
          WHERE ci.cart_id=:c
          ORDER BY ci.id DESC");
  $st->execute([':c'=>$cid]);
  $items = $st->fetchAll();
}
?>

<div class="page-head">
  <h1>Your RFQ List</h1>
  <p class="muted">Review items, set quantities, add notes, then submit your RFQ. Our team will send back a formal quotation.</p>
</div>

<?php if(!$items): ?>
  <div class="card p">
    Your RFQ list is empty. <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse products</a>
  </div>
<?php else: ?>
  <div class="grid" style="grid-template-columns:1.2fr .8fr;gap:16px;align-items:start">
    <div class="card p">
      <table class="table">
        <tr>
          <th style="width:72px"></th>
          <th>Product</th>
          <th style="width:160px">Qty</th>
          <th style="width:90px"></th>
        </tr>
        <?php foreach($items as $it): ?>
          <tr>
            <td>
              <img style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb"
                   src="<?php echo asset($it['img'] ? ltrim($it['img'],'/') : 'assets/no-image.png'); ?>" alt="">
            </td>
            <td>
              <strong><?php echo e($it['name']); ?></strong>
              <div class="muted"><?php echo e($it['brand'] ?: ''); ?><?php if(!empty($it['sku'])): ?> • <?php echo e($it['sku']); ?><?php endif; ?></div>
            </td>
            <td>
              <form action="<?php echo url('actions/cart.php'); ?>" method="post" style="display:flex;gap:8px;align-items:center">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="cart_item_id" value="<?php echo (int)$it['cart_item_id']; ?>">
                <input type="number" name="qty" min="1" value="<?php echo (int)$it['qty']; ?>" style="max-width:90px">
                <button class="btn secondary" type="submit">Update</button>
              </form>
            </td>
            <td>
              <form action="<?php echo url('actions/cart.php'); ?>" method="post">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="cart_item_id" value="<?php echo (int)$it['cart_item_id']; ?>">
                <button class="btn ghost" type="submit" onclick="return confirm('Remove this item from RFQ?')">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="card p">
      <h3 class="m0">Submit RFQ</h3>
      <form class="form mt16" action="<?php echo url('actions/quote.php'); ?>" method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="submit">

        <label>Notes (optional)</label>
        <textarea name="notes" rows="6" placeholder="Tell us what you need (pack sizes, preferred brands, delivery location, lead time, etc.)"></textarea>

        <div class="mt16" style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn" type="submit">Submit RFQ to Admin</button>
          <button class="btn secondary" type="submit" name="save_draft" value="1">Save Draft</button>
        </div>

        <div class="mt16 muted" style="font-size:13px;line-height:1.55">
          When you submit, the RFQ is emailed to all admin accounts and recorded in your profile under <strong>My RFQs</strong>.
        </div>
      </form>

      <?php
        $wa = (string)setting('contact_whatsapp', '09453462354');
        $wa_link = wa_link($wa, 'Hi! I would like to request a quotation. My RFQ is in the website (Pharmastar Diagnostics).');
      ?>
      <div class="mt16">
        <a class="btn secondary" target="_blank" rel="noopener" href="<?php echo e($wa_link); ?>">Chat on WhatsApp</a>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
