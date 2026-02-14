<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Complete System Validation for R.E.Mobiles
 * Tests all components of the new architecture
 */

// Start with basic test
require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>';
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Validation Complète - R.E.Mobiles</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
echo '</head>';
echo '<body class="bg-gray-100 py-8">';
echo '<div class="max-w-6xl mx-auto px-4">';
echo '<div class="bg-white rounded-lg shadow-lg p-6">';
echo '<h1 class="text-3xl font-bold text-center mb-8 text-gray-800">';
echo '<i class="fas fa-check-double mr-2"></i>Validation Complète du Système';
echo '</h1>';

$tests = [];
$total_tests = 0;
$passed_tests = 0;

// Test 1: Core Architecture Files
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">1. Fichiers d\'Architecture</h2>';

$core_files = [
    'includes/config_exact.php',
    'includes/database.php',
    'includes/auth_exact.php',
    'includes/security.php',
    'includes/models.php',
    'includes/init.php'
];

foreach ($core_files as $file) {
    $total_tests++;
    if (file_exists(__DIR__ . '/' . $file)) {
        $tests[] = [
            'name' => $file,
            'status' => 'success',
            'message' => 'Fichier présent',
            'details' => 'Taille: ' . number_format(filesize(__DIR__ . '/' . $file)) . ' octets'
        ];
        $passed_tests++;
    } else {
        $tests[] = [
            'name' => $file,
            'status' => 'error',
            'message' => 'Fichier manquant',
            'details' => 'Ce fichier est essentiel pour le système'
        ];
    }
}

// Test 2: Modern Pages
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">2. Pages Modernes</h2>';

$modern_pages = [
    'login_modern.php',
    'dashboard_modern.php',
    'clients_modern.php',
    'devices_modern.php',
    'stock_modern.php',
    'test_architecture.php',
    'migrate_security_tables.php'
];

foreach ($modern_pages as $page) {
    $total_tests++;
    if (file_exists(__DIR__ . '/' . $page)) {
        $tests[] = [
            'name' => $page,
            'status' => 'success',
            'message' => 'Page créée',
            'details' => 'Page moderne avec glass morphism'
        ];
        $passed_tests++;
    } else {
        $tests[] = [
            'name' => $page,
            'status' => 'error',
            'message' => 'Page manquante',
            'details' => 'Page non trouvée'
        ];
    }
}

// Test 3: Database Connection
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">3. Connexion Base de Données</h2>';

$total_tests++;
try {
    $database = Database::getInstance();
    $database->execute("SELECT 1");
    $tests[] = [
        'name' => 'Connexion PDO',
        'status' => 'success',
        'message' => 'Connecté avec succès',
        'details' => 'Base: ' . CONFIG['database']['name']
    ];
    $passed_tests++;
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Connexion PDO',
        'status' => 'error',
        'message' => 'Erreur de connexion',
        'details' => $e->getMessage()
    ];
}

// Test 4: Authentication System
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">4. Système d\'Authentification</h2>';

$total_tests++;
try {
    $auth = new Auth($database);
    $csrf_token = $auth->generateCSRFToken();
    
    if ($csrf_token && isset($_SESSION['csrf_token'])) {
        $tests[] = [
            'name' => 'Génération CSRF Token',
            'status' => 'success',
            'message' => 'Token généré avec succès',
            'details' => 'Longueur: ' . strlen($csrf_token) . ' caractères'
        ];
        $passed_tests++;
    } else {
        throw new Exception('Token non généré');
    }
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Génération CSRF Token',
        'status' => 'error',
        'message' => 'Erreur lors de la génération',
        'details' => $e->getMessage()
    ];
}

// Test 5: Models
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">5. Modèles de Base de Données</h2>';

$models_to_test = ['user', 'client', 'device', 'stockArticle'];
foreach ($models_to_test as $model_name) {
    $total_tests++;
    try {
        $model = ModelFactory::$model_name();
        $count = $model->count();
         
        $tests[] = [
            'name' => 'Modèle ' . ucfirst($model_name),
            'status' => 'success',
            'message' => 'Modèle fonctionnel',
            'details' => $count . ' enregistrements trouvés'
        ];
        $passed_tests++;
    } catch (Exception $e) {
        $tests[] = [
            'name' => 'Modèle ' . ucfirst($model_name),
            'status' => 'error',
            'message' => 'Erreur du modèle',
            'details' => $e->getMessage()
        ];
    }
}

// Test 6: Security Headers
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">6. Headers de Sécurité</h2>';

$total_tests++;
try {
    $headers = headers_list();
    $security_headers = array_filter($headers, function($header) {
        return strpos($header, 'X-') === 0 || strpos($header, 'Content-Security-Policy') === 0;
    });
    
    if (count($security_headers) >= 3) {
        $tests[] = [
            'name' => 'Headers de sécurité',
            'status' => 'success',
            'message' => 'Headers configurés',
            'details' => count($security_headers) . ' headers actifs'
        ];
        $passed_tests++;
    } else {
        throw new Exception('Headers insuffisants');
    }
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Headers de sécurité',
        'status' => 'error',
        'message' => 'Headers manquants',
        'details' => $e->getMessage()
    ];
}

