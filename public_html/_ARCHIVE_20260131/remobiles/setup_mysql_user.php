<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Configuration MySQL pour u498346438_remshop1
 * Ce script configure l'utilisateur et les permissions
 */

// Configuration MySQL root (à adapter selon votre configuration)
$mysql_root_user = 'root';
$mysql_root_password = ''; // Laissez vide si pas de mot de passe

// Configuration pour remshop1
$db_name = 'u498346438_remshop1';
$db_user = 'u498346438_remshop1';
$db_password = 'Remshop104';

echo "=== Configuration MySQL pour R.E.Mobiles ===\n\n";

// Méthode 1: Utiliser mysql.exe (si disponible)
echo "1. Tentative de configuration avec mysql.exe...\n";

$mysql_commands = [
    "CREATE DATABASE IF NOT EXISTS $db_name;",
    "CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_password';",
    "GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'localhost';",
    "FLUSH PRIVILEGES;",
    "SELECT 'Configuration terminée' AS status;"
];

$mysql_command = "mysql -u $mysql_root_user";
if (!empty($mysql_root_password)) {
    $mysql_command .= " -p$mysql_root_password";
}

$full_command = $mysql_command . " -e \"" . implode(' ', $mysql_commands) . "\"";

exec($full_command . " 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "   ✅ Configuration MySQL réussie!\n";
    echo "   Base de données créée: $db_name\n";
    echo "   Utilisateur créé: $db_user\n";
    echo "   Permissions accordées\n";
} else {
    echo "   ❌ Échec de la configuration avec mysql.exe\n";
    echo "   Erreur: " . implode("\n", $output) . "\n";
    
    // Méthode 2: Fichier SQL
    echo "\n2. Création d'un fichier SQL de configuration...\n";
    
    $sql_content = "-- Configuration R.E.Mobiles pour u498346438_remshop1\n";
    $sql_content .= "-- Exécutez ces commandes dans MySQL\n\n";
    $sql_content .= "CREATE DATABASE IF NOT EXISTS $db_name;\n";
    $sql_content .= "CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_password';\n";
    $sql_content .= "GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'localhost';\n";
    $sql_content .= "FLUSH PRIVILEGES;\n";
    $sql_content .= "SELECT 'Configuration terminée' AS status;\n";
    
    if (file_put_contents('setup_remshop1.sql', $sql_content)) {
        echo "   ✅ Fichier SQL créé: setup_remshop1.sql\n";
        echo "\n   Pour configurer manuellement, exécutez:\n";
        echo "   mysql -u root -p < setup_remshop1.sql\n";
    } else {
        echo "   ❌ Impossible de créer le fichier SQL\n";
    }
}

// Méthode 3: Instructions manuelles
echo "\n=== Instructions Manuelles ===\n\n";
echo "Si les méthodes automatiques échouent, suivez ces étapes:\n\n";

echo "1. Connectez-vous à MySQL:\n";
echo "   mysql -u root -p\n\n";

echo "2. Exécutez ces commandes:\n";
echo "   CREATE DATABASE IF NOT EXISTS $db_name;\n";
echo "   CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_password';\n";
echo "   GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'localhost';\n";
echo "   FLUSH PRIVILEGES;\n";
echo "   EXIT;\n\n";

echo "3. Testez la connexion:\n";
echo "   mysql -u $db_user -p $db_name\n";
echo "   (Entrez le mot de passe: $db_password)\n\n";

// Test de connexion après configuration
echo "=== Test de Connexion ===\n\n";
echo "Test de connexion avec les nouvelles informations...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ SUCCÈS! La connexion fonctionne!\n\n";
    
    // Vérifier les permissions
    $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Permissions accordées:\n";
    foreach ($grants as $grant) {
        echo "   - $grant\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
    echo "\nAssurez-vous que:\n";
    echo "- MySQL est en cours d'exécution\n";
    echo "- L'utilisateur $db_user existe\n";
    echo "- Les permissions sont correctement définies\n";
    echo "- Le mot de passe est correct\n";
}

// Créer un script de test
echo "\n=== Script de Test ===\n";
$test_script = '<?php
/**
 * Test de connexion pour u498346438_remshop1
 */
