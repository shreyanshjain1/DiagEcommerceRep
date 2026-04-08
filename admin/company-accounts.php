<?php
require_once __DIR__ . '/header.php';

$sql = "SELECT ca.*,
               creator.name AS created_by_name,
               (SELECT COUNT(*) FROM company_account_contacts c WHERE c.company_account_id = ca.id AND c.invite_status <> 'inactive') AS contact_count,
               (SELECT COUNT(*) FROM quotes q WHERE q.user_id IN (SELECT c2.user_id FROM company_account_contacts c2 WHERE c2.company_account_id = ca.id)) AS quote_count
        FROM company_accounts ca
        LEFT JOIN users creator ON creator.id = ca.created_by
        ORDER BY ca.created_at DESC
        LIMIT 200";
$accounts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Company Accounts</h1>
<p style="color:#6b7280;margin-top:-4px">B2B account structure for multi-contact buyers, procurement teams, and company-level quote visibility.</p>
<table class="table">
  <tr>
    <th>Company</th>
    <th>Account Code</th>
    <th>Status</th>
    <th>Primary Email</th>
    <th>Primary Phone</th>
    <th>Contacts</th>
    <th>RFQs</th>
    <th>Created By</th>
    <th>Created</th>
  </tr>
  <?php foreach ($accounts as $a): ?>
    <tr>
      <td>
        <strong><?php echo e($a['company_name']); ?></strong>
        <?php if (!empty($a['vat_tin'])): ?>
          <br><small style="color:#6b7280">VAT/TIN: <?php echo e($a['vat_tin']); ?></small>
        <?php endif; ?>
      </td>
      <td><?php echo e($a['account_code']); ?></td>
      <td><span class="tag"><?php echo e(ucfirst(str_replace('_', ' ', (string)$a['account_status']))); ?></span></td>
      <td><?php echo e($a['primary_email']); ?></td>
      <td><?php echo e($a['primary_phone']); ?></td>
      <td><?php echo e((string)$a['contact_count']); ?></td>
      <td><?php echo e((string)$a['quote_count']); ?></td>
      <td><?php echo e($a['created_by_name']); ?></td>
      <td><?php echo e($a['created_at']); ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/footer.php'; ?>
