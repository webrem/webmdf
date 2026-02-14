<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Configuration globale du site R.E.Mobiles
 * Sécurisée et centralisée
 */

// Sécurité : empêcher l'accès direct
if (!defined('APP_START')) {
    die('Accès non autorisé');
}

// Configuration de la base de données
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'username' => $_ENV['DB_USERNAME'] ?? 'u498346438_remshop1',
        'password' => $_ENV['DB_PASSWORD'] ?? 'Remshop104',
        'dbname' => $_ENV['DB_NAME'] ?? 'u498346438_remshop1',
        'charset' => 'utf8mb4'
    ],
    
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'session_name' => 'remobiles_session',
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ],
    
    'app' => [
        'name' => 'R.E.Mobiles',
        'version' => '2.0.0',
        'timezone' => 'America/Cayenne',
        'debug' => false
    ],
    
    'paths' => [
        'uploads' => '/uploads/',
        'assets' => '/assets/',
        'cache' => '/cache/'
    ]
];