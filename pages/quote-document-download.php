<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$uid = current_user_id();
if (!$uid) {
  http_response_code(403);
  exit('Login required');
}

$quoteId = (int)($_GET['id'] ?? 0);
$docId = (int)($_GET['doc'] ?? 0);
if ($quoteId <= 0 || $docId <= 0) {
  http_response_code(400);
  exit('Bad request');
}

$st = $pdo->prepare("
  SELECT d.*, q.user_id, q.quote_number
  FROM quote_documents d
  INNER JOIN quotes q ON q.id = d.quote_id
  WHERE d.id = :doc AND d.quote_id = :qid AND d.is_customer_visible = 1
  LIMIT 1
");
$st->execute([':doc' => $docId, ':qid' => $quoteId]);
$row = $st->fetch();

if (!$row || (int)$row['user_id'] !== $uid) {
  http_response_code(404);
  exit('Document not found');
}

$base = realpath(__DIR__ . '/..');
$target = realpath($base . '/' . ltrim((string)$row['file_path'], '/'));
if ($base === false || $target === false || strpos($target, $base) !== 0 || !is_file($target)) {
  http_response_code(404);
  exit('Stored file not found');
}

$mime = (string)($row['mime_type'] ?: 'application/octet-stream');
$downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$row['title']);
if ($downloadName === '') {
  $downloadName = 'quote-document-' . $docId;
}
if (pathinfo($downloadName, PATHINFO_EXTENSION) === '') {
  $downloadName .= '.html';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($target));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
readfile($target);
exit;