// Test 7: Configuration
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">7. Configuration</h2>';

$total_tests++;
try {
    if (isset(CONFIG['database']['host']) && 
        isset(CONFIG['tables']['users']) && 
        isset(CONFIG['security']['session_timeout'])) {
        $tests[] = [
            'name' => 'Configuration complète',
            'status' => 'success',
            'message' => 'Configuration valide',
            'details' => 'Base de données et sécurité configurées'
        ];
        $passed_tests++;
    } else {
        throw new Exception('Configuration incomplète');
    }
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Configuration complète',
        'status' => 'error',
        'message' => 'Configuration invalide',
        'details' => $e->getMessage()
    ];
}

// Test 8: Helper Functions
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">8. Fonctions Utilitaires</h2>';

$total_tests++;
try {
    $sanitized = sanitizeInput('<script>alert("test")</script>');
    if ($sanitized === '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;') {
        $tests[] = [
            'name' => 'Fonction sanitizeInput',
            'status' => 'success',
            'message' => 'Fonction fonctionnelle',
            'details' => 'Sanitisation correcte'
        ];
        $passed_tests++;
    } else {
        throw new Exception('Sanitisation incorrecte');
    }
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Fonction sanitizeInput',
        'status' => 'error',
        'message' => 'Erreur de fonction',
        'details' => $e->getMessage()
    ];
}

// Test 9: Database Tables
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">9. Tables de Base</h2>';

$required_tables = ['admin_users', 'clients', 'devices', 'stock_articles', 'historiques'];
foreach ($required_tables as $table) {
    $total_tests++;
    try {
        $sql = "SHOW TABLES LIKE :table";
        $result = $database->fetch($sql, [':table' => $table]);
        
        if ($result) {
            $tests[] = [
                'name' => 'Table ' . $table,
                'status' => 'success',
                'message' => 'Table existante',
                'details' => 'Table trouvée dans la base'
            ];
            $passed_tests++;
        } else {
            throw new Exception('Table manquante');
        }
    } catch (Exception $e) {
        $tests[] = [
            'name' => 'Table ' . $table,
            'status' => 'error',
            'message' => 'Table non trouvée',
            'details' => $e->getMessage()
        ];
    }
}

// Test 10: Modern Pages Content
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">10. Contenu des Pages Modernes</h2>';

$modern_pages_content = [
    'login_modern.php' => ['glass', 'anime.min.js', 'tailwindcss'],
    'dashboard_modern.php' => ['glass', 'plotly.js', 'tailwindcss'],
    'clients_modern.php' => ['glass', 'tailwindcss', 'ModelFactory'],
    'devices_modern.php' => ['glass', 'tailwindcss', 'ModelFactory'],
    'stock_modern.php' => ['glass', 'tailwindcss', 'ModelFactory']
];

foreach ($modern_pages_content as $page => $required_elements) {
    $total_tests++;
    if (file_exists(__DIR__ . '/' . $page)) {
        $content = file_get_contents(__DIR__ . '/' . $page);
        $missing_elements = [];
        
        foreach ($required_elements as $element) {
            if (!strpos($content, $element)) {
                $missing_elements[] = $element;
            }
        }
        
        if (empty($missing_elements)) {
            $tests[] = [
                'name' => 'Contenu ' . $page,
                'status' => 'success',
                'message' => 'Contenu complet',
                'details' => 'Tous les éléments requis présents'
            ];
            $passed_tests++;
        } else {
            $tests[] = [
                'name' => 'Contenu ' . $page,
                'status' => 'warning',
                'message' => 'Éléments manquants',
                'details' => 'Manque: ' . implode(', ', $missing_elements)
            ];
        }
    } else {
        $tests[] = [
            'name' => 'Contenu ' . $page,
            'status' => 'error',
            'message' => 'Page non trouvée',
            'details' => 'Impossible de vérifier le contenu'
        ];
    }
}

