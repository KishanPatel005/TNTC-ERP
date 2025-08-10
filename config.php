<?php
// Construction ERP - Global Configuration
// Update the database credentials below to match your MySQL setup.

// SECURITY: Never commit real credentials to version control.

// Database connection settings
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'construction_erp');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
// If serving over HTTPS, uncomment the next line
// ini_set('session.cookie_secure', 1);

// Application settings
define('APP_NAME', 'Construction ERP');

// Compute base URL automatically (works on Apache + PHP)
function app_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return rtrim("{$scheme}://{$host}{$dir}", '/');
}

// CSRF token settings
define('CSRF_TOKEN_KEY', 'csrf_token');

date_default_timezone_set('UTC');

// Error reporting (adjust for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
