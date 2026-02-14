<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script de déploiement automatique pour R.E.Mobiles
 * Ce script automatise l'installation et la configuration
 */

define('APP_START', true);

// Configuration du déploiement
$deploymentConfig = [
    'app_name' => 'R.E.Mobiles',
    'app_version' => '2.0.0',
    'required_php_version' => '7.4.0',
    'required_extensions' => ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json'],
    'writable_dirs' => ['uploads', 'cache', 'logs'],
    'env_example' => '.env.example',
    'env_target' => '.env'
];
 
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
        'info' => 'ℹ️',
        default => '•'
    };
    
    if ($isCLI) {
        echo "[$timestamp] $prefix $message\n";
    } else {
        echo "<div class='log-entry log-$type'>";
        echo "<span class='timestamp'>[$timestamp]</span> ";
        echo "<span class='prefix'>$prefix</span> ";
        echo "<span class='message'>$message</span>";
        echo "</div>";
    }
}

// Vérifier les prérequis
function checkPrerequisites($config) {
    logMessage("Vérification des prérequis système...");
    
    $errors = [];
    
    // Vérifier la version PHP
    if (version_compare(PHP_VERSION, $config['required_php_version'], '<')) {
        $errors[] = "PHP version insuffisante. Requis: {$config['required_php_version']}, Actuel: " . PHP_VERSION;
    } else {
        logMessage("✓ PHP version " . PHP_VERSION . " compatible", 'success');
    }
    
    // Vérifier les extensions
    foreach ($config['required_extensions'] as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extension PHP manquante: $ext";
        } else {
            logMessage("✓ Extension $ext chargée", 'success');
        }
    }
    
    // Vérifier les dossiers inscriptibles
    foreach ($config['writable_dirs'] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Impossible de créer le répertoire: $dir";
            } else {
                logMessage("✓ Répertoire $dir créé", 'success');
            }
        } elseif (!is_writable($dir)) {
            if (!chmod($dir, 0755)) {
                $errors[] = "Répertoire non inscriptible: $dir";
            } else {
                logMessage("✓ Permissions répertoire $dir corrigées", 'success');
            }
        } else {
            logMessage("✓ Répertoire $dir accessible", 'success');
        }
    }
    
    return $errors;
}

// Copier le fichier d'environnement
function setupEnvironment($config) {
    logMessage("Configuration de l'environnement...");
    
    if (!file_exists($config['env_example'])) {
        logMessage("Fichier d'exemple d'environnement manquant", 'error');
        return false;
    }
    
    if (!file_exists($config['env_target'])) {
        if (!copy($config['env_example'], $config['env_target'])) {
            logMessage("Impossible de copier le fichier .env", 'error');
            return false;
        }
        logMessage("✓ Fichier .env créé à partir de l'exemple", 'success');
    } else {
        logMessage("✓ Fichier .env existe déjà", 'info');
    }
    
    return true;
}

// Installer les dépendances Composer
function installDependencies() {
    logMessage("Installation des dépendances...");
    
    if (file_exists('composer.json')) {
        if (file_exists('vendor/autoload.php')) {
            logMessage("✓ Dépendances déjà installées", 'success');
            return true;
        }
        
        $output = [];
        $returnCode = 0;
        exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            logMessage("✓ Dépendances installées avec succès", 'success');
            return true;
        } else {
            logMessage("Erreur lors de l'installation des dépendances", 'error');
            logMessage("Détails: " . implode("\n", $output), 'error');
            return false;
        }
    } else {
        logMessage("✓ Aucune dépendance Composer requise", 'info');
        return true;
    }
}

// Configurer la base de données
function setupDatabase() {
    logMessage("Configuration de la base de données...");
    
    // Vérifier si la base de données est déjà configurée
    try {
        require_once 'database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Vérifier si les tables existent
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            logMessage("✓ Base de données déjà configurée", 'success');
            return true;
        }
        
        logMessage("Base de données existante mais tables manquantes", 'warning');
        return false;
        
    } catch (Exception $e) {
        logMessage("Configuration DB requise: " . $e->getMessage(), 'warning');
        return false;
    }
}

// Créer un fichier .htaccess pour la sécurité
function createHtaccess() {
    logMessage("Configuration de la sécurité Apache...");
    
    $htaccessContent = "# Sécurité R.E.Mobiles\n";
    $htaccessContent .= "<IfModule mod_rewrite.c>\n";
    $htaccessContent .= "RewriteEngine On\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccessContent .= "RewriteRule ^(.*)$ index.php [QSA,L]\n";
    $htaccessContent .= "</IfModule>\n\n";
    
    $htaccessContent .= "# Protection contre l'accès aux fichiers sensibles\n";
    $htaccessContent .= "<Files \".env\">\n";
    $htaccessContent .= "Order allow,deny\n";
    $htaccessContent .= "Deny from all\n";
    $htaccessContent .= "</Files>\n\n";
    
    $htaccessContent .= "<Files \"test.log\">\n";
    $htaccessContent .= "Order allow,deny\n";
    $htaccessContent .= "Deny from all\n";
    $htaccessContent .= "</Files>\n\n";
    
    $htaccessContent .= "# Headers de sécurité\n";
    $htaccessContent .= "Header set X-Content-Type-Options nosniff\n";
    $htaccessContent .= "Header set X-Frame-Options DENY\n";
    $htaccessContent .= "Header set X-XSS-Protection \"1; mode=block\"\n";
    
    if (file_put_contents('.htaccess', $htaccessContent)) {
        logMessage("✓ Fichier .htaccess créé", 'success');
        return true;
    } else {
        logMessage("Impossible de créer le fichier .htaccess", 'error');
        return false;
    }
}

