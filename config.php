<?php
// Global security configuration for the application.
// Use strict session and HTTP security headers.

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) == 443;

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_samesite', 'Lax');
if ($secure) {
    ini_set('session.cookie_secure', 1);
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if ($secure) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self';");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// The rest of the application can include config.php to get standardized security behavior.
