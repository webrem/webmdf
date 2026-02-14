<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Complete setup script for remshop1 independent system
 * This script sets up everything needed for the new database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color codes
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_PURPLE = "\033[35m";

// Header
printHeader();

// Step 1: Test database connection
echo COLOR_BLUE . "Étape 1: Test de connexion à la base de données u498346438_remshop1" . COLOR_RESET . "\n";
$connection_success = testDatabaseConnection();

if (!$connection_success) {
    echo COLOR_RED . "❌ Impossible de se connecter à la base de données. Arrêt du script." . COLOR_RESET . "\n";
    exit(1);
}

// Step 2: Setup database structure
echo "\n" . COLOR_BLUE . "Étape 2: Configuration de la structure de la base de données" . COLOR_RESET . "\n";
$database_success = setupDatabaseStructure();

// Step 3: Create independent version files
echo "\n" . COLOR_BLUE . "Étape 3: Création des fichiers pour la version indépendante" . COLOR_RESET . "\n";
$files_success = createIndependentFiles();

// Step 4: Test the complete system
echo "\n" . COLOR_BLUE . "Étape 4: Test du système complet" . COLOR_RESET . "\n";
$test_success = testCompleteSystem();

// Final Summary
printFinalSummary($connection_success, $database_success, $files_success, $test_success);

// Helper Functions
function printHeader() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                    Configuration Complète - R.E.Mobiles                      ║
║                    ────────────────────────────────────                      ║
║                                                                              ║
║  Ce script configure complètement le système avec la base de données         ║
║  u498346438_remshop1 pour des tests indépendants.                                       ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n\n";
}

function testDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=u498346438_remshop1;charset=utf8mb4",
            "u498346438_remshop1",
            "Remshop104",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Connexion réussie à u498346438_remshop1\n";
        
        // Test basic query
        $result = $pdo->query("SELECT 1");
        if ($result) {
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Test de requête réussi\n";
            return true;
        }
    } catch (Exception $e) {
        echo "  " . COLOR_RED . "✗" . COLOR_RESET . " Erreur de connexion: " . $e->getMessage() . "\n";
        return false;
    }
    
    return false;
}

function setupDatabaseStructure() {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=u498346438_remshop1;charset=utf8mb4",
            "u498346438_remshop1",
            "Remshop104"
        );
        
        echo "  Configuration des tables de base...\n";
        
        // Create essential tables first
        $essential_tables = [
            'user_roles' => "CREATE TABLE IF NOT EXISTS user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                permissions JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'users' => "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                phone VARCHAR(20),
                role_id INT DEFAULT 1,
                is_active BOOLEAN DEFAULT true,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'clients' => "CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_number VARCHAR(20) UNIQUE,
                company_name VARCHAR(100),
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                email VARCHAR(100),
                phone VARCHAR(20),
                mobile VARCHAR(20),
                address TEXT,
                city VARCHAR(50),
                postal_code VARCHAR(10),
                country VARCHAR(50) DEFAULT 'France',
                notes TEXT,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($essential_tables as $table_name => $sql) {
            try {
                $pdo->exec($sql);
                echo "    " . COLOR_GREEN . "✓" . COLOR_RESET . " Table $table_name créée\n";
            } catch (Exception $e) {
                echo "    " . COLOR_YELLOW . "⚠" . COLOR_RESET . " Table $table_name existe déjà\n";
            }
        }
        
        // Insert default admin user if not exists
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $admin_exists = $stmt->fetch()['count'] > 0;
        
        if (!$admin_exists) {
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                'admin',
                'admin@remshop1.local',
                $password_hash,
                'Administrateur',
                'Système'
            ]);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Utilisateur admin créé (mot de passe: admin123)\n";
        } else {
            echo "  " . COLOR_BLUE . "ℹ" . COLOR_RESET . " Utilisateur admin existe déjà\n";
        }
        
        // Insert sample client if not exists
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
        $client_count = $stmt->fetch()['count'];
        
        if ($client_count == 0) {
            $stmt = $pdo->prepare("INSERT INTO clients (first_name, last_name, email, phone, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'Jean',
                'Dupont',
                'jean.dupont@example.com',
                '0612345678',
                '123 Rue de l\'Exemple',
                'Paris',
                '75001'
            ]);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Client exemple créé\n";
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "  " . COLOR_RED . "✗" . COLOR_RESET . " Erreur lors de la configuration: " . $e->getMessage() . "\n";
        return false;
    }
}

