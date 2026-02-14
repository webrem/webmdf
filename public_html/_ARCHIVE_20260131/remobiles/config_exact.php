<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Configuration exacte basée sur l'export SQL de la base de données
 * Cette configuration reflète exactement la structure de votre base de données
 */

// Empêcher l'accès direct
if (!defined('APP_START')) {
    die('Accès non autorisé');
}

// Configuration de l'application
return [
    'app' => [
        'name' => 'R.E.Mobiles',
        'version' => '2.0.0',
        'description' => 'Système de gestion pour réparations mobiles',
        'author' => 'R.E.Mobiles',
        'timezone' => 'America/Cayenne',
        'debug' => false,
        'maintenance' => false,
        'url' => 'https://r-e-mobiles.com/mobiles/',
        'locale' => 'fr_FR'
    ],
    
    // Configuration de la base de données - EXACTEMENT COMME DANS L'EXPORT
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'u498346438_remshop1',
        'password' => 'Remshop104',
        'dbname' => 'u498346438_remshop1',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 30
        ]
    ],
    
    // Configuration de sécurité
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'session_name' => 'remobiles_session',
        'session_lifetime' => 7200, // 2 heures
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'password_min_length' => 8,
        'password_complexity' => true,
        'max_login_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
        'ip_block_duration' => 3600, // 1 heure
        'encryption_key' => 'default-key-change-me'
    ],
    
    // Configuration des tables - EXACTEMENT COMME DANS L'EXPORT SQL
    'tables' => [
        // Tables principales
        'users' => 'admin_users', // Utiliser admin_users comme table principale
        'admin_users' => 'admin_users',
        'users_alt' => 'users', // Table users alternative
        
        // Tables de données
        'clients' => 'clients',
        'devices' => 'devices',
        'commandes' => 'commandes',
        'historiques' => 'historiques',
        'ventes_historique' => 'ventes_historique',
        
        // Tables de stock (doublons dans la DB)
        'articles' => 'articles',
        'stock_articles' => 'stock_articles',
        
        // Tables d'acomptes (multiples tables)
        'acomptes' => 'acomptes',
        'acomptes_commandes' => 'acomptes_commandes',
        'acomptes_devices' => 'acomptes_devices',
        
        // Autres tables
        'videos' => 'videos'
    ],
    
    // Configuration des uploads
    'uploads' => [
        'max_size' => 2097152, // 2MB
        'allowed_types' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/csv',
            'video/mp4', 'video/webm', 'video/mpeg'
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'csv', 'mp4', 'webm'],
        'path' => UPLOADS_PATH,
        'temp_path' => UPLOADS_PATH . '/temp',
        'documents_path' => UPLOADS_PATH . '/documents',
        'images_path' => UPLOADS_PATH . '/images',
        'videos_path' => UPLOADS_PATH . '/videos'
    ],
    
    // Configuration des emails
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => 'noreply@remobiles.com',
        'from_name' => 'R.E.Mobiles'
    ],
    
    // Configuration des PDF
    'pdf' => [
        'generator' => 'dompdf',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'font_family' => 'Arial',
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_left' => 15,
        'margin_right' => 15
    ],
    
    // Configuration des prix
    'pricing' => [
        'default_tax_rate' => 20, // TVA 20%
        'currency' => 'EUR',
        'currency_symbol' => '€',
        'decimal_places' => 2,
        'decimal_separator' => ',',
        'thousands_separator' => ' '
    ],
    
    // Configuration des statuts - BASÉ SUR LES DONNÉES RÉELLES
    'statuses' => [
        'devices' => [
            'En attente' => 'En attente',
            'En cours' => 'En cours',
            'Terminé' => 'Terminé',
            'Livré' => 'Livré',
            'Annulé' => 'Annulé'
        ],
        'priorities' => [
            'Basse' => 'Basse',
            'Normale' => 'Normale',
            'Haute' => 'Haute',
            'Urgente' => 'Urgente'
        ],
        'commandes' => [
            'En attente' => 'En attente',
            'Confirmée' => 'Confirmée',
            'Expédiée' => 'Expédiée',
            'Reçue' => 'Reçue',
            'Annulée' => 'Annulée'
        ],
        'payment_methods' => [
            'Espèces' => 'Espèces',
            'Carte bancaire' => 'Carte bancaire',
            'Chèque' => 'Chèque',
            'Virement' => 'Virement',
            'PayPal' => 'PayPal',
            'CB' => 'CB' // Trouvé dans vos données
        ]
    ],
    
    // Configuration des rôles - BASÉ SUR LES DONNÉES RÉELLES
    'roles' => [
        'admin' => [
            'name' => 'Administrateur',
            'table' => 'admin_users',
            'permissions' => ['*'] // Toutes les permissions
        ],
        'user' => [
            'name' => 'Utilisateur',
            'table' => 'admin_users', // Même table pour compatibilité
            'permissions' => [
                'view_dashboard',
                'manage_devices',
                'manage_clients',
                'view_stock',
                'use_pos',
                'generate_pdfs'
            ]
        ]
    ],
    
    // Configuration des rapports
    'reports' => [
        'default_period' => 30, // jours
        'formats' => ['pdf', 'csv', 'excel'],
        'charts' => [
            'enabled' => true,
            'library' => 'chart.js',
            'colors' => ['#0dcaf0', '#198754', '#dc3545', '#ffc107', '#6f42c1']
        ]
    ],
    
    // Configuration du cache
    'cache' => [
        'driver' => 'file',
        'ttl' => 3600, // 1 heure
        'path' => CACHE_PATH,
        'prefix' => 'remobiles_'
    ],
    
    // Configuration des logs
    'logging' => [
        'level' => 'warning',
        'channels' => [
            'default' => [
                'handler' => 'file',
                'path' => BASE_PATH . '/logs/app.log',
                'max_files' => 10,
                'max_size' => 10485760 // 10MB
            ],
            'security' => [
                'handler' => 'file',
                'path' => BASE_PATH . '/logs/security.log',
                'max_files' => 5,
                'max_size' => 5242880 // 5MB
            ],
            'database' => [
                'handler' => 'file',
                'path' => BASE_PATH . '/logs/database.log',
                'max_files' => 5,
                'max_size' => 5242880 // 5MB
            ]
        ]
    ],
    
    // Configuration API
    'api' => [
        'enabled' => false,
        'version' => 'v1',
        'rate_limit' => 100, // requêtes par heure
        'auth_type' => 'bearer',
        'cors_enabled' => true,
        'cors_origins' => ['*']
    ],
    
    // Configuration maintenance
    'maintenance' => [
        'enabled' => false,
        'message' => 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.',
        'allowed_ips' => ['127.0.0.1', '::1'],
        'retry_after' => 3600 // 1 heure
    ],
    
    // Configuration des backups
    'backup' => [
        'enabled' => true,
        'frequency' => 'daily',
        'retention_days' => 30,
        'path' => BASE_PATH . '/backups',
        'compress' => true,
        'encrypt' => false
    ]
];

// Définir les constantes globales
foreach ($config['app'] as $key => $value) {
    $constantName = 'APP_' . strtoupper($key);
    if (!defined($constantName)) {
        define($constantName, $value);
    }
}

// Définir les constantes de tables pour compatibilité
foreach ($config['tables'] as $key => $value) {
    $constantName = 'TABLE_' . strtoupper($key);
    if (!defined($constantName)) {
        define($constantName, $value);
    }
}

// Retourner la configuration
return $config;