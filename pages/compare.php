<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/csrf.php';

/**
 * Keep GET toggle support (in case other pages still use it),
 * but users won't hit this from products page anymore (AJAX).
 */
if(isset($_GET['toggle'])){
  $pid=(int)$_GET['toggle'];
  if(in_array($pid, compare_all(), true)) compare_remove($pid);
  else compare_add($pid);
  header('Location: '.url('pages/compare.php'));
  exit;
}

$ids = compare_all();
?>
<style>
.compare-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:10px 0 14px}
.compare-head h1{margin:0}
.compare-head .muted{font-size:13px}
.compare-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:28px;text-align:center}
.compare-empty img{max-width:220px;width:70%;opacity:.9}
.compare-empty .btnrow{display:flex;gap:10px;flex-wrap:wrap;justify-content:center}
</style>

<div class="compare-head">
  <div>
    <h1>Compare</h1>
    <div class="muted">Compare specs side-by-side. Add items from Products using the compare icon.</div>
  </div>
  <div class="muted">
    <?php echo count($ids); ?> item(s)
  </div>
</div>

<?php
if(!$ids){
  echo '<div class="card compare-empty">';
  echo '<img src="'.asset('assets/img/empty.svg').'" alt="Empty">';
  echo '<div style="font-weight:900;font-size:18px">No items to compare</div>';
  echo '<div class="muted" style="max-width:520px">Go to Products and click the compare icon on any item to add it here.</div>';
  echo '<div class="btnrow">';
  echo '<a class="btn" href="'.url('pages/products.php').'">Browse Products</a>';
  echo '</div>';
  echo '</div>';
  require_once __DIR__.'/../includes/footer.php';
  exit;
}

$in = implode(',', array_map('intval',$ids));
$rows = $pdo->query("SELECT id,name,brand,sku,specs_json FROM products WHERE id IN ($in)")->fetchAll();
?>

<div style="overflow:auto">
<table class="table">
  <tr>
    <th>Attribute</th>
    <?php foreach($rows as $r): ?>
      <th><?php echo e($r['name']); ?></th>
    <?php endforeach; ?>
  </tr>

  <tr>
    <td>Brand</td>
    <?php foreach($rows as $r): ?><td><?php echo e($r['brand']); ?></td><?php endforeach; ?>
  </tr>

  <tr>
    <td>SKU</td>
    <?php foreach($rows as $r): ?><td><?php echo e($r['sku']); ?></td><?php endforeach; ?>
  </tr>

  <?php
  $keys = [];
  foreach($rows as $r){
    $j = json_decode($r['specs_json'] ?? '', true);
    if(is_array($j)) $keys = array_unique(array_merge($keys, array_keys($j)));
  }
  foreach($keys as $k){
    echo '<tr><td>'.e($k).'</td>';
    foreach($rows as $r){
      $j=json_decode($r['specs_json'] ?? '', true);
      echo '<td>'.e($j[$k] ?? '—').'</td>';
    }
    echo '</tr>';
  }
  ?>

  <tr>
    <td></td>
    <?php foreach($rows as $r): ?>
      <td>
        <form action="<?php echo url('actions/cart.php'); ?>" method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?php echo (int)$r['id']; ?>">
          <input type="hidden" name="qty" value="1">
          <button class="btn" type="submit">Add to RFQ</button>
          <a class="btn secondary" href="<?php echo url('pages/product.php?id='.$r['id']); ?>">View</a>
          <a class="btn ghost" href="<?php echo url('pages/compare.php?toggle='.$r['id']); ?>">Remove</a>
        </form>
      </td>
    <?php endforeach; ?>
  </tr>
</table>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
