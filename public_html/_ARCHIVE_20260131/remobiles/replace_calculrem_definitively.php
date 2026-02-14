<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script pour remplacer définitivement u498346438_remshop1 par u498346438_remshop1
 * Remplacement global de toutes les configurations anciennes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Remplacement Définitif: u498346438_remshop1 → u498346438_remshop1 ===\n\n";
echo "Ancienne configuration: u498346438_remshop1 / Remshop104\n";
echo "Nouvelle configuration: u498346438_remshop1 / Remshop104\n\n";

// Configurations à remplacer
$replacements = [
    'u498346438_remshop1' => 'u498346438_remshop1',
    'Remshop104' => 'Remshop104',
    'u498346438_remshop1' => 'u498346438_remshop1', // Au cas où
];

// Fonction pour remplacer dans un fichier
function replace_in_file($file_path, $replacements) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $content = file_get_contents($file_path);
    $original_content = $content;
    
    // Remplacer toutes les occurrences
    $updated = false;
    foreach ($replacements as $old => $new) {
        // Remplacer toutes les occurrences exactes
        $new_content = str_replace($old, $new, $content);
        if ($new_content !== $content) {
            $content = $new_content;
            $updated = true;
        }
    }
    
    if ($updated) {
        if (file_put_contents($file_path, $content)) {
            return true;
        }
    }
    
    return false;
}

// Obtenir tous les fichiers PHP dans le répertoire
$php_files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['php', 'js', 'json', 'xml', 'yml', 'yaml'])) {
        $php_files[] = $file->getPathname();
    }
}

echo "Fichiers trouvés: " . count($php_files) . "\n\n";

// Traiter chaque fichier
$updated_count = 0;
$skipped_count = 0;
$errors_count = 0;

foreach ($php_files as $file_path) {
    // Ignorer certains répertoires
    if (strpos($file_path, 'vendor') !== false || 
        strpos($file_path, 'node_modules') !== false ||
        strpos($file_path, '.git') !== false ||
        strpos($file_path, 'cache') !== false) {
        continue;
    }
    
    echo "Traitement: $file_path\n";
    
    if (replace_in_file($file_path, $replacements)) {
        echo "   ✅ Mis à jour avec succès\n";
        $updated_count++;
    } else {
        // Vérifier si le fichier contient des occurrences à remplacer
        $content = file_get_contents($file_path);
        $has_occurrences = false;
        foreach ($replacements as $old => $new) {
            if (strpos($content, $old) !== false) {
                $has_occurrences = true;
                break;
            }
        }
        
        if ($has_occurrences) {
            echo "   ❌ Erreur lors de la mise à jour\n";
            $errors_count++;
        } else {
            echo "   ⚠️ Aucune occurrence trouvée\n";
            $skipped_count++;
        }
    }
}

echo "\n=== RÉSUMÉ DU REMPLACEMENT ===\n\n";
echo "Fichiers mis à jour: $updated_count\n";
echo "Fichiers sans modification: $skipped_count\n";
echo "Erreurs: $errors_count\n\n";

// Créer un rapport détaillé
$report = "=== Rapport de Remplacement Définitif ===\n";
$report .= "Date: " . date('Y-m-d H:i:s') . "\n";
$report .= "Remplacement: u498346438_remshop1 → u498346438_remshop1\n";
$report .= "Mot de passe: Remshop104 → Remshop104\n";
$report .= "Fichiers traités: " . count($php_files) . "\n";
$report .= "Fichiers mis à jour: $updated_count\n";
$report .= "Fichiers sans modification: $skipped_count\n";
$report .= "Erreurs: $errors_count\n\n";

if (file_put_contents('replacement_definitive_report.txt', $report)) {
    echo "✅ Rapport créé: replacement_definitive_report.txt\n";
}

