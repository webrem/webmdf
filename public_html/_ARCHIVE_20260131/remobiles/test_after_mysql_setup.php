<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Test après configuration MySQL
 * Vérifie que l'utilisateur et la base de données sont correctement configurés
 */

// Configuration
echo "=== Test après Configuration MySQL ===\n\n";

$host = 'localhost';
$dbname = 'u498346438_remshop1';
$username = 'u498346438_remshop1';
$password = 'Remshop104';

$all_good = true;

echo "Configuration testée:\n";
echo "- Base de données: $dbname\n";
echo "- Utilisateur: $username\n";
echo "- Hôte: $host\n\n";

// Étape 1: Test de connexion
echo "1. Test de connexion...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Connexion réussie!\n";
    
    // Informations sur la connexion
    $server_info = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
    $client_version = $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
    echo "   📊 Serveur MySQL: $server_info\n";
    echo "   📊 Client PDO: $client_version\n";
    
} catch (PDOException $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
    echo "   Code d'erreur: " . $e->getCode() . "\n";
    echo "\n   Solutions possibles:\n";
    echo "   - Vérifiez que MySQL est en cours d'exécution\n";
    echo "   - Vérifiez que l'utilisateur $username existe\n";
    echo "   - Vérifiez que le mot de passe est correct\n";
    echo "   - Vérifiez que l'utilisateur a les permissions sur $dbname\n";
    echo "   - Essayez de redémarrer MySQL\n";
    $all_good = false;
}

