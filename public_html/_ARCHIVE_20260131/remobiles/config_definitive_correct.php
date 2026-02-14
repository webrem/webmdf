<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Configuration finale avec u498346438_remshop1
 * Configuration mise à jour après remplacement définitif de u498346438_calculrem
 */

return [
    "database" => [
        "host" => "localhost",
        "name" => "u498346438_remshop1",
        "user" => "u498346438_remshop1",
        "pass" => "Remshop104",
        "charset" => "utf8mb4",
        "options" => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    "security" => [
        "csrf_token_name" => "csrf_token",
        "session_name" => "remshop_session",
        "session_lifetime" => 3600,
        "max_login_attempts" => 5,
        "lockout_time" => 900,
        "password_min_length" => 8,
        "password_complexity" => true,
    ],
    
    "app" => [
        "name" => "R.E.Mobiles - Système de Gestion",
        "version" => "2.0.0",
        "environment" => "production",
        "debug" => false,
        "timezone" => "Europe/Paris",
        "language" => "fr",
    ]
];
?>