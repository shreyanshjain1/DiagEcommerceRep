<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$pid = (int)($_POST['product_id'] ?? 0);
if ($pid <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid product']);
  exit;
}

$token = (string)($_POST['csrf_token'] ?? '');
if (!isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'CSRF failed']);
  exit;
}

$ids = compare_all();
$added = true;

if (in_array($pid, $ids, true)) {
  compare_remove($pid);
  $added = false;
} else {
  compare_add($pid);
  $added = true;
}

echo json_encode([
  'ok' => true,
  'added' => $added,
  'count' => count(compare_all())
]);
