<?php
// Update these for your server
$DB_HOST = 'localhost';
$DB_NAME = 'pharmastar_db';
$DB_USER = 'pitc_admin';
$DB_PASS = 'LbT}WiDlM$@X';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// Global site config
$SITE_NAME = "Pharmastar Diagnostics";
$ADMIN_EMAIL = "shreyanshjain@pharmastar.com.ph";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
