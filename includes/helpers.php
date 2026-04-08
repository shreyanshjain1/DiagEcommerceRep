<?php
// Common helpers (no DB dependencies)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle): bool {
    $haystack = (string)$haystack;
    $needle = (string)$needle;
    if ($needle === '') return true;
    return strpos($haystack, $needle) !== false;
  }
}

function mb_first_char(string $s): string {
  if (function_exists('mb_substr')) return (string)mb_substr($s, 0, 1, 'UTF-8');
  return substr($s, 0, 1);
}

function e($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

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

function redirect(string $to): void {
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

function track_viewed(int $pid): void {
  $_SESSION['recent'] = $_SESSION['recent'] ?? [];
  array_unshift($_SESSION['recent'], $pid);
  $_SESSION['recent'] = array_slice(array_values(array_unique(array_map('intval', $_SESSION['recent']))), 0, 12);
}

function company_account_code(string $companyName): string {
  $prefix = preg_replace('/[^A-Z0-9]+/', '', strtoupper((string)$companyName));
  $prefix = substr($prefix, 0, 6);
  if ($prefix === '') {
    $prefix = 'ACC';
  }
  return $prefix . '-' . date('ymd') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

function current_user_company_account_id(PDO $pdo, ?int $userId = null): ?int {
  $uid = $userId ?: current_user_id();
  if (!$uid) return null;
  $st = $pdo->prepare('SELECT company_account_id FROM users WHERE id = :id LIMIT 1');
  $st->execute([':id' => $uid]);
  $value = $st->fetchColumn();
  return $value !== false && $value !== null ? (int)$value : null;
}

function get_company_account(PDO $pdo, int $companyAccountId): ?array {
  $st = $pdo->prepare(
    "SELECT ca.*,
            creator.name AS created_by_name,
            (SELECT COUNT(*) FROM company_account_contacts c WHERE c.company_account_id = ca.id AND c.invite_status <> 'inactive') AS contact_count,
            (SELECT COUNT(*) FROM quotes q WHERE q.user_id IN (SELECT c2.user_id FROM company_account_contacts c2 WHERE c2.company_account_id = ca.id)) AS quote_count
     FROM company_accounts ca
     LEFT JOIN users creator ON creator.id = ca.created_by
     WHERE ca.id = :id
     LIMIT 1"
  );
  $st->execute([':id' => $companyAccountId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function get_company_contacts(PDO $pdo, int $companyAccountId): array {
  $st = $pdo->prepare(
    "SELECT c.id, c.contact_role, c.is_primary, c.invite_status, c.created_at,
            u.id AS user_id, u.name, u.email, u.phone, u.role AS platform_role
     FROM company_account_contacts c
     INNER JOIN users u ON u.id = c.user_id
     WHERE c.company_account_id = :id
     ORDER BY c.is_primary DESC, u.name ASC"
  );
  $st->execute([':id' => $companyAccountId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}


function get_company_addresses(PDO $pdo, int $companyAccountId, bool $activeOnly = true): array {
  $sql = "SELECT * FROM company_account_addresses WHERE company_account_id = :id" . ($activeOnly ? " AND is_active = 1" : "") . " ORDER BY is_default_billing DESC, is_default_shipping DESC, label ASC, id ASC";
  $st = $pdo->prepare($sql);
  $st->execute([':id' => $companyAccountId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_default_company_address(PDO $pdo, int $companyAccountId, string $kind = 'shipping'): ?array {
  $kind = $kind === 'billing' ? 'billing' : 'shipping';
  $orderField = $kind === 'billing' ? 'is_default_billing' : 'is_default_shipping';
  $st = $pdo->prepare(
    "SELECT * FROM company_account_addresses
     WHERE company_account_id = :id AND is_active = 1
     ORDER BY {$orderField} DESC,
              CASE WHEN address_type = :kind THEN 0 ELSE 1 END,
              id ASC
     LIMIT 1"
  );
  $st->execute([':id' => $companyAccountId, ':kind' => $kind]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function format_company_address(array $address): string {
  $parts = [];
  foreach (['address_line1','address_line2','city','province','postal_code','country'] as $key) {
    $val = trim((string)($address[$key] ?? ''));
    if ($val !== '') $parts[] = $val;
  }
  return implode(', ', $parts);
}