// Fonction principale de déploiement
function deploy() {
    global $deploymentConfig;
    
    logMessage("🚀 Démarrage du déploiement de {$deploymentConfig['app_name']} v{$deploymentConfig['app_version']}");
    
    // Étape 1: Vérifier les prérequis
    $prerequisiteErrors = checkPrerequisites($deploymentConfig);
    if (!empty($prerequisiteErrors)) {
        logMessage("❌ Prérequis non satisfaits:", 'error');
        foreach ($prerequisiteErrors as $error) {
            logMessage("  - $error", 'error');
        }
        return false;
    }
    
    // Étape 2: Configuration de l'environnement
    if (!setupEnvironment($deploymentConfig)) {
        return false;
    }
    
    // Étape 3: Installation des dépendances
    if (!installDependencies()) {
        return false;
    }
    
    // Étape 4: Configuration de la base de données
    $dbConfigured = setupDatabase();
    
    // Étape 5: Configuration de la sécurité
    if (!createHtaccess()) {
        return false;
    }
    
    // Étape 6: Tests
    logMessage("Exécution des tests de vérification...");
    if (file_exists('test.php')) {
        ob_start();
        include 'test.php';
        $testOutput = ob_get_clean();
        logMessage("✓ Tests exécutés", 'success');
    }
    
    // Résumé
    logMessage("🎉 Déploiement terminé!", 'success');
    
    if (!$dbConfigured) {
        logMessage("⚠️ Base de données non configurée. Visitez /install.php pour terminer l'installation", 'warning');
    }
    
    logMessage("📋 Prochaines étapes:");
    logMessage("  1. Configurez votre fichier .env");
    logMessage("  2. Visitez /install.php pour créer les tables");
    logMessage("  3. Connectez-vous avec admin/remadmin123");
    logMessage("  4. Changez les identifiants par défaut");
    
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
        <title>Déploiement - R.E.Mobiles</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <style>
            * {
                font-family: 'Inter', sans-serif;
            }
            
            body {
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #000000 100%);
                min-height: 100vh;
            }
            
            .deploy-card {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(13, 202, 240, 0.2);
                border-radius: 20px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            
            .log-entry {
                padding: 0.5rem;
                margin: 0.25rem 0;
                border-radius: 0.5rem;
                font-family: 'Courier New', monospace;
                font-size: 0.875rem;
            }
            
            .log-success {
                background: rgba(25, 135, 84, 0.2);
                border-left: 4px solid #198754;
                color: #d1e7dd;
            }
            
            .log-error {
                background: rgba(220, 53, 69, 0.2);
                border-left: 4px solid #dc3545;
                color: #f8d7da;
            }
            
            .log-warning {
                background: rgba(255, 193, 7, 0.2);
                border-left: 4px solid #ffc107;
                color: #fff3cd;
            }
            
            .log-info {
                background: rgba(13, 202, 240, 0.2);
                border-left: 4px solid #0dcaf0;
                color: #cff4fc;
            }
            
            .timestamp {
                color: #6c757d;
                font-size: 0.75rem;
            }
            
            .prefix {
                font-weight: bold;
                margin-right: 0.5rem;
            }
        </style>
    </head>
    <body class="flex items-center justify-center p-8">
        <div class="deploy-card p-8 w-full max-w-4xl">
            <div class="text-center mb-8">
                <i class="bi bi-rocket-takeoff-fill text-6xl text-cyan-400 mb-4"></i>
                <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 mb-2">
                    Déploiement R.E.Mobiles
                </h1>
                <p class="text-gray-300">Installation automatique du système</p>
            </div>
            
            <div class="mb-8">
                <button onclick="startDeployment()" id="deployBtn" class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:from-cyan-400 hover:to-blue-500 transition-all">
                    <i class="bi bi-play-fill mr-2"></i>
                    Lancer le déploiement
                </button>
            </div>
            
            <div id="logContainer" class="bg-gray-900 rounded-lg p-4 h-96 overflow-y-auto hidden">
                <h3 class="text-lg font-semibold text-white mb-4">Journal de déploiement</h3>
                <div id="logs"></div>
            </div>
            
            <div id="result" class="hidden mt-8 text-center">
                <!-- Résultats affichés ici -->
            </div>
        </div>
        
        <script>
            function startDeployment() {
                document.getElementById('deployBtn').disabled = true;
                document.getElementById('deployBtn').innerHTML = '<i class="bi bi-arrow-repeat mr-2 animate-spin"></i>Déploiement en cours...';
                document.getElementById('logContainer').classList.remove('hidden');
                
                fetch('deploy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({cli: false})
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('logs').innerHTML = data;
                    document.getElementById('result').classList.remove('hidden');
                })
                .catch(error => {
                    document.getElementById('logs').innerHTML = '<div class="log-entry log-error">Erreur lors du déploiement: ' + error.message + '</div>';
                });
            }
        </script>
    </body>
    </html>
    <?php
} else {
    // Mode CLI
    deploy();
}
?>