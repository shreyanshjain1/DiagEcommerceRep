<?php
// Settings helper (DB-backed, with safe fallbacks)
// - Reads from `settings` table
// - If the table does not exist (older DB), returns defaults without crashing

require_once __DIR__ . '/../config/db.php';

/**
 * Get a setting value by key.
 */
function setting(string $key, $default = null) {
  if (!array_key_exists('__settings_cache', $GLOBALS)) {
    $GLOBALS['__settings_cache'] = null;
  }
  $cache = $GLOBALS['__settings_cache'];

  if ($cache === null) {
    $cache = [];
    try {
      global $pdo;
      $st = $pdo->query("SELECT `key`,`value` FROM settings");
      foreach ($st as $r) {
        $cache[$r['key']] = $r['value'];
      }
    } catch (Throwable $e) {
      $cache = [];
    }
    $GLOBALS['__settings_cache'] = $cache;
  }

  if (!array_key_exists($key, $cache)) return $default;
  $v = $cache[$key];
  if ($v === null || $v === '') return $default;

  $t = trim((string)$v);
  if ($t !== '' && ($t[0] === '{' || $t[0] === '[')) {
    $j = json_decode($t, true);
    if (json_last_error() === JSON_ERROR_NONE) return $j;
  }

  return $v;
}

/**
 * Upsert settings (admin use).
 */
function setting_set(array $pairs): bool {
  try {
    global $pdo;
    $pdo->beginTransaction();
    $st = $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(:k,:v)
                        ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    foreach ($pairs as $k => $v) {
      $st->execute([':k' => (string)$k, ':v' => (string)$v]);
    }
    $pdo->commit();
    // reset cache immediately
    $GLOBALS['__settings_cache'] = null;
    return true;
  } catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    return false;
  }
}

/**
 * Convert a PH mobile number (09xxxxxxxxx / 9xxxxxxxxx / +63xxxxxxxxxx) to WhatsApp wa.me digits.
 */
function ph_to_wa_digits(string $phone): string {
  $p = preg_replace('/[^0-9+]/', '', trim($phone));
  if ($p === '') return '';
  if (strpos($p, '+') === 0) {
    $digits = preg_replace('/\D/', '', $p);
    return $digits;
  }
  $digits = preg_replace('/\D/', '', $p);
  if (strpos($digits, '63') === 0) return $digits;
  if (strpos($digits, '0') === 0) return '63' . substr($digits, 1);
  if (strlen($digits) === 10 && strpos($digits, '9') === 0) return '63' . $digits;
  return $digits;
}

function wa_link(string $phone, string $message = ''): string {
  $digits = ph_to_wa_digits($phone);
  if ($digits === '') return '#';
  $base = 'https://wa.me/' . $digits;
  if ($message !== '') {
    $base .= '?text=' . rawurlencode($message);
  }
  return $base;
}