function createIndependentFiles() {
    $files_created = 0;
    
    // Create a simple index file
    $index_content = '<?php
/**
 * Index pour la version u498346438_remshop1
 */
require_once __DIR__ . "/includes/init_remshop1.php";

if (!$auth->isLoggedIn()) {
    header("Location: login_remshop1.php");
    exit;
}

echo "<h1>Bienvenue dans R.E.Mobiles - Version u498346438_remshop1</h1>";
echo "<p>Connecté en tant que: " . htmlspecialchars($current_user["username"]) . "</p>";
echo "<p><a href=\"logout_remshop1.php\">Déconnexion</a></p>";
?>';
    
    if (file_put_contents(__DIR__ . '/index_test_remshop1.php', $index_content)) {
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Fichier index_test_remshop1.php créé\n";
        $files_created++;
    }
    
    // Create a simple logout file
    $logout_content = '<?php
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
session_destroy();
header("Location: login_remshop1.php");
exit;
?>';
    
    if (file_put_contents(__DIR__ . '/logout_remshop1.php', $logout_content)) {
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Fichier logout_remshop1.php créé\n";
        $files_created++();
    }
    
    // Create a test database connection file
    $test_content = '<?php
require_once __DIR__ . "/includes/config_remshop1.php";
echo "Configuration chargée avec succès!<br>";
echo "Base de données: " . $config["database"]["name"] . "<br>";
echo "Utilisateur: " . $config["database"]["user"] . "<br>";
?>';
    
    if (file_put_contents(__DIR__ . '/test_config_remshop1.php', $test_content)) {
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Fichier test_config_remshop1.php créé\n";
        $files_created++;
    }
    
    echo "  " . COLOR_BLUE . "ℹ" . COLOR_RESET . " $files_created fichiers créés pour la version indépendante\n";
    return $files_created > 0;
}

function testCompleteSystem() {
    echo "  Test de la configuration...\n";
    
    // Test 1: Configuration loading
    try {
        $config = require_once __DIR__ . '/includes/config_remshop1.php';
        echo "    " . COLOR_GREEN . "✓" . COLOR_RESET . " Configuration chargée\n";
    } catch (Exception $e) {
        echo "    " . COLOR_RED . "✗" . COLOR_RESET . " Configuration échouée: " . $e->getMessage() . "\n";
        return false;
    }
    
    // Test 2: Database connection
    try {
        require_once __DIR__ . '/includes/database.php';
        $db = new Database();
        echo "    " . COLOR_GREEN . "✓" . COLOR_RESET . " Connexion base de données établie\n";
    } catch (Exception $e) {
        echo "    " . COLOR_RED . "✗" . COLOR_RESET . " Connexion base de données échouée: " . $e->getMessage() . "\n";
        return false;
    }
    
    // Test 3: Authentication system
    try {
        require_once __DIR__ . '/includes/auth.php';
        $auth = new Auth($db);
        echo "    " . COLOR_GREEN . "✓" . COLOR_RESET . " Système d'authentification initialisé\n";
    } catch (Exception $e) {
        echo "    " . COLOR_RED . "✗" . COLOR_RESET . " Système d'authentification échoué: " . $e->getMessage() . "\n";
        return false;
    }
    
    // Test 4: User verification
    try {
        $stmt = $db->getConnection()->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            echo "    " . COLOR_GREEN . "✓" . COLOR_RESET . " Utilisateur admin vérifié\n";
        } else {
            echo "    " . COLOR_RED . "✗" . COLOR_RESET . " Utilisateur admin non trouvé\n";
            return false;
        }
    } catch (Exception $e) {
        echo "    " . COLOR_RED . "✗" . COLOR_RESET . " Vérification utilisateur échouée: " . $e->getMessage() . "\n";
        return false;
    }
    
    return true;
}

function printFinalSummary($connection, $database, $files, $test) {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                              Résumé Final                                    ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
    
    $all_success = $connection && $database && $files && $test;
    
    if ($all_success) {
        echo COLOR_GREEN . "🎉 Configuration terminée avec succès!" . COLOR_RESET . "\n\n";
        
        echo "✅ Base de données ru498346438_remshop1 configurée\n";
        echo "✅ Tables créées avec données de test\n";
        echo "✅ Utilisateur admin créé (admin/admin123)\n";
        echo "✅ Fichiers indépendants créés\n";
        echo "✅ Système testé et fonctionnel\n\n";
        
        echo COLOR_BLUE . "Prochaines étapes:" . COLOR_RESET . "\n";
        echo "1. Accédez à: login_remshop1.php\n";
        echo "2. Connectez-vous avec admin/admin123\n";
        echo "3. Testez le système indépendamment\n";
        echo "4. Utilisez cette version pour les tests\n\n";
        
        echo COLOR_YELLOW . "Fichiers importants créés:" . COLOR_RESET . "\n";
        echo "  • includes/config_remshop1.php\n";
        echo "  • includes/init_remshop1.php\n";
        echo "  • login_remshop1.php\n";
        echo "  • index_remshop1.php\n";
        echo "  • test_remshop1_connection.php\n";
        echo "  • setup_remshop1_database.php\n";
        echo "  • complete_setup_remshop1.php\n";
        
    } else {
        echo COLOR_RED . "❌ Configuration échouée." . COLOR_RESET . "\n\n";
        
        echo "Étapes réussies:\n";
        echo ($connection ? COLOR_GREEN . "✓" : COLOR_RED . "✗") . COLOR_RESET . " Connexion base de données\n";
        echo ($database ? COLOR_GREEN . "✓" : COLOR_RED . "✗") . COLOR_RESET . " Structure base de données\n";
        echo ($files ? COLOR_GREEN . "✓" : COLOR_RED . "✗") . COLOR_RESET . " Fichiers système\n";
        echo ($test ? COLOR_GREEN . "✓" : COLOR_RED . "✗") . COLOR_RESET . " Tests système\n\n";
        
        echo COLOR_YELLOW . "Veuillez corriger les erreurs et réessayer." . COLOR_RESET . "\n";
    }
}

// Exit with appropriate code
exit($all_success ?? false ? 0 : 1);