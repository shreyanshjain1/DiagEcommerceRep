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


function redirect_with_query(string $path, array $params = []): never {
  $query = http_build_query(array_filter($params, static fn($v) => $v !== null && $v !== ''));
  $target = url($path);
  if ($query !== '') {
    $target .= (strpos($target, '?') === false ? '?' : '&') . $query;
  }
  header('Location: ' . $target);
  exit;
}

function admin_temp_password(int $length = 12): string {
  $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
  $max = strlen($alphabet) - 1;
  $out = '';
  for ($i = 0; $i < $length; $i++) {
    $out .= $alphabet[random_int(0, $max)];
  }
  return $out;
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


// --- Audit logging ---
function client_ip(): string {
  $keys = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
  foreach ($keys as $k) {
    $v = trim((string)($_SERVER[$k] ?? ''));
    if ($v === '') continue;
    if ($k === 'HTTP_X_FORWARDED_FOR') {
      $parts = array_map('trim', explode(',', $v));
      return (string)($parts[0] ?? '');
    }
    return $v;
  }
  return '';
}

function audit_json($value): ?string {
  if ($value === null) return null;
  $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  return $json === false ? null : $json;
}

function audit_log(PDO $pdo, string $entityType, int $entityId, string $action, $before = null, $after = null, array $meta = []): void {
  try {
    $stmt = $pdo->prepare("INSERT INTO audit_logs(user_id,entity_type,entity_id,action,before_json,after_json,meta_json,ip_address,user_agent,created_at) VALUES(:user_id,:entity_type,:entity_id,:action,:before_json,:after_json,:meta_json,:ip_address,:user_agent,NOW())");
    $stmt->execute([
      ':user_id' => current_user_id(),
      ':entity_type' => $entityType,
      ':entity_id' => $entityId,
      ':action' => $action,
      ':before_json' => audit_json($before),
      ':after_json' => audit_json($after),
      ':meta_json' => audit_json($meta),
      ':ip_address' => substr(client_ip(), 0, 64),
      ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
  } catch (Throwable $e) {
    error_log('audit_log failed: ' . $e->getMessage());
  }
}


function rfq_timeline_log(PDO $pdo, int $quoteId, string $eventType, ?string $fromStatus = null, ?string $toStatus = null, string $note = '', array $meta = []): void {
  try {
    $stmt = $pdo->prepare("INSERT INTO quote_status_history(quote_id, event_type, from_status, to_status, note, meta_json, acted_by, ip_address, user_agent, created_at) VALUES(:quote_id,:event_type,:from_status,:to_status,:note,:meta_json,:acted_by,:ip_address,:user_agent,NOW())");
    $stmt->execute([
      ':quote_id' => $quoteId,
      ':event_type' => substr($eventType, 0, 50),
      ':from_status' => $fromStatus !== '' ? $fromStatus : null,
      ':to_status' => $toStatus !== '' ? $toStatus : null,
      ':note' => $note !== '' ? $note : null,
      ':meta_json' => audit_json($meta),
      ':acted_by' => current_user_id(),
      ':ip_address' => substr(client_ip(), 0, 64),
      ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
  } catch (Throwable $e) {
    error_log('rfq_timeline_log failed: ' . $e->getMessage());
  }
}

function rfq_history(PDO $pdo, int $quoteId): array {
  try {
    $stmt = $pdo->prepare("SELECT h.*, u.name AS acted_by_name, u.email AS acted_by_email FROM quote_status_history h LEFT JOIN users u ON u.id = h.acted_by WHERE h.quote_id = :quote_id ORDER BY h.created_at DESC, h.id DESC");
    $stmt->execute([':quote_id' => $quoteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {
    error_log('rfq_history failed: ' . $e->getMessage());
    return [];
  }
}
