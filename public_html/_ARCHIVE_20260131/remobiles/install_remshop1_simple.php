<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Installation simple pour u498346438_remshop1
 * Script d'installation sans erreurs de syntaxe
 */

// Configuration de la base de données
$db_config = [
    'host' => 'localhost',
    'name' => 'u498346438_remshop1',
    'user' => 'u498346438_remshop1',
    'pass' => 'Remshop104'
];

// Désactiver l'affichage des erreurs pour l'installation
ini_set('display_errors', 0);

// Fonction pour afficher les messages
function install_message($message, $type = 'info') {
    $color = [
        'success' => '#28a745',
        'error' => '#dc3545', 
        'info' => '#007bff',
        'warning' => '#ffc107'
    ][$type] ?? '#007bff';
    
    echo '<div style="margin: 10px 0; padding: 10px; border-left: 4px solid ' . $color . '; background-color: #f8f9fa;">';
    echo htmlspecialchars($message);
    echo '</div>';
}

// Fonction principale d'installation
function install_system() {
    global $db_config;
    
    try {
        // Étape 1: Connexion à la base de données
        install_message('Connexion à la base de données...', 'info');
        
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
            $db_config['user'],
            $db_config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        install_message('Connecté avec succès!', 'success');
        
        // Étape 2: Créer les tables essentielles
        install_message('Création des tables de base...', 'info');
        
        $tables_created = 0;
        
        // Table users
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                phone VARCHAR(20),
                role VARCHAR(20) DEFAULT 'user',
                is_active BOOLEAN DEFAULT true,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            install_message('Table users créée avec succès', 'success');
            $tables_created++;
        } catch (Exception $e) {
            install_message('Table users existe déjà ou erreur: ' . $e->getMessage(), 'warning');
        }
        
        // Table clients
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                email VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                city VARCHAR(50),
                postal_code VARCHAR(10),
                country VARCHAR(50) DEFAULT 'France',
                notes TEXT,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            install_message('Table clients créée avec succès', 'success');
            $tables_created++;
        } catch (Exception $e) {
            install_message('Table clients existe déjà ou erreur: ' . $e->getMessage(), 'warning');
        }
        
        // Étape 3: Insérer les données par défaut
        install_message('Insertion des données par défaut...', 'info');
        
        // Vérifier si l'utilisateur admin existe
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $admin_exists = $stmt->fetch()['count'] > 0;
        
        if (!$admin_exists) {
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'admin',
                'admin@remshop1.local',
                $password_hash,
                'Administrateur',
                'Système',
                'admin'
            ]);
            
            install_message('Utilisateur admin créé avec succès!', 'success');
            install_message('Login: admin / Mot de passe: admin123', 'info');
        } else {
            install_message('Utilisateur admin existe déjà', 'info');
        }
        
        // Insérer un client exemple
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
            
            install_message('Client exemple créé avec succès!', 'success');
        } else {
            install_message('Clients existent déjà', 'info');
        }
        
        // Étape 4: Créer les fichiers de configuration
        install_message('Création des fichiers de configuration...', 'info');
        
        $config_content = '<?php
// Configuration pour u498346438_remshop1
return [
    "database" => [
        "host" => "localhost",
        "name" => "u498346438_remshop1",
        "user" => "u498346438_remshop1",
        "pass" => "Remshop104",
        "charset" => "utf8mb4"
    ],
    "app" => [
        "name" => "R.E.Mobiles - Version u498346438_remshop1",
        "version" => "2.0.0",
        "environment" => "development"
    ]
];
?>';
        
        if (file_put_contents('config_remshop1_installed.php', $config_content)) {
            install_message('Fichier de configuration créé avec succès!', 'success');
        } else {
            install_message('Erreur lors de la création du fichier de configuration', 'error');
        }
        
        // Étape 5: Test final
        install_message('Test final du système...', 'info');
        
        // Vérifier que tout fonctionne
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $total_users = $stmt->fetch()['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_clients FROM clients");
        $total_clients = $stmt->fetch()['total_clients'];
        
        install_message("Système installé avec succès!", 'success');
        install_message("Utilisateurs: $total_users | Clients: $total_clients", 'info');
        
        return true;
        
    } catch (Exception $e) {
        install_message('Erreur lors de l\'installation: ' . $e->getMessage(), 'error');
        return false;
    }
}

