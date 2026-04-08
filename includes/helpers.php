<?php
// Common helpers (no DB dependencies)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// ------------------------------------------------------------
// Compatibility helpers (PHP 7.4+)
// ------------------------------------------------------------
// PHP 8 introduced str_contains; some cPanel environments still run PHP 7.x.
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle): bool {
    $haystack = (string)$haystack;
    $needle = (string)$needle;
    if ($needle === '') return true;
    return strpos($haystack, $needle) !== false;
  }
}

// If mbstring isn't enabled, fallback to substr.
function mb_first_char(string $s): string {
  if (function_exists('mb_substr')) return (string)mb_substr($s, 0, 1, 'UTF-8');
  return substr($s, 0, 1);
}

function e($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// --- Base URL helpers so app works in a subfolder ---
function base_web(): string {
  $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  $rootFs = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
  if ($doc === '' || $rootFs === '') return '';
  $web = str_replace($doc, '', $rootFs);
  if ($web === false) $web = '';
  return rtrim((string)$web, '/');
}

function url(string $path): string {
  return base_web() . '/' . ltrim($path, '/');
}

function asset(string $path): string {
  return base_web() . '/' . ltrim($path, '/');
}

function web_path(string $p): string {
  if (stripos($p, 'http://') === 0 || stripos($p, 'https://') === 0) return $p;
  return asset(ltrim($p, '/'));
}

function ensure_post(): void {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
  }
}

function redirect(string $to): never {
  header('Location: ' . $to);
  exit;
}

function current_user_id(): ?int {
  return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user_role(): string {
  return (string)($_SESSION['role'] ?? 'customer');
}

function is_admin(): bool {
  return current_user_role() === 'admin';
}

function require_admin(): void {
  if (!current_user_id() || !is_admin()) {
    http_response_code(403);
    die('Admin access only');
  }
}

function order_number(): string {
  return 'PITC-' . date('Ymd') . '-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function quote_number(): string {
  return 'Q-' . date('Ymd') . '-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// --- Compare list (session) ---
function compare_add(int $pid): void {
  $_SESSION['compare'] = $_SESSION['compare'] ?? [];
  if (!in_array($pid, $_SESSION['compare'], true)) $_SESSION['compare'][] = $pid;
}

function compare_remove(int $pid): void {
  $_SESSION['compare'] = array_values(array_filter($_SESSION['compare'] ?? [], fn($v) => (int)$v !== (int)$pid));
}

function compare_all(): array {
  return $_SESSION['compare'] ?? [];
}

function compare_count(): int {
  return count(compare_all());
}

// --- Wishlist (session) ---
function wishlist_add(int $pid): void {
  $_SESSION['wishlist'] = $_SESSION['wishlist'] ?? [];
  if (!in_array($pid, $_SESSION['wishlist'], true)) $_SESSION['wishlist'][] = $pid;
}

function wishlist_remove(int $pid): void {
  $_SESSION['wishlist'] = array_values(array_filter($_SESSION['wishlist'] ?? [], fn($v) => (int)$v !== (int)$pid));
}

function wishlist_has(int $pid): bool {
  return in_array($pid, $_SESSION['wishlist'] ?? [], true);
}

function wishlist_all(): array {
  return $_SESSION['wishlist'] ?? [];
}

function wishlist_count(): int {
  return count(wishlist_all());
}

// --- Recently viewed ---
function track_viewed(int $pid): void {
  $_SESSION['recent'] = $_SESSION['recent'] ?? [];
  array_unshift($_SESSION['recent'], $pid);
  $_SESSION['recent'] = array_slice(array_values(array_unique(array_map('intval', $_SESSION['recent']))), 0, 12);
}


// --- Export helpers ---
function csv_escape_cell($value): string {
  $value = (string)($value ?? '');
  $value = str_replace(["
", "", "
"], ' ', $value);
  return $value;
}

function export_csv_download(string $filename, array $headers, array $rows): void {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) . '"');
  header('Pragma: no-cache');
  header('Expires: 0');

  $out = fopen('php://output', 'w');
  if ($out === false) {
    http_response_code(500);
    exit('Unable to open output stream');
  }

  fwrite($out, "ï»¿");
  fputcsv($out, $headers);
  foreach ($rows as $row) {
    $line = [];
    foreach ($headers as $header) {
      $line[] = csv_escape_cell($row[$header] ?? '');
    }
    fputcsv($out, $line);
  }
  fclose($out);
  exit;
}

function admin_export_range(): array {
  $dateFrom = trim((string)($_GET['date_from'] ?? ''));
  $dateTo   = trim((string)($_GET['date_to'] ?? ''));

  if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = '';
  if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = '';

  return [$dateFrom, $dateTo];
}
