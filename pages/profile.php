<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$uid = current_user_id();
if (!$uid) {
  echo '<div class="alert error">Please login to view your profile.</div>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, phone, company, company_account_id, company_contact_role, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$companyAccount = null;
$contacts = [];
$companyAddresses = [];
$defaultBillingAddress = null;
$defaultShippingAddress = null;
if (!empty($user['company_account_id'])) {
  $companyAccount = get_company_account($pdo, (int)$user['company_account_id']);
  $contacts = get_company_contacts($pdo, (int)$user['company_account_id']);
  $companyAddresses = get_company_addresses($pdo, (int)$user['company_account_id']);
  $defaultBillingAddress = get_default_company_address($pdo, (int)$user['company_account_id'], 'billing');
  $defaultShippingAddress = get_default_company_address($pdo, (int)$user['company_account_id'], 'shipping');
}
?>
<h1>Your Profile</h1>
<div class="form">
  <div class="row">
    <div>
      <label>Company</label>
      <input type="text" value="<?php echo e($user['company']); ?>" disabled>
    </div>
    <div>
      <label>Name</label>
      <input type="text" value="<?php echo e($user['name']); ?>" disabled>
    </div>
  </div>
  <div class="row">
    <div>
      <label>Email</label>
      <input type="text" value="<?php echo e($user['email']); ?>" disabled>
    </div>
    <div>
      <label>Phone</label>
      <input type="text" value="<?php echo e($user['phone']); ?>" disabled>
    </div>
  </div>
  <div class="row">
    <div>
      <label>Company Contact Role</label>
      <input type="text" value="<?php echo e(ucfirst((string)($user['company_contact_role'] ?? 'primary'))); ?>" disabled>
    </div>
    <div>
      <label>Member Since</label>
      <input type="text" value="<?php echo e($user['created_at']); ?>" disabled>
    </div>
  </div>

  <?php if ($companyAccount): ?>
    <div style="margin-top:18px;padding:18px;border:1px solid #e5e7eb;border-radius:16px;background:#fafafa">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
        <div>
          <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">Company Account</div>
          <h2 style="margin:6px 0 4px;font-size:22px"><?php echo e($companyAccount['company_name']); ?></h2>
          <div style="color:#6b7280">Account Code: <?php echo e($companyAccount['account_code']); ?></div>
        </div>
        <div>
          <span class="tag"><?php echo e(str_replace('_', ' ', ucfirst((string)$companyAccount['account_status']))); ?></span>
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <div>
          <label>Primary Email</label>
          <input type="text" value="<?php echo e($companyAccount['primary_email']); ?>" disabled>
        </div>
        <div>
          <label>Primary Phone</label>
          <input type="text" value="<?php echo e($companyAccount['primary_phone']); ?>" disabled>
        </div>
      </div>
      <div class="row">
        <div>
          <label>Linked Contacts</label>
          <input type="text" value="<?php echo e((string)$companyAccount['contact_count']); ?>" disabled>
        </div>
        <div>
          <label>RFQs / Quotes</label>
          <input type="text" value="<?php echo e((string)$companyAccount['quote_count']); ?>" disabled>
        </div>
      </div>

      <?php if ($contacts): ?>
        <div style="margin-top:12px">
          <label style="display:block;margin-bottom:8px;font-weight:700">Company Contacts</label>
          <table class="table">
            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Account Role</th><th>Status</th></tr>
            <?php foreach ($contacts as $contact): ?>
              <tr>
                <td><?php echo e($contact['name']); ?><?php if ((int)$contact['is_primary'] === 1): ?> <span class="tag">Primary</span><?php endif; ?></td>
                <td><?php echo e($contact['email']); ?></td>
                <td><?php echo e($contact['phone']); ?></td>
                <td><?php echo e(ucfirst((string)$contact['contact_role'])); ?></td>
                <td><?php echo e(ucfirst((string)$contact['invite_status'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>

      <?php if ($companyAddresses): ?>
        <div style="margin-top:14px">
          <label style="display:block;margin-bottom:8px;font-weight:700">Saved Address Profiles</label>
          <table class="table">
            <tr><th>Label</th><th>Type</th><th>Address</th><th>Defaults</th></tr>
            <?php foreach ($companyAddresses as $address): ?>
              <tr>
                <td>
                  <strong><?php echo e($address['label']); ?></strong>
                  <?php if (!empty($address['recipient_name'])): ?><br><small style="color:#6b7280"><?php echo e($address['recipient_name']); ?></small><?php endif; ?>
                </td>
                <td><?php echo e(ucfirst((string)$address['address_type'])); ?></td>
                <td><?php echo e(format_company_address($address)); ?></td>
                <td>
                  <?php if ((int)$address['is_default_billing'] === 1): ?><span class="tag">Default Billing</span><?php endif; ?>
                  <?php if ((int)$address['is_default_shipping'] === 1): ?><span class="tag">Default Shipping</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <div class="row" style="margin-top:12px">
          <div>
            <label>Default Billing Profile</label>
            <textarea rows="3" disabled><?php echo e($defaultBillingAddress ? format_company_address($defaultBillingAddress) : 'Not set'); ?></textarea>
          </div>
          <div>
            <label>Default Shipping Profile</label>
            <textarea rows="3" disabled><?php echo e($defaultShippingAddress ? format_company_address($defaultShippingAddress) : 'Not set'); ?></textarea>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <p style="margin-top:12px">
    <a class="btn" href="<?php echo url('pages/quotes.php'); ?>">View My RFQs</a>
    <a class="btn" href="<?php echo url('actions/auth.php'); ?>?action=logout">Logout</a>
  </p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