// Créer la configuration finale correcte
$config_final_correct = '<?php
/**
 * Configuration finale avec u498346438_remshop1
 * Configuration mise à jour après remplacement définitif de u498346438_remshop1
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
?>';

if (file_put_contents('config_definitive_correct.php', $config_final_correct)) {
    echo "✅ Configuration finale créée: config_definitive_correct.php\n";
}

// Créer un script de test pour vérifier le remplacement
echo "\nCréation du script de vérification...\n";

$test_script = '<?php
/**
 * Test pour vérifier que le remplacement de u498346438_remshop1 a fonctionné
 */

$host = "localhost";
$dbname = "u498346438_remshop1";
$username = "u498346438_remshop1";
$password = "Remshop104";

echo "=== Vérification du Remplacement de u498346438_remshop1 ===\n\n";
echo "Configuration attendue:\n";
echo "- Base de données: $dbname\n";
echo "- Utilisateur: $username\n";
echo "- Mot de passe: $password\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ SUCCÈS! Le remplacement a fonctionné!\n";
    echo "✅ La connexion à $dbname fonctionne!\n";
    echo "✅ L\'ancienne configuration u498346438_remshop1 a été remplacée!\n";
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "❌ Le remplacement n\'a pas fonctionné correctement\n";
    echo "❌ Vérifiez que tous les fichiers ont été mis à jour\n";
}
?>';

if (file_put_contents('test_calculrem_replacement.php', $test_script)) {
    echo "✅ Script de test créé: test_calculrem_replacement.php\n";
}

// Créer un script de mise à jour automatique pour les fichiers spécifiques
echo "\nCréation du script de mise à jour automatique...\n";

$update_script = '<?php
/**
 * Script de mise à jour automatique pour les configurations spécifiques
 * Met à jour les configurations qui ont encore u498346438_remshop1
 */

$configurations_to_update = [
    "/home/u498346438/domains/r-e-mobiles.com/public_html/remobiles/install.php" => [
        "find" => [
            "define(
    'APP_START', true);

// Configuration de la base de données
\$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];",
            "u498346438_remshop1",
            "Remshop104"
        ],
        "replace" => [
            "define(
    'APP_START', true);

// Configuration de la base de données
\$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];",
            "u498346438_remshop1",
            "Remshop104"
        ]
    ]
];

echo "=== Mise à jour automatique des configurations ===\n\n";

foreach ($configurations_to_update as $file_path => $config) {
    if (file_exists($file_path)) {
        echo "Mise à jour de: $file_path\n";
        
        $content = file_get_contents($file_path);
        $new_content = $content;
        
        foreach ($config["find"] as $index => $find) {
            $replace = $config["replace"][$index];
            $new_content = str_replace($find, $replace, $new_content);
        }
        
        if ($new_content !== $content) {
            if (file_put_contents($file_path, $new_content)) {
                echo "   ✅ Configuration mise à jour avec succès\n";
            } else {
                echo "   ❌ Erreur lors de la mise à jour\n";
            }
        } else {
            echo "   ⚠️ Aucune modification nécessaire\n";
        }
    } else {
        echo "Fichier non trouvé: $file_path\n";
    }
}

echo "\nMise à jour terminée!\n";
?>';

if (file_put_contents('update_specific_configs.php', $update_script)) {
    echo "✅ Script de mise à jour créé: update_specific_configs.php\n";
}

// Résumé final
echo "\n=== REMPLACEMENT TERMINÉ ===\n\n";

if ($updated_count > 0) {
    echo "🎉 REMPLACEMENT TERMINÉ AVEC SUCCÈS! 🎉\n\n";
    echo "✅ Toutes les occurrences de 'u498346438_remshop1' ont été remplacées par 'u498346438_remshop1'\n";
    echo "✅ Toutes les occurrences de 'Remshop104' ont été remplacées par 'Remshop104'\n";
    echo "✅ $updated_count fichiers ont été mis à jour\n";
    echo "✅ La configuration finale est disponible dans config_definitive_correct.php\n";
    echo "✅ Vous pouvez maintenant utiliser la base de données u498346438_remshop1\n\n";
    
    echo "🚀 Prochaines étapes:\n";
    echo "1. Testez la connexion: php test_calculrem_replacement.php\n";
    echo "2. Configurez MySQL si nécessaire: php setup_mysql_user.php\n";
    echo "3. Installez le système: php setup_basic.php\n";
    echo "4. Vérifiez l'installation: php verify_setup.php\n\n";
    
} else {
    echo "⚠️ AUCUN REMPLACEMENT EFFECTUÉ\n\n";
    echo "Aucune occurrence de 'u498346438_remshop1' ou 'Remshop104' n'a été trouvée\n";
    echo "Vérifiez que les fichiers contiennent bien les informations à remplacer\n";
}

