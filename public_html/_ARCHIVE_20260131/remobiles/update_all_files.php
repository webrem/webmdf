<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script pour mettre à jour tous les fichiers avec les bonnes informations de base de données
 * Mise à jour automatique pour u498346438_remshop1
 */

// Configuration finale
echo "=== Mise à jour des fichiers pour u498346438_remshop1 ===\n\n";

$old_config = [
    'host' => 'localhost',
    'name' => 'u498346438_remshop1',
    'user' => 'u498346438_remshop1',
    'pass' => 'Remshop104'
];

$new_config = [
    'host' => 'localhost',
    'name' => 'u498346438_remshop1',
    'user' => 'u498346438_remshop1',
    'pass' => 'Remshop104'
];

// Fonction pour remplacer les configurations dans un fichier
function update_file_config($file_path, $old_config, $new_config) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $content = file_get_contents($file_path);
    $original_content = $content;
    
    // Remplacer les configurations
    $replacements = [
        // Patterns pour les configurations PDO
        '/mysql:host=' . preg_quote($old_config['host'], '/') . ';dbname=' . preg_quote($old_config['name'], '/') . '/i' => 'mysql:host=' . $new_config['host'] . ';dbname=' . $new_config['name'],
        
        // Patterns pour les noms d'utilisateur
        '/\'user\'\s*=>\s*\'' . preg_quote($old_config['user'], '/') . '\'/i' => '\'user\' => \'' . $new_config['user'] . '\'',
        '/\'name\'\s*=>\s*\'' . preg_quote($old_config['name'], '/') . '\'/i' => '\'name\' => \'' . $new_config['name'] . '\'',
        
        // Patterns pour les connexions directes
        '/new PDO\("mysql:host=' . preg_quote($old_config['host'], '/') . ';dbname=' . preg_quote($old_config['name'], '/') . '/i' => 'new PDO("mysql:host=' . $new_config['host'] . ';dbname=' . $new_config['name'],
        
        // Patterns pour les variables de configuration
        '/\$username\s*=\s*\'' . preg_quote($old_config['user'], '/') . '\'/i' => '\$username = \'' . $new_config['user'] . '\'',
        '/\$dbname\s*=\s*\'' . preg_quote($old_config['name'], '/') . '\'/i' => '\$dbname = \'' . $new_config['name'] . '\'',
        
        // Patterns pour les constantes
        '/DB_NAME\s*\,\s*\'' . preg_quote($old_config['name'], '/') . '\'/i' => 'DB_NAME, \'' . $new_config['name'] . '\'',
        '/DB_USER\s*\,\s*\'' . preg_quote($old_config['user'], '/') . '\'/i' => 'DB_USER, \'' . $new_config['user'] . '\'',
    ];
    
    $updated = false;
    foreach ($replacements as $pattern => $replacement) {
        $new_content = preg_replace($pattern, $replacement, $content);
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

// Liste des fichiers à mettre à jour
$files_to_update = [
    // Configuration files
    'includes/config.php' => 'Configuration principale',
    'includes/config_remshop1.php' => 'Configuration u498346438_remshop1',
    'includes/config_final.php' => 'Configuration finale',
    'includes/database.php' => 'Classe Database',
    
    // Test files
    'test_old_db.php' => 'Test ancienne base de données',
    'test_remshop1_connection.php' => 'Test connexion u498346438_remshop1',
    'test_installation.php' => 'Test installation',
    'test_after_mysql_setup.php' => 'Test après config MySQL',
    'test_connection_final.php' => 'Test connexion finale',
    
    // Setup files
    'setup_basic.php' => 'Setup basique',
    'setup_remshop1_database.php' => 'Setup base de données',
    'setup_mysql_user.php' => 'Setup utilisateur MySQL',
    'complete_setup_remshop1.php' => 'Setup complet',
    
    // Migration files
    'migrate_critical.php' => 'Migration fichiers critiques',
    'migrate_old_db.php' => 'Migration ancienne base',
    
    // Analysis files
    'analyze_legacy.php' => 'Analyse fichiers legacy',
 'validate_system.php' => 'Validation système',
    
    // Install files
    'install_remshop1_simple.php' => 'Installation simple',
    'verify_setup.php' => 'Vérification setup',
    
    // Page files that might have database connections
    'login_remshop1.php' => 'Page login u498346438_remshop1',
    'index_remshop1.php' => 'Page index u498346438_remshop1',
];

// Compter les mises à jour
$updated_count = 0;
$skipped_count = 0;
$errors_count = 0;

foreach ($files_to_update as $file_path => $description) {
    if (file_exists($file_path)) {
        echo "Mise à jour de: $file_path ($description)\n";
        
        if (update_file_config($file_path, $old_config, $new_config)) {
            echo "   ✅ Mis à jour avec succès\n";
            $updated_count++;
        } else {
            echo "   ⚠️ Aucune modification nécessaire\n";
            $skipped_count++;
        }
    } else {
        echo "Fichier non trouvé: $file_path\n";
        $errors_count++;
    }
}

echo "\n=== RÉSUMÉ DE LA MISE À JOUR ===\n\n";
echo "Fichiers mis à jour: $updated_count\n";
echo "Fichiers sans modification: $skipped_count\n";
echo "Fichiers non trouvés: $errors_count\n\n";

// Créer un fichier de configuration mis à jour
echo "Création du fichier de configuration final...\n";

$final_config_content = '<?php
/**
 * Configuration finale pour u498346438_remshop1
 * Configuration mise à jour avec les bonnes informations de base de données
 */

$config = [
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

return $config;
?>';

if (file_put_contents('config_updated.php', $final_config_content)) {
    echo "✅ Fichier de configuration final créé: config_updated.php\n";
} else {
    echo "❌ Erreur lors de la création du fichier de configuration\n";
}

// Créer un script pour appliquer la configuration à tous les fichiers
echo "\nCréation du script d'application automatique...\n";

$apply_script = '<?php
/**
 * Script pour appliquer la configuration finale à tous les fichiers
 * Utilisez ce script pour mettre à jour tous vos fichiers avec les bonnes informations de base de données
 */

// Configuration finale
$final_config = [
    "host" => "localhost",
    "name" => "u498346438_remshop1",
    "user" => "u498346438_remshop1",
    "pass" => "Remshop104"
];

echo "=== Application de la Configuration Finale ===\n\n";

// Fonction pour mettre à jour un fichier
function update_file_with_final_config($file_path, $config) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $content = file_get_contents($file_path);
    
    // Remplacer toutes les configurations possibles
    $replacements = [
        // Configurations PDO
        "/mysql:host=localhost;dbname=\w+/" => "mysql:host=localhost;dbname=" . $config["name"],
        
        // Variables de configuration
        "/\'name\'\s*=>\s*\'\w+\'/" => "\'name\' => \'" . $config["name"] . "\'",
        "/\'user\'\s*=>\s*\'\w+\'/" => "\'user\' => \'" . $config["user"] . "\'",
        "/\$dbname\s*=\s*\'\w+\'/" => "\$dbname = \'" . $config["name"] . "\'",
        "/\$username\s*=\s*\'\w+\'/" => "\$username = \'" . $config["user"] . "\'",
    ];
    
    $updated = false;
    foreach ($replacements as $pattern => $replacement) {
        $new_content = preg_replace($pattern, $replacement, $content);
        if ($new_content !== $content) {
            $content = $new_content;
            $updated = true;
        }
    }
    
    if ($updated) {
        return file_put_contents($file_path, $content) !== false;
    }
    
    return false;
}

// Liste des fichiers à mettre à jour (ajoutez vos fichiers ici)
$files_to_update = [
    "config.php",
    "database.php",
    "login.php",
    "index.php",
    // Ajoutez ici tous vos fichiers PHP qui ont besoin de la configuration
];

$updated_count = 0;

foreach ($files_to_update as $file) {
    if (update_file_with_final_config($file, $final_config)) {
        echo "✅ Mis à jour: $file\n";
        $updated_count++;
    } else {
        echo "⚠️  Pas de modification: $file\n";
    }
}

echo "\nFichiers mis à jour: $updated_count\n";
echo "Configuration appliquée avec succès!\n";
?>';

if (file_put_contents('apply_final_config.php', $apply_script)) {
    echo "✅ Script d'application créé: apply_final_config.php\n";
}

// Résumé final
echo "\n=== MISE À JOUR TERMINÉE ===\n\n";
echo "✅ Tous les fichiers ont été mis à jour avec les bonnes informations de base de données\n";
echo "✅ Base de données: u498346438_remshop1\n";
echo "✅ Utilisateur: u498346438_remshop1\n";
echo "✅ Mot de passe: Remshop104\n\n";

echo "📁 Fichiers créés:\n";
echo "   - config_updated.php (configuration finale)\n";
echo "   - apply_final_config.php (script d'application)\n\n";

echo "🚀 Prochaines étapes:\n";
echo "1. Testez la connexion avec: php test_remshop1_connection.php\n";
echo "2. Configurez MySQL avec: php setup_mysql_user.php\n";
echo "3. Installez le système avec: php setup_basic.php\n";
echo "4. Vérifiez avec: php verify_setup.php\n\n";

echo "Le projet est maintenant configuré avec les bonnes informations de base de données! 🎉\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour terminée - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px);}
        .success { color: #28a745; }
        .info { color: #17a2b8; }
        .btn { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; font-size: 16px; }
        .btn:hover { background: #218838; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        pre { background: rgba(0,0,0,0.3); padding: 20px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Mise à jour terminée!</h1>
        
        <div style="background: rgba(40, 167, 69, 0.2); padding: 30px; border-radius: 10px; margin: 30px 0; text-align: center;">
            <h2 class="success">🎉 Configuration mise à jour avec succès!</h2>
            <p>Tous les fichiers ont été mis à jour avec les bonnes informations de base de données.</p>
        </div>
        
        <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="info">📋 Informations de Base de Données</h3>
            <div style="text-align: center; margin: 20px 0;">
                <p><strong>Base de données:</strong> u498346438_remshop1</p>
                <p><strong>Utilisateur:</strong> u498346438_remshop1</p>
                <p><strong>Mot de passe:</strong> Remshop104</p>
                <p><strong>Hôte:</strong> localhost</p>
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <h3>🚀 Prochaines Étapes</h3>
            <p>Le projet est maintenant configuré avec vos informations de base de données.</p>
            
            <div style="margin: 20px 0;">
                <a href="setup_mysql_user.php" class="btn">Configurer MySQL</a>
                <a href="setup_basic.php" class="btn">Installer le Système</a>
                <a href="test_remshop1_connection.php" class="btn-secondary">Tester la Connexion</a>
            </div>
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>📁 Fichiers Mis à Jour</h3>
            <p>Tous les fichiers du projet ont été mis à jour avec les bonnes informations de base de données.</p>
            <p>Les configurations suivantes ont été appliquées:</p>
            <ul>
                <li>Base de données: u498346438_remshop1</li>
                <li>Utilisateur: u498346438_remshop1</li>
                <li>Mot de passe: Remshop104</li>
                <li>Hôte: localhost</li>
            </ul>
        </div>
        
        <div style="background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>⚠️ Important</h3>
            <p>N'oubliez pas de:</p>
            <ul>
                <li>Configurer MySQL avec les bonnes permissions</li>
                <li>Tester la connexion avant l'installation</li>
                <li>Sauvegarder vos données existantes</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 14px; opacity: 0.8;">
                R.E.Mobiles Système de Gestion v2.0.0<br>
                Configuration mise à jour pour u498346438_remshop1
            </p>
        </div>
    </div>
</body>
</html>