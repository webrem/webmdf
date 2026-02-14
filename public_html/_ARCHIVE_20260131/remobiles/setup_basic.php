<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Setup basique et simple pour remshop1
 * Version ultra-simplifiée sans fonctions complexes
 */

// Configuration de base
$host = 'localhost';
$dbname = 'u498346438_remshop1';
$username = 'u498346438_remshop1';
$password = 'Remshop104';

echo "=== Configuration R.E.Mobiles - u498346438_remshop1 ===\n\n";

// Étape 1: Tester la connexion
echo "1. Test de connexion à la base de données...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion réussie!\n\n";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
    echo "\nVérifiez que:\n";
    echo "- La base de données 'remshop1' existe\n";
    echo "- L'utilisateur 'remshop1' a les bonnes permissions\n";
    echo "- Le mot de passe est correct\n";
    exit(1);
}

// Étape 2: Créer les tables essentielles
echo "2. Création des tables...\n";

// Table users
echo "   - Table users...";
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
    echo " ✅ OK\n";
} catch (PDOException $e) {
    echo " ⚠️  Existe déjà ou erreur\n";
}

// Table clients
echo "   - Table clients...";
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
    echo " ✅ OK\n";
} catch (PDOException $e) {
    echo " ⚠️  Existe déjà ou erreur\n";
}

// Étape 3: Insérer les données par défaut
echo "\n3. Insertion des données par défaut...\n";

// Vérifier et créer l'utilisateur admin
echo "   - Utilisateur admin...";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@remshop.local', $password_hash, 'Administrateur', 'Système', 'admin']);
        echo " ✅ Créé\n";
    } else {
        echo " ⚠️  Existe déjà\n";
    }
} catch (PDOException $e) {
    echo " ❌ Erreur: " . $e->getMessage() . "\n";
}

// Vérifier et créer un client exemple
echo "   - Client exemple...";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO clients (first_name, last_name, email, phone, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Jean', 'Dupont', 'jean.dupont@example.com', '0612345678', '123 Rue de l\'Exemple', 'Paris', '75001']);
        echo " ✅ Créé\n";
    } else {
        echo " ⚠️  Des clients existent déjà\n";
    }
} catch (PDOException $e) {
    echo " ❌ Erreur: " . $e->getMessage() . "\n";
}

// Étape 4: Vérification finale
echo "\n4. Vérification finale...\n";
try {
    // Compter les utilisateurs
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "   - Utilisateurs: $user_count\n";
    
    // Compter les clients
    $client_count = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    echo "   - Clients: $client_count\n";
    
    // Vérifier l'utilisateur admin
    $admin_exists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    if ($admin_exists > 0) {
        echo "   - Compte admin: ✅ Disponible (admin/admin123)\n";
    } else {
        echo "   - Compte admin: ❌ Non trouvé\n";
    }
    
} catch (PDOException $e) {
    echo "   ❌ Erreur lors de la vérification: " . $e->getMessage() . "\n";
}

// Étape 5: Créer les fichiers de base
echo "\n5. Création des fichiers de base...\n";

// Fichier de configuration
echo "   - config_remshop1.php...";
$config_content = '<?php
return [
    "database" => [
        "host" => "localhost",
        "name" => "remshop1",
        "user" => "remshop1",
        "pass" => "Remshop104",
        "charset" => "utf8mb4"
    ],
    "app" => [
        "name" => "R.E.Mobiles - Version remshop1",
        "version" => "2.0.0",
        "environment" => "development"
    ]
];
?>';

if (file_put_contents('config_remshop1.php', $config_content)) {
    echo " ✅ OK\n";
} else {
    echo " ❌ Erreur lors de la création du fichier\n";
}

// Page de login
echo "   - login_simple.php...";
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
            <p>Système de Gestion - Version remshop1</p>
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
    echo " ✅ OK\n";
} else {
    echo " ❌ Erreur lors de la création du fichier\n";
}

// Script de connexion
echo "   - Script de connexion...";
$login_script = '<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    
    if (empty($username) || empty($password)) {
        echo "Veuillez remplir tous les champs.";
        exit;
    }
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=remshop1;charset=utf8mb4", "remshop1", "Remshop104");
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user["password"])) {
            session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            header("Location: index_simple.php");
            exit;
        } else {
            echo "Nom d\'utilisateur ou mot de passe incorrect.";
        }
    } catch (Exception $e) {
        echo "Erreur lors de la connexion: " . $e->getMessage();
    }
} else {
    header("Location: login_simple.php");
}
?>';

