<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Configuration finale pour u498346438_remshop1
 * Configuration de production avec les bonnes informations de base de données
 */

$config = [
    'database' => [
        'host' => 'localhost',
        'name' => 'u498346438_remshop1',
        'user' => 'u498346438_remshop1',
        'pass' => 'Remshop104',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'session_name' => 'remshop_session',
        'session_lifetime' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
        'password_min_length' => 8,
        'password_complexity' => true,
    ],
    
    'app' => [
        'name' => 'R.E.Mobiles - Système de Gestion',
        'version' => '2.0.0',
        'environment' => 'production', // development, testing, production
        'debug' => false,
        'timezone' => 'Europe/Paris',
        'language' => 'fr',
    ],
    
    'tables' => [
        // Users and authentication
        'users' => 'users',
        'user_roles' => 'user_roles',
        'user_sessions' => 'user_sessions',
        'login_attempts' => 'login_attempts',
        
        // Clients
        'clients' => 'clients',
        'client_contacts' => 'client_contacts',
        'client_addresses' => 'client_addresses',
        
        // Devices and repairs
        'devices' => 'devices',
        'device_models' => 'device_models',
        'device_brands' => 'device_brands',
        'repairs' => 'repairs',
        'repair_status' => 'repair_status',
        'repair_parts' => 'repair_parts',
        
        // Stock and inventory
        'stock_articles' => 'stock_articles',
        'articles' => 'articles',
        'stock_categories' => 'stock_categories',
        'suppliers' => 'suppliers',
        'stock_movements' => 'stock_movements',
        
        // Financial
        'invoices' => 'invoices',
        'invoice_items' => 'invoice_items',
        'payments' => 'payments',
        'quotes' => 'quotes',
        'quote_items' => 'quote_items',
        
        // History and logs
        'historiques' => 'historiques',
        'activity_logs' => 'activity_logs',
        'system_logs' => 'system_logs',
        
        // Settings
        'settings' => 'settings',
        'notifications' => 'notifications',
        'templates' => 'templates',
    ],
    
    'uploads' => [
        'path' => __DIR__ . '/../uploads/',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'text/plain',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'],
    ],
    
    'pagination' => [
        'default_per_page' => 20,
        'options' => [10, 20, 50, 100],
    ],
    
    'date_format' => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i',
    'currency' => 'EUR',
    'currency_symbol' => '€',
];

// Environment-specific overrides
if ($config['app']['environment'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $config['app']['debug'] = true;
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    $config['app']['debug'] = false;
}

// Define constants
foreach ($config as $section => $values) {
    foreach ($values as $key => $value) {
        $constant_name = strtoupper($section . '_' . $key);
        if (!defined($constant_name)) {
            define($constant_name, is_array($value) ? json_encode($value) : $value);
        }
    }
}

// Version constant
if (!defined('APP_VERSION')) {
    define('APP_VERSION', $config['app']['version']);
}

return $config;