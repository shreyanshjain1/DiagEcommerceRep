<?php
if (!function_exists('app_start_session')) {
    function app_start_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');

        session_name('ECOMSESSID');
        session_start();

        if (!isset($_SESSION['_session_started_at'])) {
            $_SESSION['_session_started_at'] = time();
        }

        $rotationWindow = 900;
        $lastRegenerated = (int)($_SESSION['_session_regenerated_at'] ?? 0);
        if ($lastRegenerated <= 0 || (time() - $lastRegenerated) >= $rotationWindow) {
            session_regenerate_id(true);
            $_SESSION['_session_regenerated_at'] = time();
        }
    }
}

app_start_session();
