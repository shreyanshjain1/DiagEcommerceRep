<?php
require_once __DIR__ . '/../includes/session.php';

function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['_csrf'];
}

function csrf_input(): string {
  return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_field(): void {
  echo csrf_input();
}

function csrf_validate(?string $fallback = null): void {
  $token = (string)($_POST['csrf'] ?? '');
  $sess  = (string)($_SESSION['_csrf'] ?? '');
  $ok = ($token !== '' && $sess !== '' && hash_equals($sess, $token));
  if ($ok) return;

  $fallback = $fallback ?: (isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '/');
  header('Location: ' . $fallback);
  exit;
}
