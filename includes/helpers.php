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

function ensure_storage_dir(string $relativeDir): string {
  $relativeDir = trim(str_replace('\\', '/', $relativeDir), '/');
  $full = realpath(__DIR__ . '/..');
  if ($full === false) {
    throw new RuntimeException('Unable to resolve application root');
  }
  $target = $full . '/' . $relativeDir;
  if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
    throw new RuntimeException('Unable to create storage directory: ' . $relativeDir);
  }
  return $target;
}

function quote_document_title(array $quote, string $kind = 'Quotation Snapshot'): string {
  $number = (string)($quote['quote_number'] ?? 'Quote');
  return trim($kind . ' - ' . $number);
}

function quote_public_download_url(int $quoteId, int $documentId): string {
  return url('pages/quote-document-download.php?id=' . $quoteId . '&doc=' . $documentId);
}

function get_quote_documents(PDO $pdo, int $quoteId, bool $customerVisibleOnly = false): array {
  $sql = "SELECT d.*, u.name AS created_by_name
          FROM quote_documents d
          LEFT JOIN users u ON u.id = d.created_by
          WHERE d.quote_id = :qid";
  if ($customerVisibleOnly) {
    $sql .= " AND d.is_customer_visible = 1";
  }
  $sql .= " ORDER BY d.created_at DESC, d.id DESC";
  $st = $pdo->prepare($sql);
  $st->execute([':qid' => $quoteId]);
  return $st->fetchAll() ?: [];
}

