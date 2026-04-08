<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/csrf.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM products WHERE id=:id");
$st->execute([':id' => $id]);
$p = $st->fetch();
if (!$p) {
  echo '<div class="alert error">Product not found.</div>';
  require_once __DIR__ . '/footer.php';
  exit;
}

$cats = $pdo->query("SELECT id,name FROM categories ORDER BY sort_order ASC,name ASC")->fetchAll();

$imgs = $pdo->prepare("SELECT id,image_path,sort_order FROM product_images WHERE product_id=:p ORDER BY sort_order ASC,id ASC");
$imgs->execute([':p' => $id]);
$images = $imgs->fetchAll();

$docs = $pdo->prepare("SELECT id,title,label,file_path FROM documents WHERE product_id=:p ORDER BY id ASC");
$docs->execute([':p' => $id]);
$documents = $docs->fetchAll();

$specs = [];
if (!empty($p['specs_json'])) {
  $decoded = json_decode($p['specs_json'], true);
  if (is_array($decoded)) $specs = $decoded;
}
?>

<h1>Edit Product</h1>
<?php if (isset($_GET['ok']) || isset($_GET['saved'])): ?>
  <div class="alert success">Saved.</div>
<?php endif; ?>

<div class="card p">
  <!--
    IMPORTANT:
    This page previously had nested <form> tags (delete buttons inside the edit form).
    Nested forms break HTML form submission in most browsers, causing text fields/specs/docs to not submit.
    We keep ONE form for saving, and the media delete forms are rendered OUTSIDE that form.
  -->

  <form id="productForm" class="form" action="<?php echo url('admin_actions/product_update.php'); ?>" method="post" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">

    <div class="row">
      <div>
        <label>Name</label>
        <input type="text" name="name" value="<?php echo e($p['name']); ?>" required>
      </div>
      <div>
        <label>SKU</label>
        <input type="text" name="sku" value="<?php echo e($p['sku']); ?>" required>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Brand</label>
        <input type="text" name="brand" value="<?php echo e($p['brand']); ?>" required>
      </div>
      <div>
        <label>Category</label>
        <select name="category_id" required>
          <?php foreach ($cats as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$p['category_id'] === (int)$c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Price (PHP)</label>
        <input type="number" step="0.01" name="price" value="<?php echo e($p['price']); ?>">
        <div class="muted" style="margin-top:6px">Set to 0 to show RFQ instead of a price.</div>
      </div>
      <div>
        <label>Stock</label>
        <input type="number" name="stock" value="<?php echo e($p['stock']); ?>">
      </div>
      <div>
        <label>Active</label>
        <select name="is_active">
          <option value="1" <?php echo ((int)$p['is_active'] === 1) ? 'selected' : ''; ?>>Yes</option>
          <option value="0" <?php echo ((int)$p['is_active'] === 0) ? 'selected' : ''; ?>>No</option>
        </select>
      </div>
    </div>

    <label>Short Description</label>
    <textarea name="short_desc" rows="2"><?php echo e($p['short_desc']); ?></textarea>

    <label>Long Description</label>
    <textarea name="long_desc" rows="6"><?php echo e($p['long_desc']); ?></textarea>

    <h3 class="mt16">Specs</h3>
    <div class="muted">Shown on the product page. Add up to 12 key specs.</div>
    <div class="spec-grid mt16">
      <?php
      $keys = array_keys($specs);
      for ($i = 0; $i < 12; $i++):
        $k = $keys[$i] ?? '';
        $v = ($k !== '' && isset($specs[$k])) ? (string)$specs[$k] : '';
      ?>
        <div class="spec-row">
          <input type="text" name="spec_key[]" placeholder="Spec name" value="<?php echo e($k); ?>">
          <input type="text" name="spec_val[]" placeholder="Spec value" value="<?php echo e($v); ?>">
        </div>
      <?php endfor; ?>
    </div>

    <h3 class="mt16">Images</h3>
    <div class="muted">Upload new images to append, or tick “Replace existing images”.</div>
    <div class="row mt16">
      <div>
        <label>Upload Images (JPG/PNG/WEBP) – multiple</label>
        <input type="file" name="images[]" accept="image/*" multiple>
        <label style="display:flex;gap:8px;align-items:center;margin-top:10px">
          <input type="checkbox" name="replace_images" value="1">
          Replace existing images
        </label>
      </div>
    </div>

    <h3 class="mt16">Brochures / Documents</h3>
    <div class="muted">Upload PDFs or other files. Tick “Replace existing documents” to reset the list.</div>

    <div class="row mt16">
      <div>
        <label>Upload Documents – multiple</label>
        <input type="file" name="docs[]" accept="application/pdf" multiple>
        <label style="display:flex;gap:8px;align-items:center;margin-top:10px">
          <input type="checkbox" name="replace_docs" value="1">
          Replace existing documents
        </label>
      </div>
      <div>
        <label>Doc Title (applies to uploaded docs)</label>
        <input type="text" name="doc_title" placeholder="Brochure / IFU / Spec Sheet">
        <label class="mt16">Doc Label (optional)</label>
        <input type="text" name="doc_label" placeholder="PDF">
      </div>
    </div>
  </form>

  <div class="mt16" style="display:flex;flex-wrap:wrap;gap:10px">
    <button class="btn" type="submit" form="productForm">Save Changes</button>
    <a class="btn ghost" href="<?php echo url('admin/products.php'); ?>">Back</a>
    <a class="btn secondary" target="_blank" href="<?php echo url('pages/product.php?id=' . (int)$p['id']); ?>">View on Site</a>
  </div>
</div>

<?php if ($images): ?>
  <div class="card p mt16">
    <h3 style="margin-top:0">Existing Images</h3>
    <div class="media-grid mt16">
      <?php foreach ($images as $im): ?>
        <div class="media-card">
          <img src="<?php echo asset(ltrim($im['image_path'], '/')); ?>" alt="">
          <div class="media-actions">
            <form action="<?php echo url('admin_actions/product_media_delete.php'); ?>" method="post" onsubmit="return confirm('Delete this image?');">
              <?php csrf_field(); ?>
              <input type="hidden" name="type" value="image">
              <input type="hidden" name="id" value="<?php echo (int)$im['id']; ?>">
              <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($documents): ?>
  <div class="card p mt16">
    <h3 style="margin-top:0">Existing Documents</h3>
    <div class="mt16">
      <table class="table">
        <tr><th>Title</th><th>Label</th><th>File</th><th></th></tr>
        <?php foreach ($documents as $d): ?>
          <tr>
            <td><?php echo e($d['title']); ?></td>
            <td><?php echo e($d['label'] ?? ''); ?></td>
            <td><a target="_blank" href="<?php echo asset(ltrim($d['file_path'], '/')); ?>">Open</a></td>
            <td>
              <form action="<?php echo url('admin_actions/product_media_delete.php'); ?>" method="post" onsubmit="return confirm('Delete this document?');">
                <?php csrf_field(); ?>
                <input type="hidden" name="type" value="doc">
                <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
<?php endif; ?>

<style>
.spec-grid{display:grid;grid-template-columns:1fr;gap:10px}
.spec-row{display:grid;grid-template-columns:1fr 1.2fr;gap:10px}
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.media-card{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff}
.media-card img{width:100%;height:120px;object-fit:cover;display:block}
.media-actions{padding:10px;display:flex;justify-content:flex-end}
@media (max-width: 700px){.spec-row{grid-template-columns:1fr}}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
