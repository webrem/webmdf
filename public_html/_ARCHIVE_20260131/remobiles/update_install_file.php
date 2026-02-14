<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script pour mettre à jour spécifiquement le fichier install.php
 * Remplace u498346438_remshop1 par u498346438_remshop1 dans install.php
 */

$file_path = __DIR__ . '/install.php';

echo "=== Mise à jour du fichier install.php ===\n\n";

if (!file_exists($file_path)) {
    echo "❌ Fichier non trouvé: $file_path\n";
    echo "Assurez-vous que le fichier install.php existe dans le répertoire courant.\n";
    exit(1);
}

// Lire le contenu du fichier
$content = file_get_contents($file_path);

// Afficher le contenu actuel pour vérification
echo "Contenu actuel de install.php:\n";
echo str_repeat("-", 50) . "\n";
echo $content;
echo "\n" . str_repeat("-", 50) . "\n\n";

// Configuration à remplacer
$old_config = "define('APP_START', true);

// Configuration de la base de données
\$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];";

$new_config = "define('APP_START', true);

// Configuration de la base de données
\$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];";

// Remplacer la configuration
echo "Remplacement de la configuration...\n";

$new_content = str_replace($old_config, $new_config, $content);

if ($new_content !== $content) {
    // Sauvegarder l'ancien fichier
    $backup_path = $file_path . '.backup.' . date('Y-m-d-H-i-s');
    if (file_put_contents($backup_path, $content)) {
        echo "✅ Sauvegarde créée: $backup_path\n";
    }
    
    // Écrire le nouveau contenu
    if (file_put_contents($file_path, $new_content)) {
        echo "✅ Fichier install.php mis à jour avec succès!\n";
        echo "✅ Configuration remplacée: u498346438_remshop1 → u498346438_remshop1\n";
        echo "✅ Mot de passe remplacé: Remshop104 → Remshop104\n";
        
        echo "\n=== NOUVEAU CONTENU ===\n";
        echo str_repeat("-", 50) . "\n";
        echo $new_config;
        echo "\n" . str_repeat("-", 50) . "\n";
        
    } else {
        echo "❌ Erreur lors de l'écriture du fichier\n";
    }
} else {
    echo "⚠️ Aucune modification nécessaire\n";
    echo "La configuration dans install.php ne correspond pas au pattern attendu.\n";
    
    // Recherche alternative
    if (strpos($content, 'u498346438_remshop1') !== false) {
        echo "✅ Occurrences de 'u498346438_remshop1' trouvées\n";
        $new_content = str_replace('u498346438_remshop1', 'u498346438_remshop1', $content);
        $new_content = str_replace('Remshop104', 'Remshop104', $new_content);
        
        if (file_put_contents($file_path, $new_content)) {
            echo "✅ Fichier mis à jour avec le remplacement simple\n";
        } else {
            echo "❌ Erreur lors de l'écriture du fichier\n";
        }
    } else {
        echo "❌ Aucune occurrence de 'u498346438_remshop1' trouvée\n";
    }
}

// Créer un script de test pour vérifier le fichier mis à jour
echo "\nCréation du script de test...\n";

$test_script = '<?php
/**
 * Test pour vérifier que install.php a été mis à jour correctement
 */

// Vérifiez que le fichier install.php contient la bonne configuration
$file_path = __DIR__ . "/install.php";

echo "=== Test de install.php ===\n\n";

if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    
    echo "Contenu de install.php:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Rechercher la configuration
    if (strpos($content, "u498346438_remshop1") !== false) {
        echo "✅ Configuration u498346438_remshop1 trouvée\n";
    } else {
        echo "❌ Configuration u498346438_remshop1 non trouvée\n";
    }
    
    if (strpos($content, "Remshop104") !== false) {
        echo "✅ Mot de passe Remshop104 trouvé\n";
    } else {
        echo "❌ Mot de passe Remshop104 non trouvé\n";
    }
    
    if (strpos($content, "u498346438_remshop1") !== false) {
        echo "❌ Ancienne configuration u498346438_remshop1 toujours présente\n";
    } else {
        echo "✅ Ancienne configuration u498346438_remshop1 supprimée\n";
    }
    
} else {
    echo "❌ Fichier install.php non trouvé\n";
}
?>';

if (file_put_contents('test_install_update.php', $test_script)) {
    echo "✅ Script de test créé: test_install_update.php\n";
}

echo "\n=== FIN DE LA MISE À JOUR ===\n\n";
echo "Le fichier install.php a été mis à jour avec les bonnes informations de base de données!\n";
echo "Base de données: u498346438_remshop1\n";
echo "Utilisateur: u498346438_remshop1\n";
echo "Mot de passe: Remshop104\n";
echo "Hôte: localhost\n\n";

echo "Prochaines étapes:\n";
echo "1. Exécutez: php test_install_update.php pour vérifier\n";
echo "2. Configurez MySQL si nécessaire\n";
echo "3. Installez le système\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour install.php - R.E.Mobiles</title>
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
        .code { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Mise à jour de install.php</h1>
        <p style="text-align: center;">Remplacement de u498346438_remshop1 par u498346438_remshop1</p>
        
        <div style="background: rgba(40, 167, 69, 0.2); padding: 30px; border-radius: 10px; margin: 30px 0; text-align: center;">
            <h2 class="success">✅ Mise à jour terminée!</h2>
            <p>Le fichier install.php a été mis à jour avec les bonnes informations de base de données.</p>
        </div>
        
        <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="info">📋 Configuration Mise à Jour</h3>
            <div style="text-align: center;">
                <p><strong>Base de données:</strong> u498346438_remshop1</p>
                <p><strong>Utilisateur:</strong> u498346438_remshop1</p>
                <p><strong>Mot de passe:</strong> Remshop104</p>
                <p><strong>Hôte:</strong> localhost</p>
            </div>
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>📋 Ancienne Configuration</h3>
            <div class="code">
define('APP_START', true);

// Configuration de la base de données
$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];
            </div>
        </div>
        
        <div style="background: rgba(40, 167, 69, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>📋 Nouvelle Configuration</h3>
            <div class="code">
define('APP_START', true);

// Configuration de la base de données
$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <h3>🚀 Prochaines Étapes</h3>
            <p>Le fichier install.php est maintenant configuré correctement.</p>
            <div style="margin: 20px 0;">
                <a href="test_install_update.php" class="btn">Tester l'Installation</a>
                <a href="setup_mysql_user.php" class="btn-secondary">Configurer MySQL</a>
                <a href="setup_basic.php" class="btn">Installer le Système</a>
            </div>
        </div>
        
        <div style="background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>⚠️ Important</h3>
            <p>Le fichier install.php a été mis à jour avec les bonnes informations de base de données.</p>
            <p>Si vous avez d'autres fichiers avec l'ancienne configuration, exécutez le script de remplacement global.</p>
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