$host = "localhost";
$dbname = "u498346438_remshop1";
$username = "u498346438_remshop1";
$password = "Remshop104";

echo "Test de connexion à la base de données u498346438_remshop1...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ SUCCÈS! Connexion établie.\n";
    
    // Test simple
    $result = $pdo->query("SELECT 1 as test");
    $row = $result->fetch();
    echo "✅ Test de requête: " . $row["test"] . "\n";
    
    echo "\nLa base de données est prête! 🎉\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "\nVérifiez:\n";
    echo "- Que MySQL est en cours d\'exécution\n";
    echo "- Que la base de données u498346438_remshop1 existe\n";
    echo "- Que l\'utilisateur u498346438_remshop1 a les bonnes permissions\n";
    echo "- Que le mot de passe est correct\n";
}
?>';

if (file_put_contents('test_connection_final.php', $test_script)) {
    echo "✅ Script de test créé: test_connection_final.php\n";
    echo "Exécutez: php test_connection_final.php\n";
}

echo "\n=== FIN ===\n";
echo "Configuration terminée!\n";
echo "Si la connexion échoue, utilisez les instructions manuelles ci-dessus.\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration MySQL - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px);}
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .code { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #218838; }
        h1, h2, h3 { text-align: center; }
        .section { margin: 20px 0; padding: 20px; border-radius: 10px; }
        .method { background: rgba(255,255,255,0.1); margin: 10px 0; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Configuration MySQL pour R.E.Mobiles</h1>
        <p style="text-align: center;">Configuration pour la base de données u498346438_remshop1</p>
        
        <div class="section" style="background: rgba(40, 167, 69, 0.2);">
            <h2 class="success">✅ Configuration Réussie!</h2>
            <p>Si vous voyez ce message, la configuration MySQL a fonctionné.</p>
        </div>
        
        <div class="section" style="background: rgba(23, 162, 184, 0.2);">
            <h3 class="info">📋 Informations de Configuration</h3>
            <div class="code">
Base de données: u498346438_remshop1<br>
Utilisateur: u498346438_remshop1<br>
Mot de passe: Remshop104<br>
Host: localhost
            </div>
        </div>
        
        <div class="section" style="background: rgba(255, 193, 7, 0.2);">
            <h3 class="warning">⚠️ Problème de Connexion?</h3>
            <p>Si vous obtenez une erreur de connexion, suivez ces étapes:</p>
            
            <div class="method">
                <h4>1. Configuration Manuelle</h4>
                <p>Connectez-vous à MySQL et exécutez ces commandes:</p>
                <div class="code">
CREATE DATABASE IF NOT EXISTS u498346438_remshop1;<br>
CREATE USER IF NOT EXISTS 'u498346438_remshop1'@'localhost' IDENTIFIED BY 'Remshop104';<br>
GRANT ALL PRIVILEGES ON u498346438_remshop1.* TO 'u498346438_remshop1'@'localhost';<br>
FLUSH PRIVILEGES;
                </div>
            </div>
            
            <div class="method">
                <h4>2. Utiliser le fichier SQL</h4>
                <p>Un fichier SQL a été créé. Exécutez:</p>
                <div class="code">
                    mysql -u root -p < setup_remshop1.sql
                </div>
                <a href="setup_remshop1.sql" class="btn" download>Télécharger setup_remshop1.sql</a>
            </div>
        </div>
        
        <div class="section" style="background: rgba(255,255,255,0.1);">
            <h3>🧪 Test de Connexion</h3>
            <p>Pour tester la connexion, exécutez:</p>
            <div class="code">
                php test_connection_final.php
            </div>
            <a href="test_connection_final.php" class="btn">Tester la Connexion</a>
        </div>
        
        <div class="section" style="background: rgba(255,255,255,0.1);">
            <h3>🚀 Continuer l'Installation</h3>
            <p>Une fois la configuration MySQL terminée:</p>
            <a href="setup_basic.php" class="btn">Installer le Système</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 14px; opacity: 0.8;">
                R.E.Mobiles Système de Gestion<br>
                Configuration MySQL pour u498346438_remshop1
            </p>
        </div>
    </div>
</body>
</html>