function generate_quote_snapshot(PDO $pdo, int $quoteId, ?int $createdBy = null): array {
  $q = $pdo->prepare("SELECT * FROM quotes WHERE id=:id");
  $q->execute([':id' => $quoteId]);
  $quote = $q->fetch();
  if (!$quote) {
    throw new RuntimeException('Quote not found');
  }

  $itemsSt = $pdo->prepare("
    SELECT qi.qty, qi.unit_price, p.name, p.brand, p.sku
    FROM quote_items qi
    LEFT JOIN products p ON p.id = qi.product_id
    WHERE qi.quote_id = :qid
    ORDER BY qi.id ASC
  ");
  $itemsSt->execute([':qid' => $quoteId]);
  $items = $itemsSt->fetchAll() ?: [];

  require_once __DIR__ . '/settings.php';

  $isQuoted = (($quote['status'] ?? '') === 'quoted');
  $subtotal = 0.0;
  foreach ($items as $it) {
    $subtotal += ((int)$it['qty']) * ((float)($it['unit_price'] ?? 0));
  }
  $shipping = (float)($quote['shipping_fee'] ?? 0);
  $over = (float)($quote['overhead_charge'] ?? 0);
  $other = (float)($quote['other_expenses'] ?? 0);
  $inst = (float)($quote['installation_expenses'] ?? 0);
  $grand = (float)($quote['total'] ?? ($subtotal + $shipping + $over + $other + $inst));

  $site = (string)setting('site_name', 'Pharmastar Diagnostics');
  $hotline = (string)setting('contact_hotline', '+639691229230');
  $email = (string)setting('contact_email', 'admin@example.com');
  $wa = (string)setting('contact_whatsapp', '+639453462354');

  $safeQuoteNumber = preg_replace('/[^A-Za-z0-9\-]+/', '-', (string)$quote['quote_number']);
  $relativeDir = 'uploads/quote_documents/' . date('Y') . '/' . date('m');
  $dir = ensure_storage_dir($relativeDir);
  $filename = strtolower($safeQuoteNumber . '-snapshot-' . date('Ymd-His') . '.html');
  $fullPath = $dir . '/' . $filename;
  $relativePath = $relativeDir . '/' . $filename;

  ob_start();
  ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo e($isQuoted ? 'Official Quotation' : 'RFQ Snapshot'); ?> <?php echo e($quote['quote_number']); ?></title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;color:#0f172a;margin:28px;background:#fff}
  .top{display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
  .brand{font-size:20px;font-weight:800}
  .muted{color:#64748b}
  .badge{display:inline-block;padding:4px 10px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:12px;font-weight:700}
  table{width:100%;border-collapse:collapse;margin-top:18px}
  th,td{border:1px solid #e2e8f0;padding:10px 12px;font-size:13px;vertical-align:top}
  th{background:#f8fafc;text-align:left}
  .right{text-align:right}
  .card{border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin-top:18px}
  .notes{white-space:pre-line;line-height:1.6}
  .footer{margin-top:20px;font-size:12px;color:#64748b}
</style>
</head>
<body>
  <div class="top">
    <div>
      <div class="brand"><?php echo e($site); ?></div>
      <div class="muted">Hotline: <?php echo e($hotline); ?> • WhatsApp: <?php echo e($wa); ?> • Email: <?php echo e($email); ?></div>
    </div>
    <div style="text-align:right">
      <div><strong>Reference:</strong> <?php echo e($quote['quote_number']); ?></div>
      <div style="margin-top:6px"><span class="badge"><?php echo e(strtoupper((string)$quote['status'])); ?></span></div>
      <div class="muted" style="margin-top:6px">Generated: <?php echo e(date('Y-m-d H:i:s')); ?></div>
    </div>
  </div>

  <div class="card">
    <div><strong>Customer:</strong> <?php echo e($quote['name']); ?></div>
    <?php if (!empty($quote['company'])): ?><div><strong>Company:</strong> <?php echo e($quote['company']); ?></div><?php endif; ?>
    <div><strong>Email:</strong> <?php echo e($quote['email']); ?></div>
    <?php if (!empty($quote['phone'])): ?><div><strong>Phone:</strong> <?php echo e($quote['phone']); ?></div><?php endif; ?>
  </div>

  <h2 style="margin-top:22px"><?php echo e($isQuoted ? 'Official Quotation Snapshot' : 'RFQ Snapshot'); ?></h2>
  <table>
    <?php if ($isQuoted): ?>
      <tr><th>Product</th><th style="width:80px">Qty</th><th style="width:140px" class="right">Unit</th><th style="width:160px" class="right">Line Total</th></tr>
    <?php else: ?>
      <tr><th>Product</th><th style="width:90px">Qty</th></tr>
    <?php endif; ?>
    <?php foreach ($items as $it): ?>
      <tr>
        <td>
          <strong><?php echo e($it['name'] ?: 'Unknown product'); ?></strong><br>
          <span class="muted"><?php echo e($it['brand'] ?: ''); ?><?php if (!empty($it['sku'])): ?> • <?php echo e($it['sku']); ?><?php endif; ?></span>
        </td>
        <?php if ($isQuoted): ?>
          <?php $qty = (int)$it['qty']; $unit = (float)($it['unit_price'] ?? 0); $line = $qty * $unit; ?>
          <td><?php echo $qty; ?></td>
          <td class="right">₱<?php echo number_format($unit, 2); ?></td>
          <td class="right">₱<?php echo number_format($line, 2); ?></td>
        <?php else: ?>
          <td><?php echo (int)$it['qty']; ?></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if ($isQuoted): ?>
    <div class="card">
      <h3 style="margin:0 0 10px">Commercial Summary</h3>
      <table style="margin-top:0">
        <tr><th class="right">Subtotal</th><td class="right">₱<?php echo number_format($subtotal, 2); ?></td></tr>
        <tr><th class="right">Overhead Charge</th><td class="right">₱<?php echo number_format($over, 2); ?></td></tr>
        <tr><th class="right">Other Expenses</th><td class="right">₱<?php echo number_format($other, 2); ?></td></tr>
        <tr><th class="right">Installation Expenses</th><td class="right">₱<?php echo number_format($inst, 2); ?></td></tr>
        <tr><th class="right">Shipping</th><td class="right">₱<?php echo number_format($shipping, 2); ?></td></tr>
        <tr><th class="right">Grand Total</th><td class="right"><strong>₱<?php echo number_format($grand, 2); ?></strong></td></tr>
      </table>
    </div>

    <div class="card">
      <h3 style="margin:0 0 10px">Quotation Terms</h3>
      <div class="notes"><strong>Valid Until:</strong> <?php echo e($quote['valid_until'] ?? '—'); ?></div>
      <div class="notes"><strong>Lead Time:</strong> <?php echo e($quote['lead_time'] ?? '—'); ?></div>
      <div class="notes"><strong>Warranty:</strong> <?php echo e($quote['warranty'] ?? '—'); ?></div>
      <div class="notes"><strong>Payment Terms:</strong><br><?php echo nl2br(e($quote['payment_terms'] ?? '—')); ?></div>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3 style="margin:0 0 10px">Customer Notes</h3>
    <div class="notes"><?php echo e($quote['notes'] ?: '—'); ?></div>
  </div>

  <div class="footer">
    Stored snapshot generated by the RFQ workflow. This document can be referenced for customer follow-up and recruiter-facing repository review.
  </div>
</body>
</html>
<?php
  $html = (string)ob_get_clean();
  file_put_contents($fullPath, $html);
  $fileSize = @filesize($fullPath) ?: 0;

  $insert = $pdo->prepare("
    INSERT INTO quote_documents
      (quote_id, document_type, title, storage_mode, file_path, mime_type, file_size, created_by, is_customer_visible)
    VALUES
      (:qid, 'quotation_html', :title, 'generated', :path, 'text/html', :size, :created_by, 1)
  ");
  $title = quote_document_title($quote, $isQuoted ? 'Official Quotation Snapshot' : 'RFQ Snapshot');
  $insert->execute([
    ':qid' => $quoteId,
    ':title' => $title,
    ':path' => $relativePath,
    ':size' => $fileSize,
    ':created_by' => $createdBy,
  ]);

  return [
    'id' => (int)$pdo->lastInsertId(),
    'title' => $title,
    'file_path' => $relativePath,
    'mime_type' => 'text/html',
    'file_size' => $fileSize,
  ];
}
