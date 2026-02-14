<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script de migration vers la nouvelle version 2.0.0
 */
 
define('APP_START', true);

// Vérifier si c'est une requête CLI
$isCLI = php_sapi_name() === 'cli';

// Fonction de log
function logMessage($message, $type = 'info') {
    global $isCLI;
    $timestamp = date('Y-m-d H:i:s');
    $prefix = match($type) {
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    };
    
    if ($isCLI) {
        echo "[$timestamp] $prefix $message\n";
    } else {
        echo "<div style='margin: 0.5rem 0; padding: 0.5rem; border-radius: 0.5rem; font-family: monospace;'>";
        echo "<span style='color: #6c757d;'>[$timestamp]</span> ";
        echo "<strong>$prefix</strong> $message";
        echo "</div>";
        flush();
    }
}

// Vérifier les prérequis
function checkPrerequisites() {
    logMessage("Vérification des prérequis...");
    
    // Vérifier PHP version
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        logMessage("PHP version insuffisante: " . PHP_VERSION, 'error');
        return false;
    }
    
    // Vérifier les extensions
    $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl'];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            logMessage("Extension manquante: $ext", 'error');
            return false;
        }
    }
    
    logMessage("✓ Prérequis vérifiés", 'success');
    return true;
}

// Sauvegarder la base de données
function backupDatabase() {
    logMessage("Sauvegarde de la base de données...");
    
    $backupDir = '/mnt/okcomputer/backup/' . date('Y-m-d_H-i-s');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . '/database_backup.sql';
    $command = "mysqldump -h localhost -u u498346438_remshop1 -pRemshop104 u498346438_remshop1 > $backupFile 2>&1";
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        logMessage("✓ Sauvegarde créée: $backupFile", 'success');
        return true;
    } else {
        logMessage("❌ Erreur lors de la sauvegarde", 'error');
        return false;
    }
}

// Fonction principale de migration
function migrate() {
    logMessage("🚀 Démarrage de la migration vers R.E.Mobiles 2.0.0");
    
    // Étape 1: Vérifier les prérequis
    if (!checkPrerequisites()) {
        return false;
    }
    
    // Étape 2: Sauvegarder la base de données
    if (!backupDatabase()) {
        return false;
    }
    
    // Étape 3: Créer le fichier .env
    logMessage("Création du fichier .env...");
    $envContent = "# Configuration R.E.Mobiles
DB_HOST=localhost
DB_USERNAME=u498346438_remshop1
DB_PASSWORD=Remshop104
DB_NAME=u498346438_remshop1
APP_ENV=production
APP_DEBUG=false
";
    
    if (file_put_contents('/mnt/okcomputer/output/.env', $envContent)) {
        logMessage("✓ Fichier .env créé", 'success');
    }
    
    // Étape 4: Configurer les répertoires
    logMessage("Configuration des répertoires...");
    $directories = [
        '/mnt/okcomputer/output/uploads',
        '/mnt/okcomputer/output/cache',
        '/mnt/okcomputer/output/logs',
        '/mnt/okcomputer/output/backups'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            logMessage("✓ Répertoire créé: $dir", 'success');
        }
    }
    
    logMessage("🎉 Migration terminée!", 'success');
    logMessage("Prochaines étapes:", 'info');
    logMessage("1. Exécutez install.php pour créer les tables", 'info');
    logMessage("2. Testez l'application avec test.php", 'info');
    logMessage("3. Configurez votre serveur web", 'info');
    
    return true;
}

// Interface web
if (!$isCLI) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Migration - R.E.Mobiles</title>
        <style>
            body {
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #000000 100%);
                color: white;
                font-family: 'Inter', sans-serif;
                margin: 0;
                padding: 2rem;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
            }
            .log-entry {
                margin: 0.5rem 0;
                padding: 0.5rem;
                border-radius: 0.5rem;
                font-family: monospace;
                font-size: 0.9rem;
            }
            .log-success { background: rgba(25, 135, 84, 0.2); border-left: 4px solid #198754; }
            .log-error { background: rgba(220, 53, 69, 0.2); border-left: 4px solid #dc3545; }
            .log-warning { background: rgba(255, 193, 7, 0.2); border-left: 4px solid #ffc107; }
            .log-info { background: rgba(13, 202, 240, 0.2); border-left: 4px solid #0dcaf0; }
            .timestamp { color: #6c757d; font-size: 0.8rem; }
            .prefix { font-weight: bold; margin-right: 0.5rem; }
            button {
                background: linear-gradient(135deg, #0dcaf0 0%, #0b5ed7 100%);
                border: none;
                border-radius: 12px;
                color: white;
                font-weight: 600;
                padding: 16px 32px;
                cursor: pointer;
                font-size: 1rem;
            }
            button:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem; font-size: 2rem; background: linear-gradient(135deg, #0dcaf0, #0b5ed7); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                🚀 Migration R.E.Mobiles 2.0.0
            </h1>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <button onclick="startMigration()">Lancer la migration</button>
            </div>
            
            <div id="logContainer" style="background: rgba(255,255,255,0.05); border-radius: 16px; padding: 1rem; backdrop-filter: blur(20px);">
                <h3>Journal de migration</h3>
                <div id="logs"></div>
            </div>
        </div>
        
        <script>
            function startMigration() {
                document.querySelector('button').disabled = true;
                document.querySelector('button').textContent = 'Migration en cours...';
                
                fetch('migrate.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'}
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('logs').innerHTML = data;
                });
            }
        </script>
    </body>
    </html>
    <?php
} else {
    // Mode CLI
    migrate();
}
?>