echo "Consultez replacement_definitive_report.txt pour plus de détails.\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remplacement Définitif - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px);}
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .btn { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; font-size: 16px; }
        .btn:hover { background: #218838; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        pre { background: rgba(0,0,0,0.3); padding: 20px; border-radius: 5px; overflow-x: auto; }
        .summary { text-align: center; margin: 30px 0; padding: 30px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔁 Remplacement Définitif</h1>
        <p style="text-align: center;">Remplacement de u498346438_remshop1 par u498346438_remshop1</p>
        
        <?php if ($updated_count > 0): ?>
            <div class="summary" style="background: rgba(40, 167, 69, 0.2);">
                <h2 class="success">🎉 REMPLACEMENT TERMINÉ!</h2>
                <p>Toutes les occurrences ont été remplacées avec succès!</p>
            </div>
            
            <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3 class="info">📊 Statistiques</h3>
                <div style="text-align: center;">
                    <p><strong>Fichiers mis à jour:</strong> <?php echo $updated_count; ?></p>
                    <p><strong>Fichiers sans modification:</strong> <?php echo $skipped_count; ?></p>
                    <p><strong>Erreurs:</strong> <?php echo $errors_count; ?></p>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🚀 Prochaines Étapes</h3>
                <p>Le projet est maintenant configuré avec u498346438_remshop1</p>
                <div style="margin: 20px 0;">
                    <a href="test_calculrem_replacement.php" class="btn">Tester la Configuration</a>
                    <a href="setup_mysql_user.php" class="btn-secondary">Configurer MySQL</a>
                    <a href="setup_basic.php" class="btn">Installer le Système</a>
                </div>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3>📁 Fichiers Créés</h3>
                <ul>
                    <li><strong>config_definitive_correct.php</strong> - Configuration finale</li>
                    <li><strong>test_calculrem_replacement.php</strong> - Test du remplacement</li>
                    <li><strong>replacement_definitive_report.txt</strong> - Rapport détaillé</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="summary" style="background: rgba(255, 193, 7, 0.2);">
                <h2 class="warning">⚠️ AUCUN REMPLACEMENT EFFECTUÉ</h2>
                <p>Aucune occurrence de 'u498346438_remshop1' n'a été trouvée dans les fichiers.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🔍 Vérification</h3>
                <p>Assurez-vous que les fichiers contiennent bien les informations à remplacer.</p>
                <a href="test_calculrem_replacement.php" class="btn">Tester Quand Même</a>
            </div>
        <?php endif; ?>
        
        <div style="background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="warning">⚠️ Important</h3>
            <p>Configuration mise à jour:</p>
            <ul>
                <li><strong>Base de données:</strong> u498346438_remshop1</li>
                <li><strong>Utilisateur:</strong> u498346438_remshop1</li>
                <li><strong>Mot de passe:</strong> Remshop104</li>
                <li><strong>Hôte:</strong> localhost</li>
            </ul>
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>📋 Fichiers Traités</h3>
            <p>Tous les fichiers PHP, JS, JSON, XML, YML du projet ont été analysés et mis à jour si nécessaire.</p>
            <p>Consultez replacement_definitive_report.txt pour le rapport détaillé.</p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 14px; opacity: 0.8;">
                R.E.Mobiles Système de Gestion<br>
                Configuration mise à jour pour u498346438_remshop1
            </p>
        </div>
    </div>
</body>
</html>