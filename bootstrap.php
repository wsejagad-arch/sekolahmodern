<?php

/**
 * bootstrap.php
 * Global initialization file for the application.
 * Include this file at the top of every page instead of manual includes.
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Set error reporting for development (adjust for production)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Include authentication helper (handles session initialization)
require_once __DIR__ . '/auth_helper.php';

// Include database connection
require_once __DIR__ . '/koneksi.php';

// Include common functions
require_once __DIR__ . '/functions.php';

if (function_exists('track_user_online_status')) {
    track_user_online_status($conn);
}

// Check maintenance mode
$lembaga = data_lembaga();
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
if ($lembaga['maintenance_mode'] && !in_array($currentScript, ['maintenance.php', 'admin-pusat-login.php', 'login.php', 'index.php', 'ceklogin.php'], true)) {
    if (!is_admin_pusat() && (!is_admin() || !isset($_SESSION['bypass_maintenance']))) {
        header('Location: maintenance.php');
        exit;
    }
}

// Global helper functions
function get_app_url(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // HTTP_HOST already contains the user-facing port when one is present.
    // SERVER_PORT can be derived from Apache ServerName and may point to an
    // internal/stale port, which breaks redirects on local XAMPP installs.
    if ($host !== '') {
        return "$protocol://$host";
    }

    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? 80;

    // Don't show port if it's default
    if (($protocol === 'http' && $port == 80) || ($protocol === 'https' && $port == 443)) {
        return "$protocol://$host";
    }

    return "$protocol://$host:$port";
}

function get_base_path(): string
{
    // For web execution, use SCRIPT_NAME
    if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] !== '') {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        // If script is in /jurnal directory, return /jurnal
        if (strpos($scriptName, '/jurnal') !== false) {
            return '/jurnal';
        }
        // Otherwise try to get dirname
        $path = dirname($scriptName);
        $path = str_replace('\\', '/', $path);
        if ($path === '/' || $path === '.' || $path === '') {
            return '';
        }
        return '/' . trim($path, '/');
    }
    // Default for CLI/testing - detect if running in jurnal directory
    if (strpos(__DIR__, 'jurnal') !== false) {
        return '/jurnal';
    }
    return '';
}

function asset_url(string $path): string
{
    $basePath = get_base_path();
    return get_app_url() . $basePath . '/' . ltrim($path, '/');
}

function redirect(string $path, int $code = 302): void
{
    $url = (strpos($path, 'http') === 0) ? $path : get_app_url() . get_base_path() . '/' . ltrim($path, '/');
    header("Location: $url", true, $code);
    exit;
}

// CSRF protection helper
function generate_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_token_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

// Input sanitization helpers
function clean_input(string $input): string
{
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function clean_array(array $input): array
{
    return array_map('clean_input', $input);
}

// Debug helper (only in development)
function dd(...$vars): void
{
    if (isset($_GET['debug']) || (defined('DEBUG_MODE') && DEBUG_MODE)) {
        echo '<pre style="background:#f8f9fa;padding:1rem;border:1px solid #dee2e6;margin:1rem;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}

// Log helper
function app_log(string $message, string $level = 'info'): void
{
    $logFile = __DIR__ . '/logs/app.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf("[%s] %s: %s\n", $timestamp, strtoupper($level), $message);

    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Auto-create logs directory with .htaccess protection
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

/**
 * Page routing helpers
 * Generate friendly URLs that hide actual file paths
 */

/**
 * Generate URL untuk pages/guru/*
 * @param string $page Nama file tanpa .php (misal: data-siswa)
 * @param array $params Query parameters
 * @return string URL yang friendly
 */
function guru_page(string $page, array $params = []): string
{
    $basePath = get_base_path();
    $appUrl = get_app_url();
    $safePage = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
    $suffix = php_sapi_name() === 'cli-server' ? '.php' : '';
    $url = $appUrl . $basePath . '/pages/guru/' . $safePage . $suffix;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

/**
 * Generate URL untuk pages/siswa/*
 */
function siswa_page(string $page, array $params = []): string
{
    $basePath = get_base_path();
    $appUrl = get_app_url();
    $safePage = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
    $suffix = php_sapi_name() === 'cli-server' ? '.php' : '';
    $url = $appUrl . $basePath . '/pages/siswa/' . $safePage . $suffix;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

/**
 * Generate URL untuk pages/admin/*
 */
function admin_page(string $page, array $params = []): string
{
    $basePath = get_base_path();
    $appUrl = get_app_url();
    $safePage = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
    $suffix = php_sapi_name() === 'cli-server' ? '.php' : '';
    $url = $appUrl . $basePath . '/pages/admin/' . $safePage . $suffix;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

/**
 * Generate URL untuk pages/public/*
 */
function public_page(string $page, array $params = []): string
{
    $basePath = get_base_path();
    $appUrl = get_app_url();
    $safePage = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
    $suffix = php_sapi_name() === 'cli-server' ? '.php' : '';
    $url = $appUrl . $basePath . '/pages/' . $safePage . $suffix;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

$htaccessPath = $logsDir . '/.htaccess';
if (!file_exists($htaccessPath)) {
    @file_put_contents($htaccessPath, "Deny from all\n");
}

// Global constants
define('APP_NAME', 'Sistem Jurnal');
define('APP_VERSION', '2.0.0');

// Initialize session if not already started (done by auth_helper)
// Additional app-specific session configuration can go here