// Fonction pour créer la page de login simple
function create_simple_login() {
    $login_content = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 100%; max-width: 400px; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 { color: #333; margin: 0; }
        .login-header p { color: #666; margin: 10px 0 0 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .login-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .login-btn:hover { opacity: 0.9; }
        .info-box { background: #e3f2fd; border: 1px solid #2196f3; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-box h3 { margin: 0 0 10px 0; color: #1976d2; }
        .info-box p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>R.E.Mobiles</h1>
            <p>Système de Gestion - Version u498346438_remshop1</p>
        </div>
        
        <div class="info-box">
            <h3>Compte de Test</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
        </div>
        
        <form method="POST" action="login_simple.php">
            <div class="form-group">
                <label for="username">Nom d\'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">Se connecter</button>
        </form>
    </div>
</body>
</html>';
    
    if (file_put_contents('login_simple.php', $login_content)) {
        install_message('Page de login créée avec succès!', 'success');
        return true;
    } else {
        install_message('Erreur lors de la création de la page de login', 'error');
        return false;
    }
}

// Fonction pour créer la page d'accueil simple
function create_simple_index() {
    $index_content = '<?php
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique

// Vérifier si l\'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login_simple.php");
    exit;
}

// Configuration de la base de données
$db_config = [
    "host" => "localhost",
    "name" => "u498346438_remshop1",
    "user" => "u498346438_remshop1",
    "pass" => "Remshop104"
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config["host"]};dbname={$db_config["name"]};charset=utf8mb4",
        $db_config["user"],
        $db_config["pass"]
    );
    
    // Récupérer les statistiques
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $client_count = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin-bottom: 30px; backdrop-filter: blur(10px);}
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; backdrop-filter: blur(10px); text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 2em; }
        .logout-btn { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue dans R.E.Mobiles - Version u498346438_remshop1</h1>
            <p>Connecté en tant que: <?php echo htmlspecialchars($_SESSION["username"] ?? "Utilisateur"); ?></p>
            <a href="logout_simple.php" class="logout-btn">Déconnexion</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div style="background: #dc3545; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $user_count ?? 0; ?></h3>
                <p>Utilisateurs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $client_count ?? 0; ?></h3>
                <p>Clients</p>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; backdrop-filter: blur(10px);">
            <h2>Système opérationnel!</h2>
            <p>La base de données u498346438_remshop1 est configurée et fonctionnelle.</p>
            <p>Vous pouvez maintenant commencer à utiliser le système.</p>
            <p><strong>Base de données:</strong> u498346438_remshop1</p>
            <p><strong>Version:</strong> 2.0.0</p>
        </div>
    </div>
</body>
</html>';
    
    if (file_put_contents('index_simple.php', $index_content)) {
        install_message('Page d\'accueil créée avec succès!', 'success');
        return true;
    } else {
        install_message('Erreur lors de la création de la page d\'accueil', 'error');
        return false;
    }
}

// Fonction pour créer la déconnexion
function create_logout() {
    $logout_content = '<?php
session_start();
session_destroy();
header("Location: login_simple.php");
exit;
?>';
    
    if (file_put_contents('logout_simple.php', $logout_content)) {
        install_message('Système de déconnexion créé!', 'success');
        return true;
    } else {
        install_message('Erreur lors de la création de la déconnexion', 'error');
        return false;
    }
}

// Fonction pour gérer la connexion
function handle_login() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            return 'Veuillez remplir tous les champs.';
        }
        
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=u498346438_remshop1;charset=utf8mb4",
                "u498346438_remshop1",
                "Remshop104"
            );
            
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index_simple.php');
                exit;
            } else {
                return 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            return 'Erreur lors de la connexion: ' . $e->getMessage();
        }
    }
    return '';
}

// Traiter la requête
$error = '';
if (basename($_SERVER['PHP_SELF']) === 'login_simple.php') {
    $error = handle_login();
}

// Afficher la page appropriée
if (basename($_SERVER['PHP_SELF']) === 'install_remshop1_simple.php') {
    // Page d'installation
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation - R.E.Mobiles</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px);}
            .step { background: rgba(255,255,255,0.1); padding: 20px; margin: 20px 0; border-radius: 5px; }
            .success { color: #28a745; }
            .error { color: #dc3545; }
            .info { color: #17a2b8; }
            .btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
            .btn:hover { background: #218838; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Installation du Système R.E.Mobiles</h1>
            <p>Base de données: u498346438_remshop1</p>
            
            <?php
            if (isset($_GET['install']) && $_GET['install'] === 'true') {
                echo '<div class="step">';
                echo '<h2>Installation en cours...</h2>';
                
                // Exécuter l'installation
                $success = install_system();
                
                if ($success) {
                    echo '<p class="success">✅ Installation terminée avec succès!</p>';
                    
                    // Créer les fichiers supplémentaires
                    echo '<p>Création des fichiers supplémentaires...</p>';
                    create_simple_login();
                    create_simple_index();
                    create_logout();
                    
                    echo '<p class="success">✅ Tous les fichiers ont été créés!</p>';
                    echo '<p><strong>Prochaines étapes:</strong></p>';
                    echo '<ul>';
                    echo '<li>Accédez à: <a href="login_simple.php" class="btn">login_simple.php</a></li>';
                    echo '<li>Connectez-vous avec: admin / admin123</li>';
                    echo '<li>Testez le système!</li>';
                    echo '</ul>';
                } else {
                    echo '<p class="error">❌ Erreur lors de l\'installation.</p>';
                }
                echo '</div>';
            } else {
                echo '<div class="step">';
                echo '<h2>Prêt pour l\'installation</h2>';
                echo '<p>Ce script va:</p>';
                echo '<ul>';
                echo '<li>Créer les tables nécessaires dans la base de données u498346438_remshop1</li>';
                echo '<li>Insérer un utilisateur admin (admin/admin123)</li>';
                echo '<li>Créer un client exemple</li>';
                echo '<li>Générer les fichiers de login et d\'accueil</li>';
                echo '</ul>';
                echo '<p><a href="?install=true" class="btn">Démarrer l\'installation</a></p>';
                echo '</div>';
            }
            ?>
        </div>
    </body>
    </html>
    <?php
}

// Si ce fichier est inclus, retourner les fonctions
if (basename($_SERVER['PHP_SELF']) !== 'install_remshop1_simple.php') {
    return [
        'install_message' => 'install_message',
        'install_system' => 'install_system',
        'create_simple_login' => 'create_simple_login',
        'create_simple_index' => 'create_simple_index',
        'create_logout' => 'create_logout',
        'handle_login' => 'handle_login'
    ];
}
?>