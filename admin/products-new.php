<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT id, name FROM suppliers WHERE is_active=1 ORDER BY name ASC")->fetchAll();
?>

<h1>Add Product</h1>

<div class="card p">
  <form class="form" action="<?php echo url('admin_actions/products_new.php'); ?>" method="post" enctype="multipart/form-data">
    <?php csrf_field(); ?>

    <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
      <div>
        <label>Product Name</label>
        <input name="name" required placeholder="e.g., Erba H560 Hematology Analyzer">
      </div>
      <div>
        <label>SKU</label>
        <input name="sku" required placeholder="e.g., ERB-H560">
      </div>
      <div>
        <label>Brand</label>
        <input name="brand" required placeholder="e.g., Erba">
      </div>
      <div>
        <label>Supplier / Vendor</label>
        <select name="supplier_id">
          <option value="">None selected</option>
          <?php foreach($suppliers as $s): ?>
            <option value="<?php echo (int)$s['id']; ?>"><?php echo e($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Vendor SKU</label>
        <input name="vendor_sku" placeholder="Supplier-side SKU / ref code">
      </div>
      <div>
        <label>Category</label>
        <select name="category_id" required>
          <option value="">Select...</option>
          <?php foreach($cats as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo e($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Price (PHP)</label>
        <input name="price" type="number" step="0.01" value="0" placeholder="0 for RFQ">
        <div class="muted" style="margin-top:6px">Set to 0 to show RFQ instead of a price.</div>
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>

    <h3 class="mt16">Commercial Details</h3>
    <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px">
      <div>
        <label>Availability Status</label>
        <select name="availability_status">
          <option value="in_stock">In stock</option>
          <option value="low_stock">Low stock</option>
          <option value="out_of_stock">Out of stock</option>
          <option value="backorder">Backorder</option>
          <option value="preorder">Pre-order</option>
          <option value="discontinued">Discontinued</option>
        </select>
      </div>
      <div>
        <label>Unit of Measure</label>
        <input name="unit_of_measure" placeholder="e.g., unit, box, kit, bottle">
      </div>
      <div>
        <label>Pack Size</label>
        <input name="pack_size" placeholder="e.g., 1 analyzer / box of 50 / 25 tests">
      </div>
      <div>
        <label>MOQ</label>
        <input name="moq" type="number" min="1" value="1">
      </div>
      <div>
        <label>Lead Time (days)</label>
        <input name="lead_time_days" type="number" min="0" placeholder="e.g., 14">
      </div>
      <div>
        <label>Lead Time Note</label>
        <input name="lead_time_note" placeholder="e.g., ex-stock / imported on order">
      </div>
    </div>

    <label class="mt16">Short description</label>
    <textarea name="short_description" rows="2" placeholder="1–2 lines shown in cards/listings..."></textarea>

    <label class="mt16">Full description</label>
    <textarea name="description" rows="6" placeholder="Detailed description, inclusions, applications, etc..."></textarea>

    <div class="mt16" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <label style="margin:0"><input type="checkbox" name="featured" value="1"> Featured (show on home)</label>
    </div>

    <h3 class="mt16">Specs</h3>
    <div id="specRows" class="grid" style="grid-template-columns:1fr 1fr auto;gap:10px">
      <?php for($i=0;$i<6;$i++): ?>
        <input name="spec_key[]" placeholder="Spec (e.g., Throughput)">
        <input name="spec_val[]" placeholder="Value (e.g., 60 tests/hour)">
        <button type="button" class="btn ghost" onclick="this.parentElement.remove()" title="Remove row">✕</button>
      <?php endfor; ?>
    </div>
    <button type="button" class="btn secondary mt8" id="addSpec">Add another spec</button>

    <h3 class="mt16">Images</h3>
    <div class="muted">You can upload multiple images. The first one becomes the primary image.</div>
    <input class="mt8" type="file" name="images[]" accept="image/*" multiple>

    <h3 class="mt16">Documents (PDF)</h3>
    <div class="muted">Brochures, spec sheets, manuals (PDF). Optional.</div>
    <input class="mt8" type="file" name="docs[]" accept="application/pdf" multiple>

    <div class="mt16">
      <button class="btn" type="submit">Create product</button>
      <a class="btn ghost" href="<?php echo url('admin/products.php'); ?>" style="margin-left:8px">Cancel</a>
    </div>
  </form>
</div>

<script>
  (function(){
    const add = document.getElementById('addSpec');
    const wrap = document.getElementById('specRows');
    if (!add || !wrap) return;
    add.addEventListener('click', ()=>{
      const row = document.createElement('div');
      row.style.display = 'contents';
      row.innerHTML = `
        <input name="spec_key[]" placeholder="Spec (e.g., Sample Volume)">
        <input name="spec_val[]" placeholder="Value">
        <button type="button" class="btn ghost" title="Remove row">✕</button>
      `;
      wrap.appendChild(row);
      row.querySelector('button').addEventListener('click', ()=> row.remove());
    });
  })();
</script>

<?php require_once __DIR__.'/footer.php'; ?>
