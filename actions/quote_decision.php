<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

$uid = current_user_id();
if (!$uid) {
  redirect(url('pages/login.php'));
}

ensure_post();
csrf_validate();

$id = (int)($_POST['id'] ?? 0);
$decision = strtolower(trim((string)($_POST['decision'] ?? '')));
$allowed = ['approved', 'rejected'];

if ($id <= 0 || !in_array($decision, $allowed, true)) {
  redirect(url('pages/quotes.php'));
}

$reason = trim((string)($_POST['reason'] ?? ''));
$redirectUrl = url('pages/rfq-view.php?id=' . $id);

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("SELECT id, user_id, quote_number, status, approval_status, approval_note, approval_decided_at, total, valid_until FROM quotes WHERE id=:id AND user_id=:user_id LIMIT 1");
  $stmt->execute([':id' => $id, ':user_id' => $uid]);
  $before = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$before) {
    throw new RuntimeException('RFQ not found.');
  }

  if (($before['status'] ?? '') !== 'quoted') {
    throw new RuntimeException('Only quoted RFQs can be approved or rejected.');
  }

  if (($before['approval_status'] ?? 'pending') !== 'pending') {
    throw new RuntimeException('This quotation already has a customer decision.');
  }

  $update = $pdo->prepare("UPDATE quotes SET approval_status=:approval_status, approval_note=:approval_note, approval_decided_at=NOW(), updated_at=NOW() WHERE id=:id AND user_id=:user_id");
  $update->execute([
    ':approval_status' => $decision,
    ':approval_note' => ($reason !== '' ? $reason : null),
    ':id' => $id,
    ':user_id' => $uid,
  ]);

  $afterStmt = $pdo->prepare("SELECT id, user_id, quote_number, status, approval_status, approval_note, approval_decided_at, total, valid_until FROM quotes WHERE id=:id LIMIT 1");
  $afterStmt->execute([':id' => $id]);
  $after = $afterStmt->fetch(PDO::FETCH_ASSOC);

  audit_log($pdo, 'quote', $id, 'customer_quote_decision', $before, $after, [
    'decision' => $decision,
    'reason' => $reason,
    'source' => 'actions/quote_decision.php',
  ]);

  rfq_timeline_log(
    $pdo,
    $id,
    $decision === 'approved' ? 'quote_approved' : 'quote_rejected',
    $before['status'] ?? null,
    $after['status'] ?? null,
    $reason !== '' ? $reason : ($decision === 'approved' ? 'Customer approved the quotation.' : 'Customer rejected the quotation.'),
    [
      'decision' => $decision,
      'quote_number' => $after['quote_number'] ?? null,
      'source' => 'actions/quote_decision.php',
    ]
  );

  $pdo->commit();
  redirect($redirectUrl . '&decision=' . ($decision === 'approved' ? 'approved' : 'rejected'));
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('quote_decision failed: ' . $e->getMessage());
  redirect($redirectUrl . '&decision=error');
}