// Display results
echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">';
foreach ($tests as $test) {
    $icon = $test['status'] === 'success' ? 'fa-check-circle text-green-500' : 
             ($test['status'] === 'warning' ? 'fa-exclamation-triangle text-yellow-500' : 'fa-times-circle text-red-500');
    $bg_color = $test['status'] === 'success' ? 'bg-green-50' : 
                ($test['status'] === 'warning' ? 'bg-yellow-50' : 'bg-red-50');
    $border_color = $test['status'] === 'success' ? 'border-green-200' : 
                    ($test['status'] === 'warning' ? 'border-yellow-200' : 'border-red-200');
    $text_color = $test['status'] === 'success' ? 'text-green-800' : 
                  ($test['status'] === 'warning' ? 'text-yellow-800' : 'text-red-800');
    
    echo "<div class='{$bg_color} border {$border_color} rounded-lg p-4'>\n";
    echo "<div class='flex items-start'>\n";
    echo "<i class='fas {$icon} text-xl mr-3 mt-1'></i>\n";
    echo "<div class='flex-1'>\n";
    echo "<h3 class='font-semibold {$text_color}'>{$test['name']}</h3>\n";
    echo "<p class='text-sm {$text_color} opacity-75'>{$test['message']}</p>\n";
    echo "<p class='text-xs {$text_color} opacity-60 mt-1'>{$test['details']}</p>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}
echo '</div>';

// Summary
echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center mb-6">';
echo '<h2 class="text-2xl font-bold text-blue-800 mb-2">Résumé des Tests</h2>';
echo '<div class="text-4xl font-bold text-blue-600 mb-2">';
echo "{$passed_tests} / {$total_tests}";
echo '</div>';
echo '<p class="text-blue-700">Tests réussis</p>';

if ($passed_tests === $total_tests) {
    echo '<div class="mt-4">';
    echo '<i class="fas fa-trophy text-6xl text-yellow-500"></i>';
    echo '<p class="text-green-600 font-semibold mt-2">Système prêt pour la production!</p>';
    echo '</div>';
} elseif ($passed_tests >= $total_tests * 0.8) {
    echo '<div class="mt-4">';
    echo '<i class="fas fa-check-circle text-6xl text-green-500"></i>';
    echo '<p class="text-green-600 font-semibold mt-2">Système fonctionnel avec quelques avertissements</p>';
    echo '</div>';
} else {
    echo '<div class="mt-4">';
    echo '<i class="fas fa-exclamation-triangle text-6xl text-yellow-500"></i>';
    echo '<p class="text-yellow-600 font-semibold mt-2">Des problèmes doivent être résolus</p>';
    echo '</div>';
}
echo '</div>';

// Next Steps
echo '<div class="bg-green-50 border border-green-200 rounded-lg p-6">';
echo '<h3 class="text-lg font-semibold text-green-800 mb-3">';
echo '<i class="fas fa-arrow-right mr-2"></i>Prochaines Étapes\n';
echo '</h3>\n';
echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">\n';
echo '<div class="space-y-2 text-green-700">\n';
echo '<h4 class="font-semibold">Pour commencer:</h4>\n';
echo '<ul class="space-y-1 text-sm">\n';
echo '<li><i class="fas fa-check mr-2"></i>Exécutez migrate_security_tables.php</li>\n';
echo '<li><i class="fas fa-check mr-2"></i>Testez login_modern.php</li>\n';
echo '<li><i class="fas fa-check mr-2"></i>Vérifiez dashboard_modern.php</li>\n';
echo '<li><i class="fas fa-check mr-2"></i>Testez les nouvelles fonctionnalités</li>\n';
echo '</ul>\n';
echo '</div>\n';
echo '<div class="space-y-2 text-green-700">\n';
echo '<h4 class="font-semibold">Pour les fichiers originaux:</h4>\n';
echo '<ul class="space-y-1 text-sm">\n';
echo '<li><i class="fas fa-check mr-2"></i>Utilisez adapt_original_files.php</li>\n';
echo '<li><i class="fas fa-check mr-2"></i>Suivez MIGRATION_GUIDE.md</li>\n';
echo '<li><i class="fas fa-check mr-2"></i>Testez chaque fichier</li>\n';
echo '<li><i class="fas fa-check mr-2"></i>Sauvegardez avant modification</li>\n';
echo '</ul>\n';
echo '</div>\n';
echo '</div>\n';
echo '</div>\n';

// System Information
echo '<div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">';
echo '<h3 class="text-lg font-semibold text-gray-800 mb-3">';
echo '<i class="fas fa-info-circle mr-2"></i>Informations Système\n';
echo '</h3>\n';
echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">\n';
echo '<div><strong>PHP Version:</strong> ' . PHP_VERSION . '</div>\n';
echo '<div><strong>Système:</strong> ' . PHP_OS . '</div>\n';
echo '<div><strong>Architecture:</strong> ' . (PHP_INT_SIZE === 8 ? '64-bit' : '32-bit') . '</div>\n';
echo '</div>\n';
echo '</div>\n';

echo '</div>\n';
echo '</div>\n';
echo '</body>\n';
echo '</html>\n';

// Save validation report
$validation_report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $total_tests,
    'passed_tests' => $passed_tests,
    'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
    'tests' => $tests,
    'php_version' => PHP_VERSION,
    'system' => PHP_OS
];

file_put_contents(__DIR__ . '/validation_report.json', json_encode($validation_report, JSON_PRETTY_PRINT));
?>