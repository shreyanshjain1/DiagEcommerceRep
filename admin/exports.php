<?php
require_once __DIR__.'/header.php';

$dataset = $_GET['dataset'] ?? 'rfqs';
$allowed = [
  'rfqs' => 'RFQs',
  'quotes' => 'Quotation Line Items',
  'inquiries' => 'Inquiries',
  'users' => 'Users',
  'products' => 'Products',
  'company_accounts' => 'Company Accounts'
];
if (!isset($allowed[$dataset])) $dataset = 'rfqs';

[$dateFrom, $dateTo] = admin_export_range();
$status = trim((string)($_GET['status'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));

$examples = [
  'rfqs' => 'Filter by quote status or search by quote number, customer, company, or email.',
  'quotes' => 'Export line-item-level commercial data for quoting analysis.',
  'inquiries' => 'Useful for customer service reporting and sales follow-up.',
  'users' => 'Recruiter-friendly signal for account administration and CRM-style visibility.',
  'products' => 'Useful for catalog health, stock checks, and merchandising review.',
  'company_accounts' => 'Reserved for B2B account exports when company-account tables exist.'
];
?>

<h1>Export Center</h1>
<p class="muted" style="margin-top:-6px;margin-bottom:16px">Download recruiter-friendly CSV exports for core business datasets. This makes the repo look more operational, not just CRUD-based.</p>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:18px">
  <?php foreach ($allowed as $key => $label): ?>
    <a class="card" href="<?php echo url('admin/exports.php?dataset='.$key); ?>" style="text-decoration:none;border:1px solid <?php echo $dataset === $key ? 'var(--brand,#2563eb)' : 'rgba(148,163,184,.25)'; ?>;box-shadow:0 8px 30px rgba(15,23,42,.08)">
      <div style="font-weight:800;color:#0f172a"><?php echo e($label); ?></div>
      <div class="muted" style="margin-top:6px;font-size:14px"><?php echo e($examples[$key]); ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-bottom:16px">
  <h3 style="margin-top:0">Generate CSV Export</h3>
  <form method="get" action="<?php echo url('admin/export_csv.php'); ?>" class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end">
    <div>
      <label>Dataset</label>
      <select name="dataset">
        <?php foreach ($allowed as $key => $label): ?>
          <option value="<?php echo e($key); ?>" <?php echo $dataset === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Status (optional)</label>
      <input type="text" name="status" value="<?php echo e($status); ?>" placeholder="submitted / quoted / closed">
    </div>
    <div>
      <label>Date From</label>
      <input type="date" name="date_from" value="<?php echo e($dateFrom); ?>">
    </div>
    <div>
      <label>Date To</label>
      <input type="date" name="date_to" value="<?php echo e($dateTo); ?>">
    </div>
    <div>
      <label>Search (optional)</label>
      <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="customer, company, SKU, email...">
    </div>
    <div>
      <button class="btn" type="submit">Download CSV</button>
    </div>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0">Included Exports</h3>
  <ul style="margin:0;padding-left:18px;line-height:1.7">
    <li><strong>RFQs</strong> – quote number, status, customer identity, totals, validity, timing.</li>
    <li><strong>Quotation Line Items</strong> – line-level export for product, quantity, and pricing analysis.</li>
    <li><strong>Inquiries</strong> – customer service and pre-sales requests.</li>
    <li><strong>Users</strong> – account administration and CRM-style export.</li>
    <li><strong>Products</strong> – catalog, stock, SKU, category, and activation visibility.</li>
    <li><strong>Company Accounts</strong> – exports gracefully fall back if the table is not present yet.</li>
  </ul>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
