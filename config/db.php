<?php
/**
 * Database bootstrap.
 *
 * Priority:
 * 1) config/db.local.php (gitignored local machine/server config)
 * 2) Environment variables
 * 3) Safe placeholders below
 */

$defaultConfig = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'change_me_database',
    'DB_USER' => 'change_me_user',
    'DB_PASS' => 'change_me_password',
    'SITE_NAME' => 'Pharmastar Diagnostics',
    'ADMIN_EMAIL' => 'admin@example.com',
];

$localConfigFile = __DIR__ . '/db.local.php';
if (is_file($localConfigFile)) {
    require $localConfigFile;
}

$DB_HOST = getenv('DB_HOST') ?: ($DB_HOST ?? $defaultConfig['DB_HOST']);
$DB_NAME = getenv('DB_NAME') ?: ($DB_NAME ?? $defaultConfig['DB_NAME']);
$DB_USER = getenv('DB_USER') ?: ($DB_USER ?? $defaultConfig['DB_USER']);
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($DB_PASS ?? $defaultConfig['DB_PASS']);
$SITE_NAME = getenv('SITE_NAME') ?: ($SITE_NAME ?? $defaultConfig['SITE_NAME']);
$ADMIN_EMAIL = getenv('ADMIN_EMAIL') ?: ($ADMIN_EMAIL ?? $defaultConfig['ADMIN_EMAIL']);

$missingDefaults = [
    'change_me_database',
    'change_me_user',
    'change_me_password',
];

if (in_array($DB_NAME, $missingDefaults, true) || in_array($DB_USER, $missingDefaults, true) || in_array($DB_PASS, $missingDefaults, true)) {
    http_response_code(500);
    exit('Database is not configured. Copy config/db.local.example.php to config/db.local.php and update your credentials.');
}

try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config/db.local.php or your environment variables.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