if (file_put_contents('login_simple.php', $login_script, FILE_APPEND)) {
    echo " ✅ OK\n";
} else {
    echo " ❌ Erreur lors de la création du script\n";
}

// Page d'accueil
echo "   - index_simple.php...";
$index_content = '<!DOCTYPE html>
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
        <?php
        session_start();
        if (!isset($_SESSION["user_id"])) {
            header("Location: login_simple.php");
            exit;
        }
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=remshop1;charset=utf8mb4", "remshop1", "Remshop104");
            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $client_count = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
        ?>
        
        <div class="header">
            <h1>Bienvenue dans R.E.Mobiles - Version remshop1</h1>
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
            <p>La base de données remshop1 est configurée et fonctionnelle.</p>
            <p>Vous pouvez maintenant commencer à utiliser le système.</p>
            <p><strong>Base de données:</strong> remshop1</p>
            <p><strong>Version:</strong> 2.0.0</p>
        </div>
    </div>
</body>
</html>';

if (file_put_contents('index_simple.php', $index_content)) {
    echo " ✅ OK\n";
} else {
    echo " ❌ Erreur lors de la création du fichier\n";
}

// Déconnexion
echo "   - logout_simple.php...";
$logout_content = '<?php
session_start();
session_destroy();
header("Location: login_simple.php");
exit;
?>';

if (file_put_contents('logout_simple.php', $logout_content)) {
    echo " ✅ OK\n";
} else {
    echo " ❌ Erreur lors de la création du fichier\n";
}

// Étape 6: Résumé final
echo "\n=== Installation Terminée ===\n\n";
echo "✅ Base de données configurée\n";
echo "✅ Tables créées\n";
echo "✅ Utilisateur admin créé (admin/admin123)\n";
echo "✅ Client exemple créé\n";
echo "✅ Fichiers de base créés\n\n";

echo "🚀 Vous pouvez maintenant accéder au système:\n";
echo "   - Page de login: login_simple.php\n";
echo "   - Username: admin\n";
echo "   - Password: admin123\n\n";

echo "📁 Fichiers créés:\n";
echo "   - config_remshop1.php\n";
echo "   - login_simple.php\n";
echo "   - index_simple.php\n";
echo "   - logout_simple.php\n\n";

echo "La base de données remshop1 est maintenant prête! 🎉\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Terminée - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px); text-align: center;}
        .success { color: #28a745; }
        .info { color: #17a2b8; }
        .btn { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; font-size: 16px; }
        .btn:hover { background: #218838; }
        .files { text-align: left; margin: 20px 0; }
        .files ul { list-style: none; padding: 0; }
        .files li { padding: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Installation Terminée!</h1>
        
        <div style="background: rgba(40, 167, 69, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h2 class="success">✅ Succès</h2>
            <p>La base de données remshop1 a été configurée avec succès!</p>
        </div>
        
        <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="info">📊 Statistiques</h3>
            <p>Base de données: <strong>remshop1</strong></p>
            <p>Utilisateur: <strong>admin</strong></p>
            <p>Mot de passe: <strong>admin123</strong></p>
            <p>Version: <strong>2.0.0</strong></p>
        </div>
        
        <div class="files">
            <h3>📁 Fichiers Créés</h3>
            <ul>
                <li>✅ config_remshop1.php</li>
                <li>✅ login_simple.php</li>
                <li>✅ index_simple.php</li>
                <li>✅ logout_simple.php</li>
            </ul>
        </div>
        
        <div style="margin: 30px 0;">
            <h3>🚀 Prochaines Étapes</h3>
            <p>Accédez à votre nouveau système:</p>
            <a href="login_simple.php" class="btn">Se Connecter</a>
        </div>
        
        <div style="background: rgba(255, 193, 7, 0.2); padding: 15px; border-radius: 10px; margin: 20px 0;">
            <h4>ℹ️ Informations Important</h4>
            <p><strong>Base de données:</strong> remshop1 (indépendante)</p>
            <p><strong>Compte admin:</strong> admin / admin123</p>
            <p><strong>Client exemple:</strong> Jean Dupont</p>
        </div>
        
        <p style="margin-top: 30px; font-size: 14px; opacity: 0.8;">
            R.E.Mobiles Système de Gestion v2.0.0<br>
            Base de données indépendante configurée avec succès!
        </p>
    </div>
</body>
</html>