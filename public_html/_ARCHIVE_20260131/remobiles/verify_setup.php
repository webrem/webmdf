<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Vérification finale de l'installation
 * Ce script vérifie que tout est correctement configuré
 */

// Configuration
echo "=== Vérification de l'Installation R.E.Mobiles ===\n\n";

$host = 'localhost';
$dbname = 'u498346438_remshop1';
$username = 'u498346438_remshop1';
$password = 'Remshop104';

$all_good = true;

// 1. Test de connexion à la base de données
echo "1. Test de connexion à la base de données...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Connexion réussie!\n";
} catch (PDOException $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
    $all_good = false;
}

if ($all_good) {
    // 2. Vérifier les tables
echo "\n2. Vérification des tables...\n";
    $tables = ['users', 'clients'];
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "   ✅ Table $table: OK\n";
        } catch (PDOException $e) {
            echo "   ❌ Table $table: ERREUR - " . $e->getMessage() . "\n";
            $all_good = false;
        }
    }

    // 3. Vérifier l'utilisateur admin
    echo "\n3. Vérification de l'utilisateur admin...\n";
    try {
        $stmt = $pdo->query("SELECT id, username, first_name, last_name FROM users WHERE username = 'admin'");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "   ✅ Utilisateur admin trouvé:\n";
            echo "      - ID: " . $admin['id'] . "\n";
            echo "      - Username: " . $admin['username'] . "\n";
            echo "      - Nom: " . $admin['first_name'] . " " . $admin['last_name'] . "\n";
        } else {
            echo "   ❌ Utilisateur admin non trouvé\n";
            $all_good = false;
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors de la vérification de l'admin: " . $e->getMessage() . "\n";
        $all_good = false;
    }

    // 4. Compter les enregistrements
    echo "\n4. Statistiques de la base de données...\n";
    try {
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $client_count = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        
        echo "   📊 Utilisateurs: $user_count\n";
        echo "   📊 Clients: $client_count\n";
        
        if ($user_count == 0) {
            echo "   ⚠️  Aucun utilisateur trouvé\n";
        }
        if ($client_count == 0) {
            echo "   ⚠️  Aucun client trouvé\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors du comptage: " . $e->getMessage() . "\n";
        $all_good = false;
    }

    // 5. Vérifier les fichiers PHP
echo "\n5. Vérification des fichiers PHP...\n";
    $files = [
        'config_remshop1.php',
        'login_simple.php',
        'index_simple.php',
        'logout_simple.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "   ✅ $file: Trouvé\n";
        } else {
            echo "   ❌ $file: Manquant\n";
            $all_good = false;
        }
    }

    // 6. Test de la syntaxe PHP
echo "\n6. Vérification de la syntaxe PHP...\n";
    foreach ($files as $file) {
        if (file_exists($file)) {
            $output = shell_exec("php -l $file 2>&1");
            if (strpos($output, 'No syntax errors') !== false) {
                echo "   ✅ $file: Syntaxe OK\n";
            } else {
                echo "   ❌ $file: Erreur de syntaxe\n";
                echo "      " . trim($output) . "\n";
                $all_good = false;
            }
        }
    }

    // 7. Test de connexion avec mot de passe
echo "\n7. Test de connexion avec mot de passe...\n";
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin_password = $stmt->fetchColumn();
        
        if ($admin_password && password_verify('admin123', $admin_password)) {
            echo "   ✅ Mot de passe admin123: Valide\n";
        } else {
            echo "   ❌ Mot de passe admin123: Invalide\n";
            $all_good = false;
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors du test du mot de passe: " . $e->getMessage() . "\n";
        $all_good = false;
    }
}

// Résumé final
echo "\n=== RÉSUMÉ FINAL ===\n\n";

if ($all_good) {
    echo "🎉 TOUT EST FONCTIONNEL! 🎉\n\n";
    echo "✅ Base de données: CONNECTÉE\n";
    echo "✅ Tables: CRÉÉES\n";
    echo "✅ Utilisateur admin: DISPONIBLE\n";
    echo "✅ Fichiers PHP: PRÉSENTS\n";
    echo "✅ Syntaxe PHP: CORRECTE\n";
    echo "✅ Mot de passe: VALIDE\n\n";
    
    echo "🚀 VOUS POUVEZ MAINTENANT UTILISER LE SYSTÈME!\n\n";
    echo "Accédez à: login_simple.php\n";
    echo "Connectez-vous avec: admin / admin123\n";
    
} else {
    echo "❌ DES PROBLÈMES ONT ÉTÉ DÉTECTÉS\n\n";
    echo "Veuillez vérifier les éléments marqués avec ❌ ci-dessus.\n";
    echo "Consultez le guide de dépannage: TROUBLESHOOTING.md\n";
}

// Afficher également en HTML pour le navigateur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px);}
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .btn { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; font-size: 16px; }
        .btn:hover { background: #218838; }
        pre { background: rgba(0,0,0,0.3); padding: 20px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Vérification de l'Installation</h1>
        
        <?php if ($all_good): ?>
            <div style="background: rgba(40, 167, 69, 0.2); padding: 30px; border-radius: 10px; margin: 30px 0; text-align: center;">
                <h2 class="success">🎉 TOUT EST FONCTIONNEL! 🎉</h2>
                <p>Votre système R.E.Mobiles est prêt à être utilisé!</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🚀 Accédez à votre système:</h3>
                <a href="login_simple.php" class="btn">Se Connecter</a>
                <p style="margin-top: 15px;">
                    <strong>Login:</strong> admin<br>
                    <strong>Password:</strong> admin123
                </p>
            </div>
        <?php else: ?>
            <div style="background: rgba(220, 53, 69, 0.2); padding: 30px; border-radius: 10px; margin: 30px 0; text-align: center;">
                <h2 class="error">❌ PROBLÈMES DÉTECTÉS</h2>
                <p>Veuillez corriger les erreurs avant de continuer.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🔧 Solutions:</h3>
                <p>1. Vérifiez les messages d'erreur ci-dessus</p>
                <p>2. Consultez le guide de dépannage</p>
                <p>3. Réexécutez la vérification</p>
                <a href="verify_setup.php" class="btn">Re-vérifier</a>
            </div>
        <?php endif; ?>
        
        <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="info">📊 Informations Système</h3>
            <p><strong>Base de données:</strong> u498346438_remshop1</p>
            <p><strong>Version:</strong> 2.0.0</p>
            <p><strong>Environnement:</strong> Test indépendant</p>
        </div>
        
        <div style="background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="warning">⚠️ Support</h3>
            <p>Si vous rencontrez des problèmes:</p>
            <ul>
                <li>Consultez: TROUBLESHOOTING.md</li>
                <li>Testez: test_remshop1_connection.php</li>
                <li>Vérifiez: test_installation.php</li>
            </ul>
        </div>
    </div>
</body>
</html>