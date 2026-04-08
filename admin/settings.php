<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';
require_once __DIR__.'/../includes/settings.php';

$hotline = (string)setting('contact_hotline', '+639691229230');
$whatsapp = (string)setting('contact_whatsapp', '09453462354');
$contactEmail = (string)setting('contact_email', $ADMIN_EMAIL);
?>

<h1>Settings</h1>
<?php if(isset($_GET['ok'])): ?><div class="alert success">Settings saved.</div><?php endif; ?>
<?php if(isset($_GET['err'])): ?><div class="alert error">Could not save settings (check DB table permissions).</div><?php endif; ?>
<div class="card p">
  <form class="form" action="<?php echo url('admin_actions/settings_save.php'); ?>" method="post">
    <?php csrf_field(); ?>

    <div class="row">
      <div>
        <label>Contact Email</label>
        <input type="email" name="contact_email" value="<?php echo e($contactEmail); ?>" required>
        <div class="muted" style="margin-top:6px">Shown in header/footer. RFQs are still emailed to ALL admin accounts.</div>
      </div>
      <div>
        <label>Hotline (tel: link)</label>
        <input type="text" name="contact_hotline" value="<?php echo e($hotline); ?>" placeholder="+639XXXXXXXXX or 09XXXXXXXXX" required>
      </div>
    </div>

    <div class="row">
      <div>
        <label>WhatsApp Number</label>
        <input type="text" name="contact_whatsapp" value="<?php echo e($whatsapp); ?>" placeholder="0945XXXXXXX" required>
        <div class="muted" style="margin-top:6px">Used for the floating WhatsApp button and RFQ WhatsApp shortcut.</div>
      </div>
      <div>
        <label>Site Name</label>
        <input type="text" name="site_name" value="<?php echo e($SITE_NAME); ?>" disabled>
        <div class="muted" style="margin-top:6px">Edit in <code>config/db.php</code> if needed.</div>
      </div>
    </div>

    <button class="btn" type="submit">Save Settings</button>
  </form>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