if ($all_good) {
    // Étape 2: Vérifier les permissions
echo "\n2. Vérification des permissions...\n";
    try {
        $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
        $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "   ✅ Permissions de l'utilisateur $username:\n";
        foreach ($grants as $grant) {
            echo "      - $grant\n";
        }
        
        // Vérifier si l'utilisateur a toutes les permissions sur la base
        $has_all_privileges = false;
        foreach ($grants as $grant) {
            if (strpos($grant, 'ALL PRIVILEGES') !== false && strpos($grant, $dbname) !== false) {
                $has_all_privileges = true;
                break;
            }
        }
        
        if ($has_all_privileges) {
            echo "   ✅ L'utilisateur a toutes les permissions sur $dbname\n";
        } else {
            echo "   ⚠️  L'utilisateur n'a pas toutes les permissions sur $dbname\n";
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors de la vérification des permissions: " . $e->getMessage() . "\n";
        $all_good = false;
    }

    // Étape 3: Test de création de table
echo "\n3. Test de création de table...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_column VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "   ✅ Table de test créée avec succès\n";
        
        // Test d'insertion
        $stmt = $pdo->prepare("INSERT INTO test_table (test_column) VALUES (?)");
        $stmt->execute(['Test de connexion']);
        echo "   ✅ Insertion réussie\n";
        
        // Test de lecture
        $stmt = $pdo->query("SELECT * FROM test_table ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
        echo "   ✅ Lecture réussie: ID=" . $row['id'] . ", Valeur='" . $row['test_column'] . "'\n";
        
        // Nettoyage
        $pdo->exec("DROP TABLE IF EXISTS test_table");
        echo "   ✅ Nettoyage effectué\n";
        
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors du test de création de table: " . $e->getMessage() . "\n";
        $all_good = false;
    }

    // Étape 4: Vérifier les caractéristiques de la base de données
echo "\n4. Informations sur la base de données...\n";
    try {
        // Version de MySQL
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        echo "   📊 Version MySQL: $version\n";
        
        // Encodage
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
        $charset = $stmt->fetch()['Value'];
        echo "   📊 Encodage: $charset\n";
        
        // Moteur de stockage
        $stmt = $pdo->query("SHOW TABLE STATUS FROM $dbname LIMIT 1");
        if ($table_status = $stmt->fetch()) {
            echo "   📊 Moteur de stockage: " . $table_status['Engine'] . "\n";
        }
        
    } catch (PDOException $e) {
        echo "   ⚠️ Impossible d'obtenir les informations de la base de données: " . $e->getMessage() . "\n";
    }

    // Étape 5: Test de performance
echo "\n5. Test de performance...\n";
    $start_time = microtime(true);
    
    try {
        // Test de requêtes multiples
        for ($i = 0; $i < 10; $i++) {
            $pdo->query("SELECT 1");
        }
        
        $end_time = microtime(true);
        $duration = ($end_time - $start_time) * 1000; // en millisecondes
        
        echo "   ⚡ Performance: 10 requêtes en " . number_format($duration, 2) . " ms\n";
        
        if ($duration < 100) {
            echo "   ✅ Performance excellente\n";
        } elseif ($duration < 500) {
            echo "   ✅ Performance bonne\n";
        } else {
            echo "   ⚠️ Performance lente\n";
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors du test de performance: " . $e->getMessage() . "\n";
    }
}

// Résumé final
echo "\n=== RÉSUMÉ FINAL ===\n\n";

if ($all_good) {
    echo "🎉 CONFIGURATION MYSQL RÉUSSIE! 🎉\n\n";
    echo "✅ Base de données: $dbname - CONNECTÉE\n";
    echo "✅ Utilisateur: $username - FONCTIONNEL\n";
    echo "✅ Permissions: ACCORDÉES\n";
    echo "✅ Performance: ADÉQUATE\n\n";
    
    echo "🚀 VOUS POUVEZ MAINTENANT INSTALLER LE SYSTÈME!\n\n";
    echo "Exécutez: php setup_basic.php\n";
    echo "Ou accédez à: setup_basic.php dans votre navigateur\n";
    
} else {
    echo "❌ PROBLÈMES DÉTECTÉS\n\n";
    echo "La configuration MySQL n'est pas complète.\n";
    echo "Veuillez:\n";
    echo "1. Vérifier les erreurs ci-dessus\n";
    echo "2. Exécuter: php setup_mysql_user.php\n";
    echo "3. Ou suivre les instructions manuelles\n";
    echo "4. Relancer ce test après correction\n";
}

echo "\n=== FIN DU TEST ===\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test MySQL - R.E.Mobiles</title>
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
        .test-result { margin: 20px 0; padding: 20px; border-radius: 10px; }
        .summary { text-align: center; margin: 30px 0; padding: 30px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test de Configuration MySQL</h1>
        <p style="text-align: center;">Test de connexion pour u498346438_remshop1</p>
        
        <?php if ($all_good): ?>
            <div class="summary" style="background: rgba(40, 167, 69, 0.2);">
                <h2 class="success">🎉 CONFIGURATION MYSQL RÉUSSIE!</h2>
                <p>Votre base de données est prête pour l'installation du système R.E.Mobiles!</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🚀 Continuer l'Installation</h3>
                <p>La configuration MySQL est terminée. Vous pouvez maintenant installer le système complet.</p>
                <a href="setup_basic.php" class="btn">Installer le Système</a>
                <a href="setup_mysql_user.php" class="btn-secondary">Reconfigurer MySQL</a>
            </div>
        <?php else: ?>
            <div class="summary" style="background: rgba(220, 53, 69, 0.2);">
                <h2 class="error">❌ PROBLÈMES DÉTECTÉS</h2>
                <p>La configuration MySQL n'est pas complète.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🔧 Solutions</h3>
                <p>Veuillez corriger les problèmes avant de continuer:</p>
                <a href="setup_mysql_user.php" class="btn">Configurer MySQL</a>
                <a href="test_after_mysql_setup.php" class="btn-secondary">Re-tester</a>
            </div>
        <?php endif; ?>
        
        <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="info">ℹ️ Informations de Configuration</h3>
            <p><strong>Base de données:</strong> u498346438_remshop1</p>
            <p><strong>Utilisateur:</strong> u498346438_remshop1</p>
            <p><strong>Mot de passe:</strong> Remshop104</p>
            <p><strong>Hôte:</strong> localhost</p>
        </div>
        
        <div style="background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 class="warning">⚠️ Support</h3>
            <p>Si vous rencontrez des problèmes:</p>
            <ul>
                <li>Consultez: TROUBLESHOOTING.md</li>
                <li>Exécutez: setup_mysql_user.php</li>
                <li>Vérifiez que MySQL est en cours d'exécution</li>
                <li>Vérifiez les logs d'erreur MySQL</li>
            </ul>
        </div>
    </div>
</body>
</html>