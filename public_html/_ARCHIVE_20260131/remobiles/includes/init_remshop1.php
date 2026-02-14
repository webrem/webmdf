<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Application initialization for remshop1 database
 * This file initializes the complete system with the new database
 */

// Start session with security
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_name('remshop_session');
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique

// Set timezone
date_default_timezone_set('Europe/Paris');

// Define base constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', BASE_PATH . '/includes');
}

if (!defined('PAGES_PATH')) {
    define('PAGES_PATH', BASE_PATH . '/pages');
}

if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', BASE_PATH . '/assets');
}

if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', BASE_PATH . '/uploads');
}

// Load configuration
require_once INCLUDES_PATH . '/config_remshop1.php';

// Load core classes
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/security.php';
require_once INCLUDES_PATH . '/models.php';
require_once INCLUDES_PATH . '/helpers.php';

// Initialize global instances
try {
    // Database connection
    $db = new Database();
    
    // Authentication system
    $auth = new Auth($db);
    
    // Security utilities
    $security = new Security();
    
    // Model factory
    $modelFactory = new ModelFactory($db);
    
} catch (Exception $e) {
    // Handle initialization errors
    error_log("Application initialization error: " . $e->getMessage());
    
    if (php_sapi_name() !== 'cli') {
        // Don't show errors in production
        if ($config['app']['environment'] === 'production') {
            die("Une erreur s'est produite. Veuillez contacter l'administrateur.");
        } else {
            die("Erreur d'initialisation: " . $e->getMessage());
        }
    }
}

// Security headers
if (php_sapi_name() !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com https://cdn.jsdelivr.net; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; ";
    $csp .= "img-src 'self' data: https:; ";
    $csp .= "connect-src 'self';";
    header("Content-Security-Policy: $csp");
}

// Utility functions
function base_url($path = '') {
    static $baseUrl = null;
    
    if ($baseUrl === null) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
        $baseUrl = rtrim($baseUrl, '/');
    }
    
    return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
}

function asset_url($path) {
    return base_url('assets/' . ltrim($path, '/'));
}

function upload_url($path) {
    return base_url('uploads/' . ltrim($path, '/'));
}

function format_date($date, $format = null) {
    global $config;
    if ($format === null) {
        $format = $config['date_format'];
    }
    
    if (empty($date)) {
        return '';
    }
    
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function format_currency($amount, $currency = null) {
    global $config;
    if ($currency === null) {
        $currency = $config['currency_symbol'];
    }
    
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

function redirect($url, $code = 302) {
    http_response_code($code);
    header("Location: $url");
    exit();
}

function csrf_token() {
    global $auth;
    return $auth->getCSRFToken();
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function old($key, $default = '') {
    return $_SESSION['old_input'][$key] ?? $default;
}

function flash($key, $default = null) {
    if (isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }
    return $default;
}

function flash_error($message) {
    $_SESSION['flash']['error'] = $message;
}

function flash_success($message) {
    $_SESSION['flash']['success'] = $message;
}

function flash_warning($message) {
    $_SESSION['flash']['warning'] = $message;
}

// Auto-logout for inactive sessions
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    redirect('login.php');
}

$_SESSION['last_activity'] = time();

// Log user activity
if (isset($auth) && $auth->isLoggedIn()) {
    $user_id = $auth->getCurrentUser()['id'];
    $action = 'page_view';
    $details = $_SERVER['REQUEST_URI'] ?? 'Unknown page';
    
    try {
        $activityModel = $modelFactory->create('activity_logs');
        $activityModel->create([
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        // Silently fail for activity logging
    }
}