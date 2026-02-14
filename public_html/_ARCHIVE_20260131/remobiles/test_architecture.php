<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Architecture Test Suite for R.E.Mobiles
 * Validates the new modern architecture against the existing database structure
 */

require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>';
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Test Architecture R.E.Mobiles</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">';
echo '</head>';
echo '<body class="bg-gray-100 py-8">';
echo '<div class="max-w-6xl mx-auto px-4">';
echo '<div class="bg-white rounded-lg shadow-lg p-6">';
echo '<h1 class="text-3xl font-bold text-center mb-8 text-gray-800">';
echo '<i class="fas fa-tools mr-2"></i>Test Architecture R.E.Mobiles';
echo '</h1>';

$tests = [];
$total_tests = 0;
$passed_tests = 0;

// Test 1: Database Connection
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">1. Connexion Base de Données</h2>';
try {
    $database = Database::getInstance();
    $tests[] = ['name' => 'Connexion PDO', 'status' => 'success', 'message' => 'Connecté avec succès'];
    $passed_tests++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Connexion PDO', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 2: Configuration
echo '<div class="mb-4">'; 
try {
    if (isset(CONFIG['database']['host']) && isset(CONFIG['tables'])) {
        $tests[] = ['name' => 'Configuration chargée', 'status' => 'success', 'message' => 'Configuration valide'];
        $passed_tests++;
    } else {
        throw new Exception('Configuration incomplète');
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Configuration chargée', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 3: Check Database Tables
echo '<div class="mb-4">';
try {
    $required_tables = ['admin_users', 'clients', 'devices', 'stock_articles', 'historiques'];
    $existing_tables = [];
    
    $sql = "SHOW TABLES";
    $tables = $database->fetchAll($sql);
    
    foreach ($tables as $table) {
        $existing_tables[] = array_values($table)[0];
    }
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    
    if (empty($missing_tables)) {
        $tests[] = ['name' => 'Tables de base', 'status' => 'success', 'message' => 'Toutes les tables requises existent'];
        $passed_tests++;
    } else {
        throw new Exception('Tables manquantes: ' . implode(', ', $missing_tables));
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Tables de base', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 4: Authentication System
echo '<div class="mb-4">';
try {
    $auth = new Auth($database);
    $csrf_token = $auth->generateCSRFToken();
    
    if ($csrf_token && isset($_SESSION['csrf_token'])) {
        $tests[] = ['name' => 'Système d\'authentification', 'status' => 'success', 'message' => 'CSRF token généré'];
        $passed_tests++;
    } else {
        throw new Exception('Erreur génération CSRF token');
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Système d\'authentification', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 5: Model Factory
echo '<div class="mb-4">';
try {
    ModelFactory::setDatabase($database);
    $userModel = ModelFactory::user();
    
    if ($userModel instanceof UserModel) {
        $tests[] = ['name' => 'Model Factory', 'status' => 'success', 'message' => 'Factory fonctionne'];
        $passed_tests++;
    } else {
        throw new Exception('Erreur instanciation modèle');
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Model Factory', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 6: User Model
echo '<div class="mb-4">';
try {
    $userModel = ModelFactory::user();
    $count = $userModel->count();
    
    $tests[] = ['name' => 'Modèle Utilisateur', 'status' => 'success', 'message' => "{$count} utilisateurs trouvés"];
    $passed_tests++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Modèle Utilisateur', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 7: Client Model
echo '<div class="mb-4">';
try {
    $clientModel = ModelFactory::client();
    $count = $clientModel->count();
    
    $tests[] = ['name' => 'Modèle Clients', 'status' => 'success', 'message' => "{$count} clients trouvés"];
    $passed_tests++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Modèle Clients', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 8: Device Model
echo '<div class="mb-4">';
try {
    $deviceModel = ModelFactory::device();
    $count = $deviceModel->count();
    
    $tests[] = ['name' => 'Modèle Appareils', 'status' => 'success', 'message' => "{$count} appareils trouvés"];
    $passed_tests++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Modèle Appareils', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 9: Stock Articles Model
echo '<div class="mb-4">';
try {
    $stockModel = ModelFactory::stockArticle();
    $count = $stockModel->count();
    
    $tests[] = ['name' => 'Modèle Stock', 'status' => 'success', 'message' => "{$count} articles trouvés"];
    $passed_tests++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Modèle Stock', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Test 10: Security Headers
echo '<div class="mb-4">';
try {
    $headers = headers_list();
    $security_headers = array_filter($headers, function($header) {
        return strpos($header, 'X-') === 0 || strpos($header, 'Content-Security-Policy') === 0;
    });
    
    if (count($security_headers) >= 3) {
        $tests[] = ['name' => 'Headers de sécurité', 'status' => 'success', 'message' => count($security_headers) . ' headers actifs'];
        $passed_tests++;
    } else {
        throw new Exception('Headers de sécurité manquants');
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Headers de sécurité', 'status' => 'error', 'message' => $e->getMessage()];
}
$total_tests++;

// Display results
echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">';
foreach ($tests as $test) {
    $icon = $test['status'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500';
    $bg_color = $test['status'] === 'success' ? 'bg-green-50' : 'bg-red-50';
    $border_color = $test['status'] === 'success' ? 'border-green-200' : 'border-red-200';
    
    echo "<div class=\"{$bg_color} border {$border_color} rounded-lg p-4\">";
    echo "<div class=\"flex items-center\">";
    echo "<i class=\"fas {$icon} text-xl mr-3\"></i>";
    echo "<div>";
    echo "<h3 class=\"font-semibold text-gray-800\">{$test['name']}</h3>";
    echo "<p class=\"text-sm text-gray-600\">{$test['message']}</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
echo '</div>';

// Summary
echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">';
echo '<h2 class="text-2xl font-bold text-blue-800 mb-2">Résumé des Tests</h2>';
echo '<div class="text-4xl font-bold text-blue-600 mb-2">';
echo "{$passed_tests} / {$total_tests}";
echo '</div>';
echo '<p class=\"text-blue-700\">Tests réussis</p>';

if ($passed_tests === $total_tests) {
    echo '<div class=\"mt-4\">';
    echo '<i class=\"fas fa-check-circle text-6xl text-green-500\"></i>';
    echo '<p class=\"text-green-600 font-semibold mt-2\">Architecture validée avec succès!</p>';
    echo '</div>';
} else {
    echo '<div class=\"mt-4\">';
    echo '<i class=\"fas fa-exclamation-triangle text-6xl text-yellow-500\"></i>';
    echo '<p class=\"text-yellow-600 font-semibold mt-2\">Des erreurs ont été détectées</p>';
    echo '</div>';
}
echo '</div>';

// Database Schema Info
echo '<div class=\"mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6\">';
echo '<h3 class=\"text-lg font-semibold text-gray-800 mb-4\">';
echo '<i class=\"fas fa-database mr-2\"></i>Informations Base de Données';
echo '</h3>';

try {
    $sql = "SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = :dbname 
            ORDER BY TABLE_NAME";
    
    $tables = $database->fetchAll($sql, [':dbname' => CONFIG['database']['name']]);
    
    echo '<div class=\"overflow-x-auto\">';
    echo '<table class=\"min-w-full bg-white border border-gray-200 rounded-lg\">';
    echo '<thead class=\"bg-gray-100\">';
    echo '<tr>';
    echo '<th class=\"px-4 py-2 text-left text-sm font-semibold text-gray-700\">Table</th>';
    echo '<th class=\"px-4 py-2 text-left text-sm font-semibold text-gray-700\">Lignes</th>';
    echo '<th class=\"px-4 py-2 text-left text-sm font-semibold text-gray-700\">Créée le</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($tables as $table) {
        echo '<tr class=\"border-t border-gray-100\">';
        echo '<td class=\"px-4 py-2 text-sm text-gray-800\">' . htmlspecialchars($table['TABLE_NAME']) . '</td>';
        echo '<td class=\"px-4 py-2 text-sm text-gray-600\">' . number_format($table['TABLE_ROWS']) . '</td>';
        echo '<td class=\"px-4 py-2 text-sm text-gray-600\">' . date('d/m/Y H:i', strtotime($table['CREATE_TIME'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<p class=\"text-red-600\">Erreur lors de la récupération des informations: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Next Steps
echo '<div class=\"mt-6 bg-green-50 border border-green-200 rounded-lg p-6\">';
echo '<h3 class=\"text-lg font-semibold text-green-800 mb-4\">';
echo '<i class=\"fas fa-arrow-right mr-2\"></i>Prochaines Étapes';
echo '</h3>';
echo '<ul class=\"space-y-2 text-green-700\">';
echo '<li class=\"flex items-start\"><i class=\"fas fa-check text-green-500 mr-2 mt-1\"></i>Architecture de base validée</li>';
echo '<li class=\"flex items-start\"><i class=\"fas fa-clock text-yellow-500 mr-2 mt-1\"></i>Adapter les fichiers existants (83 fichiers)</li>';
echo '<li class=\"flex items-start\"><i class=\"fas fa-clock text-yellow-500 mr-2 mt-1\"></i>Créer les pages modernes avec glass morphism</li>';
echo '<li class=\"flex items-start\"><i class=\"fas fa-clock text-yellow-500 mr-2 mt-1\"></i>Implémenter les tests complets</li>';
echo '<li class=\"flex items-start\"><i class=\"fas fa-clock text-yellow-500 mr-2 mt-1\"></i>Déployer en production</li>';
echo '</ul>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';

// Clean output buffer
ob_end_flush();