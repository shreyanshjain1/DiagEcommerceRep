<?php require_once __DIR__.'/header.php'; require_once __DIR__.'/../config/csrf.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM inquiries WHERE id=:id");
$st->execute([':id'=>$id]);
$q = $st->fetch();
if(!$q){ echo '<div class="alert error">Inquiry not found.</div>'; require_once __DIR__.'/footer.php'; exit; }
?>
<h1 class="m0">Inquiry</h1>
<p class="mt8"><span class="badge badge-ok">#<?php echo e($q['id']); ?></span> • <?php echo e($q['created_at']); ?></p>

<div class="order-wrap mt12">
  <div class="card">
    <div class="p">
      <h3 class="m0">From</h3>
      <p class="mt8">
        <strong><?php echo e($q['company'] ?: '—'); ?></strong><br>
        <?php echo e($q['name']); ?><br>
        <?php echo e($q['email']); ?><?php if($q['phone']): ?> • <?php echo e($q['phone']); ?><?php endif; ?>
      </p>
      <h3 class="mt16 m0">Subject</h3>
      <p class="mt8"><?php echo e($q['subject']); ?></p>
      <h3 class="mt16 m0">Message</h3>
      <div class="mt8" style="white-space:pre-wrap;border:1px solid var(--admin-border);border-radius:12px;padding:12px;background:#fafafa">
        <?php echo e($q['message']); ?>
      </div>

      <p class="mt16">
        <a class="btn" href="mailto:<?php echo e($q['email']); ?>?subject=<?php echo rawurlencode('Re: '.$q['subject']); ?>">Reply by Email</a>
      </p>
    </div>
  </div>

  <div class="card">
    <div class="p">
      <h3 class="m0">Status</h3>
      <form class="mt12" action="<?php echo url('admin_actions/inquiry.php'); ?>" method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo e($q['id']); ?>">
        <select name="status">
          <?php foreach(['New','In Progress','Closed'] as $s): ?>
            <option value="<?php echo e($s); ?>" <?php echo $q['status']===$s?'selected':''; ?>><?php echo e($s); ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn mt12" type="submit">Update</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>
