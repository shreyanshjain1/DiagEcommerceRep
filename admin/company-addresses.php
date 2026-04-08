<?php
require_once __DIR__ . '/header.php';

$accounts = $pdo->query("SELECT id, company_name FROM company_accounts ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$selected = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
$where = '';
$params = [];
if ($selected > 0) {
  $where = 'WHERE caa.company_account_id = :account_id';
  $params[':account_id'] = $selected;
}

$sql = "SELECT caa.*, ca.company_name, ca.account_code
        FROM company_account_addresses caa
        INNER JOIN company_accounts ca ON ca.id = caa.company_account_id
        {$where}
        ORDER BY ca.company_name ASC, caa.is_default_billing DESC, caa.is_default_shipping DESC, caa.label ASC, caa.id DESC
        LIMIT 300";
$st = $pdo->prepare($sql);
$st->execute($params);
$addresses = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Company Addresses</h1>
<p style="color:#6b7280;margin-top:-4px">Saved billing, shipping, site, and warehouse profiles linked to B2B company accounts.</p>

<form method="get" class="card" style="padding:14px;margin-bottom:16px">
  <div class="row">
    <div>
      <label>Filter by Company Account</label>
      <select name="account_id">
        <option value="0">All company accounts</option>
        <?php foreach ($accounts as $account): ?>
          <option value="<?php echo (int)$account['id']; ?>" <?php echo $selected === (int)$account['id'] ? 'selected' : ''; ?>><?php echo e($account['company_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="align-self:end">
      <button class="btn" type="submit">Apply Filter</button>
    </div>
  </div>
</form>

<table class="table">
  <tr>
    <th>Company</th>
    <th>Label</th>
    <th>Type</th>
    <th>Recipient</th>
    <th>Address</th>
    <th>Defaults</th>
    <th>Status</th>
  </tr>
  <?php foreach ($addresses as $a): ?>
    <tr>
      <td>
        <strong><?php echo e($a['company_name']); ?></strong><br>
        <small style="color:#6b7280"><?php echo e($a['account_code']); ?></small>
      </td>
      <td><?php echo e($a['label']); ?></td>
      <td><span class="tag"><?php echo e(ucfirst((string)$a['address_type'])); ?></span></td>
      <td>
        <?php echo e($a['recipient_name']); ?>
        <?php if (!empty($a['recipient_phone'])): ?><br><small style="color:#6b7280"><?php echo e($a['recipient_phone']); ?></small><?php endif; ?>
        <?php if (!empty($a['email'])): ?><br><small style="color:#6b7280"><?php echo e($a['email']); ?></small><?php endif; ?>
      </td>
      <td><?php echo e(format_company_address($a)); ?></td>
      <td>
        <?php if ((int)$a['is_default_billing'] === 1): ?><span class="tag">Default Billing</span><?php endif; ?>
        <?php if ((int)$a['is_default_shipping'] === 1): ?><span class="tag">Default Shipping</span><?php endif; ?>
      </td>
      <td><span class="tag"><?php echo (int)$a['is_active'] === 1 ? 'Active' : 'Inactive'; ?></span></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/footer.php'; ?>
