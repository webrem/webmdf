<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Test de compatibilité avec l'ancienne base de données
 */

define('APP_START', true);

echo "<!DOCTYPE html>\n<html lang='fr'>\n<head>\n";
echo "<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<title>Test Compatibilité Ancienne DB - R.E.Mobiles</title>\n";
echo "<script src='https://cdn.tailwindcss.com'></script>\n";
echo "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>\n";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'>\n";
echo "</head>\n<body class='bg-gray-900 text-white py-8'>\n";
echo "<div class='container mx-auto px-4'>\n";
echo "<h1 class='text-3xl font-bold text-center mb-8 text-cyan-400'>🧪 Test Compatibilité Ancienne Base de Données</h1>\n";

// Test 1: Vérifier la connexion à la base de données
$tests = [];

try {
    $conn = new mysqli("localhost", "u498346438_remshop1", "Remshop104", "u498346438_remshop1");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        $tests[] = ['test' => 'Connexion DB', 'status' => 'ERREUR', 'message' => $conn->connect_error];
    } else {
        $tests[] = ['test' => 'Connexion DB', 'status' => 'OK', 'message' => 'Connexion réussie'];
    }
} catch (Exception $e) {
    $tests[] = ['test' => 'Connexion DB', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 2: Vérifier les tables principales
$requiredTables = ['admin_users', 'historiques', 'clients', 'devices', 'stock_articles'];
$existingTables = [];

$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $existingTables[] = $row[0];
}

$missingTables = array_diff($requiredTables, $existingTables);
if (empty($missingTables)) {
    $tests[] = ['test' => 'Tables principales', 'status' => 'OK', 'message' => 'Toutes les tables requises existent'];
} else {
    $tests[] = ['test' => 'Tables principales', 'status' => 'ERREUR', 'message' => 'Tables manquantes: ' . implode(', ', $missingTables)];
}

// Test 3: Vérifier la table admin_users
try {
    $result = $conn->query("DESCRIBE admin_users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['id', 'username', 'password'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        $tests[] = ['test' => 'Structure admin_users', 'status' => 'OK', 'message' => 'Structure correcte'];
    } else {
        $tests[] = ['test' => 'Structure admin_users', 'status' => 'ERREUR', 'message' => 'Colonnes manquantes: ' . implode(', ', $missingColumns)];
    }
} catch (Exception $e) {
    $tests[] = ['test' => 'Structure admin_users', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 4: Vérifier s'il y a des utilisateurs
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_users");
    $row = $result->fetch_assoc();
    $userCount = $row['count'];
    
    if ($userCount > 0) {
        $tests[] = ['test' => 'Utilisateurs existants', 'status' => 'OK', 'message' => "$userCount utilisateur(s) trouvé(s)"];
    } else {
        $tests[] = ['test' => 'Utilisateurs existants', 'status' => 'WARNING', 'message' => 'Aucun utilisateur trouvé'];
    }
} catch (Exception $e) {
    $tests[] = ['test' => 'Utilisateurs existants', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 5: Vérifier l'utilisateur admin
try {
    $result = $conn->query("SELECT username FROM admin_users WHERE username = 'admin'");
    if ($result && $result->num_rows > 0) {
        $tests[] = ['test' => 'Utilisateur admin', 'status' => 'OK', 'message' => 'Utilisateur admin existe'];
    } else {
        $tests[] = ['test' => 'Utilisateur admin', 'status' => 'WARNING', 'message' => 'Utilisateur admin non trouvé'];
    }
} catch (Exception $e) {
    $tests[] = ['test' => 'Utilisateur admin', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 6: Vérifier la table historiques
try {
    $result = $conn->query("DESCRIBE historiques");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['id', 'piece', 'prix_achat', 'quantite', 'main_oeuvre', 'client_nom', 'client_tel', 'doc_type', 'prix_final'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        $tests[] = ['test' => 'Structure historiques', 'status' => 'OK', 'message' => 'Structure correcte'];
    } else {
        $tests[] = ['test' => 'Structure historiques', 'status' => 'ERREUR', 'message' => 'Colonnes manquantes: ' . implode(', ', $missingColumns)];
    }
} catch (Exception $e) {
    $tests[] = ['test' => 'Structure historiques', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 7: Vérifier les données dans historiques
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM historiques");
    $row = $result->fetch_assoc();
    $historiquesCount = $row['count'];
    
    $tests[] = ['test' => 'Données historiques', 'status' => 'OK', 'message' => "$historiquesCount enregistrement(s) trouvé(s)"];
} catch (Exception $e) {
    $tests[] = ['test' => 'Données historiques', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 8: Vérifier la compatibilité PDO
try {
    require_once 'includes/database.php';
    $db = Database::getInstance();
    $connPDO = $db->getConnection();
    
    if ($connPDO) {
        $tests[] = ['test' => 'Compatibilité PDO', 'status' => 'OK', 'message' => 'Connexion PDO réussie'];
    } else {
        $tests[] = ['test' => 'Compatibilité PDO', 'status' => 'ERREUR', 'message' => 'Impossible de créer la connexion PDO'];
    }
} catch (Exception $e) {
    $tests[] = ['test' => 'Compatibilité PDO', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Test 9: Vérifier le système d'authentification
try {
    require_once 'includes/auth.php';
    $auth = new Auth();
    
    $tests[] = ['test' => 'Système Auth', 'status' => 'OK', 'message' => 'Système d\'authentification initialisé'];
} catch (Exception $e) {
    $tests[] = ['test' => 'Système Auth', 'status' => 'ERREUR', 'message' => $e->getMessage()];
}

// Fermer la connexion
$conn->close();

// Afficher les résultats
echo "<div class='bg-gray-800 rounded-lg p-6 mb-6'>\n";
echo "<h2 class='text-xl font-bold mb-4 text-cyan-400'>📋 Résultats des tests de compatibilité</h2>\n";
echo "<div class='space-y-3'>\n";

$totalTests = count($tests);
$passedTests = 0;

foreach ($tests as $test) {
    $statusClass = match($test['status']) {
        'OK' => 'text-green-400',
        'ERREUR' => 'text-red-400',
        'WARNING' => 'text-yellow-400',
        default => 'text-gray-400'
    };
    
    $iconClass = match($test['status']) {
        'OK' => 'bi-check-circle-fill text-green-400',
        'ERREUR' => 'bi-x-circle-fill text-red-400',
        'WARNING' => 'bi-exclamation-triangle-fill text-yellow-400',
        default => 'bi-question-circle-fill text-gray-400'
    };
    
    if ($test['status'] === 'OK') {
        $passedTests++;
    }
    
    echo "<div class='flex items-center justify-between p-3 bg-gray-700 rounded'>\n";
    echo "<div class='flex items-center'>\n";
    echo "<i class='bi {$iconClass} mr-3'></i>\n";
    echo "<span>{$test['test']}</span>\n";
    echo "</div>\n";
    echo "<div class='text-right'>\n";
    echo "<span class='{$statusClass} font-semibold'>{$test['status']}</span>\n";
    echo "<div class='text-xs text-gray-400 mt-1'>{$test['message']}</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}

echo "</div>\n";
echo "</div>\n";

// Résumé global
$successRate = round(($passedTests / $totalTests) * 100);
$globalStatus = $successRate >= 80 ? 'success' : ($successRate >= 60 ? 'warning' : 'error');
$globalIcon = $globalStatus === 'success' ? 'bi-check-circle-fill' : ($globalStatus === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill');
$globalColor = $globalStatus === 'success' ? 'text-green-400' : ($globalStatus === 'warning' ? 'text-yellow-400' : 'text-red-400');

echo "<div class='bg-gray-800 rounded-lg p-6 mb-6 text-center'>\n";
echo "<i class='bi {$globalIcon} text-6xl {$globalColor} mb-4'></i>\n";
echo "<h2 class='text-2xl font-bold mb-2 {$globalColor}'>", ucfirst($globalStatus), "</h2>\n";
echo "<p class='text-gray-300'>Taux de compatibilité : {$successRate}% ({$passedTests}/{$totalTests} tests passés)</p>\n";
echo "</div>\n";

// Recommandations
if ($globalStatus === 'success') {
    echo "<div class='bg-green-900 bg-opacity-20 border border-green-500 border-opacity-30 rounded-lg p-6 mb-6'>\n";
    echo "<h3 class='text-lg font-bold text-green-400 mb-3'>✅ Compatibilité parfaite !</h3>\n";
    echo "<p class='text-green-200 mb-4'>Votre ancienne base de données est entièrement compatible avec la nouvelle version.</p>\n";
    echo "<div class='space-y-2'>\n";
    echo "<p class='text-green-200'>• <a href='migrate_old_db.php' class='underline'>Exécuter la migration pour ajouter les nouvelles fonctionnalités</a></p>\n";
    echo "<p class='text-green-200'>• <a href='login.php' class='underline'>Tester la connexion</a></p>\n    echo "<p class='text-green-200'>• <a href='dashboard.php' class='underline'>Accéder au tableau de bord</a></p>\n";
    echo "</div>\n";
    echo "</div>\n";
} else {
    echo "<div class='bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-6 mb-6'>\n";
    echo "<h3 class='text-lg font-bold text-red-400 mb-3'>❌ Problèmes de compatibilité détectés</h3>\n";
    echo "<p class='text-red-200 mb-4'>Des problèmes ont été détectés avec votre base de données.</p>\n";
    echo "<div class='space-y-2'>\n";
    echo "<p class='text-red-200'>• Vérifiez que votre base de données est accessible</p>\n";
    echo "<p class='text-red-200'>• Assurez-vous que les tables principales existent</p>\n";
    echo "<p class='text-red-200'>• Vérifiez les permissions de l'utilisateur MySQL</p>\n";
    echo "<p class='text-red-200'>• Exécutez le script d'installation original si nécessaire</p>\n";
    echo "</div>\n";
    echo "</div>\n";
}

// Informations détaillées
echo "<div class='bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg p-6'>\n";
echo "<h3 class='text-lg font-bold text-blue-400 mb-3'>ℹ️ Informations détaillées</h3>\n";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4 text-sm'>\n";
echo "<div>\n";
echo "<p class='text-blue-200'><strong>Base de données :</strong> u498346438_remshop1</p>\n";
echo "<p class='text-blue-200'><strong>Serveur :</strong> localhost</p>\n";
echo "<p class='text-blue-200'><strong>Charset :</strong> utf8mb4</p>\n";
echo "</div>\n";
echo "<div>\n";
echo "<p class='text-blue-200'><strong>Tables principales :</strong> " . implode(', ', $requiredTables) . "</p>\n";
echo "<p class='text-blue-200'><strong>Tables existantes :</strong> " . count($existingTables) . "</p>\n";
echo "<p class='text-blue-200'><strong>Date du test :</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "</div>\n";
echo "</div>\n";
echo "</body>\n</html>\n";

// Logger les résultats du test
$logContent = date('Y-m-d H:i:s') . " - Test compatibilité DB: {$successRate}% de réussite\n";
file_put_contents('test_old_db.log', $logContent, FILE_APPEND | LOCK_EX);
?>