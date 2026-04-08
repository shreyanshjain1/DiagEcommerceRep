<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/settings.php';

$uid = current_user_id();
if(!$uid){
  header('Location: ' . url('pages/login.php'));
  exit;
}

$msg = (string)($_GET['msg'] ?? '');
if ($msg === 'submitted') $flash = 'RFQ submitted successfully. Our admin team will contact you soon.';
elseif ($msg === 'draft') $flash = 'RFQ draft saved. You can submit it later.';
else $flash = '';

$st = $pdo->prepare("SELECT id, quote_number, status, created_at, updated_at
                     FROM quotes
                     WHERE user_id=:u
                     ORDER BY updated_at DESC
                     LIMIT 200");
$st->execute([':u'=>$uid]);
$rfqs = $st->fetchAll();
?>

<div class="page-head">
  <h1>My RFQs</h1>
  <p class="muted">Track your quotation requests here.</p>
</div>

<?php if($flash): ?><div class="alert success"><?php echo e($flash); ?></div><?php endif; ?>

<?php if(!$rfqs): ?>
  <div class="card p">
    You don't have any RFQs yet. <a class="btn" href="<?php echo url('pages/products.php'); ?>">Browse products</a>
  </div>
<?php else: ?>
  <table class="table card">
    <tr>
      <th>RFQ #</th>
      <th>Status</th>
      <th>Submitted</th>
      <th>Updated</th>
      <th></th>
    </tr>
    <?php foreach($rfqs as $r): ?>
      <tr>
        <td><strong><?php echo e($r['quote_number']); ?></strong></td>
        <td><span class="tag"><?php echo e($r['status']); ?></span></td>
        <td><?php echo e($r['created_at']); ?></td>
        <td><?php echo e($r['updated_at']); ?></td>
        <td style="text-align:right">
          <a class="btn secondary" href="<?php echo url('pages/rfq-view.php?id='.$r['id']); ?>">Open</a>
          <a class="btn ghost" href="<?php echo url('actions/quote.php?action=reorder&id='.$r['id']); ?>">Reorder</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
