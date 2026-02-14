<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Migration Script for Security Tables
 * Adds login_attempts and login_logs tables without affecting existing data
 * R.E.Mobiles Database Enhancement
 */

require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>';
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Migration Tables de Sécurité - R.E.Mobiles</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">';
echo '</head>';
echo '<body class="bg-gray-100 py-8">';
echo '<div class="max-w-4xl mx-auto px-4">';
echo '<div class="bg-white rounded-lg shadow-lg p-6">';
echo '<h1 class="text-3xl font-bold text-center mb-8 text-gray-800">';
echo '<i class="fas fa-database mr-2"></i>Migration Tables de Sécurité';
echo '</h1>';

$database = Database::getInstance();
$migration_success = true;
$executed_queries = []; 

// SQL Queries for new tables
$queries = [
    'login_attempts' => "
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
            `attempt_result` enum('success','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'failed',
            `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `username` (`username`),
            KEY `ip_address` (`ip_address`),
            KEY `attempt_time` (`attempt_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'login_logs' => "
        CREATE TABLE IF NOT EXISTS `login_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
            `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `result` enum('success','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
            `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `username` (`username`),
            KEY `ip_address` (`ip_address`),
            KEY `login_time` (`login_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'add_indexes' => [
        "ALTER TABLE `admin_users` ADD INDEX `idx_username` (`username`)",
        "ALTER TABLE `admin_users` ADD INDEX `idx_status` (`status`)",
        "ALTER TABLE `clients` ADD INDEX `idx_phone` (`phone`)",
        "ALTER TABLE `devices` ADD INDEX `idx_client_id` (`client_id`)",
        "ALTER TABLE `devices` ADD INDEX `idx_status` (`status`)",
        "ALTER TABLE `stock_articles` ADD INDEX `idx_category` (`category`)",
        "ALTER TABLE `stock_articles` ADD INDEX `idx_quantity` (`quantity`)"
    ]
];

// Execute migration
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">Exécution de la migration</h2>';

try {
    // Start transaction
    $database->execute("START TRANSACTION");
    
    // Create login_attempts table
    echo '<div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded">';
    echo '<h3 class="font-semibold text-blue-800">Création table login_attempts</h3>';
    try {
        $database->execute($queries['login_attempts']);
        $executed_queries[] = 'login_attempts';
        echo '<p class="text-green-600"><i class="fas fa-check mr-1"></i>Table créée avec succès</p>';
    } catch (Exception $e) {
        echo '<p class="text-red-600"><i class="fas fa-times mr-1"></i>Erreur: ' . $e->getMessage() . '</p>';
        $migration_success = false;
    }
    echo '</div>';
    
    // Create login_logs table
    echo '<div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded">';
    echo '<h3 class="font-semibold text-blue-800">Création table login_logs</h3>';
    try {
        $database->execute($queries['login_logs']);
        $executed_queries[] = 'login_logs';
        echo '<p class="text-green-600"><i class="fas fa-check mr-1"></i>Table créée avec succès</p>';
    } catch (Exception $e) {
        echo '<p class="text-red-600"><i class="fas fa-times mr-1"></i>Erreur: ' . $e->getMessage() . '</p>';
        $migration_success = false;
    }
    echo '</div>';
    
    // Add indexes to existing tables
    echo '<div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded">';
    echo '<h3 class="font-semibold text-blue-800">Ajout d\'index pour les performances</h3>';
    $indexes_added = 0;
    
    foreach ($queries['add_indexes'] as $index_query) {
        try {
            $database->execute($index_query);
            $indexes_added++;
        } catch (Exception $e) {
            // Index may already exist, continue
            echo '<p class="text-yellow-600"><i class="fas fa-exclamation mr-1"></i>Index existe déjà ou erreur: ' . substr($index_query, 0, 50) . '...</p>';
        }
    }
    
    echo '<p class="text-green-600"><i class="fas fa-check mr-1"></i>' . $indexes_added . ' index ajoutés</p>';
    echo '</div>';
    
    // Commit or rollback
    if ($migration_success) {
        $database->execute("COMMIT");
        
        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-green-800 mb-2"><i class="fas fa-check-circle mr-2"></i>Migration réussie!</h3>';
        echo '<p class="text-green-700">Les tables de sécurité ont été créées avec succès.</p>';
        echo '</div>';
        
        // Verify created tables
        echo '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-gray-800 mb-3">Vérification des tables créées</h3>';
        
        try {
            $sql = "SHOW TABLES LIKE 'login_%'";
            $result = $database->fetchAll($sql);
            
            echo '<ul class="space-y-1">';
            foreach ($result as $row) {
                $table_name = array_values($row)[0];
                echo '<li class="flex items-center text-green-600">';
                echo '<i class="fas fa-table mr-2"></i>' . $table_name;
                echo '</li>';
            }
            echo '</ul>';
            
            // Get table structure info
            echo '<h4 class="font-semibold text-gray-700 mt-4 mb-2">Structure des nouvelles tables:</h4>';
            
            foreach (['login_attempts', 'login_logs'] as $table) {
                echo '<div class="mb-2 p-2 bg-white border rounded">';
                echo '<h5 class="font-medium text-gray-800">' . $table . '</h5>';
                
                try {
                    $sql = "DESCRIBE " . $table;
                    $columns = $database->fetchAll($sql);
                    
                    echo '<ul class="text-sm text-gray-600 ml-4">';
                    foreach ($columns as $col) {
                        echo '<li>' . $col['Field'] . ' (' . $col['Type'] . ')</li>';
                    }
                    echo '</ul>';
                } catch (Exception $e) {
                    echo '<p class="text-red-500 text-sm">Erreur: ' . $e->getMessage() . '</p>';
                }
                
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<p class="text-red-600">Erreur lors de la vérification: ' . $e->getMessage() . '</p>';
        }
        
        echo '</div>';
        
    } else {
        $database->execute("ROLLBACK");
        
        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-red-800 mb-2"><i class="fas fa-times-circle mr-2"></i>Migration échouée</h3>';
        echo '<p class="text-red-700">Des erreurs ont été rencontrées. Aucune modification n\'a été appliquée.</p>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800 mb-2">Erreur lors de la migration</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Next steps
echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">';
echo '<h3 class="font-semibold text-blue-800 mb-3">Prochaines étapes</h3>';
echo '<ul class="space-y-2 text-blue-700">';
echo '<li class="flex items-start"><i class="fas fa-arrow-right mr-2 mt-1"></i>Exécuter test_architecture.php pour valider</li>';
echo '<li class="flex items-start"><i class="fas fa-arrow-right mr-2 mt-1"></i>Adapter les pages de connexion existantes</li>';
echo '<li class="flex items-start"><i class="fas fa-arrow-right mr-2 mt-1"></i>Implémenter les modèles dans les pages existantes</li>';
echo '<li class="flex items-start"><i class="fas fa-arrow-right mr-2 mt-1"></i>Tester l\'authentification avec les utilisateurs existants</li>';
echo '</ul>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';