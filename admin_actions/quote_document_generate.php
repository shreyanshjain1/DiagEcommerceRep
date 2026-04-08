<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . url('admin/rfqs.php'));
  exit;
}

try {
  generate_quote_snapshot($pdo, $id, current_user_id());
  header('Location: ' . url('admin/rfq-view.php?id=' . $id . '&doc=generated'));
  exit;
} catch (Throwable $e) {
  error_log('Quote snapshot generation failed: ' . $e->getMessage());
  header('Location: ' . url('admin/rfq-view.php?id=' . $id . '&doc=failed'));
  exit;
}
