<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Test pour vérifier que le remplacement de u498346438_remshop1 a fonctionné
 * Vérifie la connexion avec la nouvelle configuration
 */

// Configuration finale avec u498346438_remshop1
$host = 'localhost';
$dbname = 'u498346438_remshop1';
$username = 'u498346438_remshop1';
$password = 'Remshop104';

echo "=== Test du Remplacement de u498346438_remshop1 ===\n\n";
echo "Configuration attendue:\n";
echo "- Base de données: $dbname\n";
echo "- Utilisateur: $username\n";
echo "- Mot de passe: $password\n\n"; 

echo "Test de connexion...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ SUCCÈS! Le remplacement a fonctionné!\n";
    echo "✅ La connexion à $dbname fonctionne!\n";
    echo "✅ L'ancienne configuration u498346438_remshop1 a été remplacée!\n\n";
    
    // Test supplémentaire
    echo "Test de la base de données...\n";
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Test de requête: " . $result['test'] . "\n";
    
    // Vérifier les tables
    echo "\nVérification des tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "✅ Tables trouvées: " . count($tables) . "\n";
        echo "📋 Tables: " . implode(', ', array_slice($tables, 0, 5)) . "\n";
    } else {
        echo "⚠️ Aucune table trouvée (normal pour une nouvelle installation)\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "❌ Le remplacement n'a pas fonctionné correctement\n";
    echo "❌ Vérifiez que tous les fichiers ont été mis à jour\n";
    echo "❌ Erreur de connexion avec u498346438_remshop1\n\n";
    
    echo "Solutions:\n";
    echo "1. Vérifiez que MySQL est en cours d'exécution\n";
    echo "2. Vérifiez que l'utilisateur u498346438_remshop1 existe\n";
    echo "3. Vérifiez que le mot de passe Remshop104 est correct\n";
    echo "4. Exécutez: php setup_mysql_user.php\n";
    echo "5. Relancez: php replace_calculrem_definitively.php\n";
}

echo "\n=== FIN DU TEST ===\n";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Remplacement - R.E.Mobiles</title>
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
        .summary { text-align: center; margin: 30px 0; padding: 30px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test du Remplacement</h1>
        <p style="text-align: center;">Vérification que u498346438_remshop1 a été remplacé par u498346438_remshop1</p>
        
        <?php
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            ?>
            
            <div class="summary" style="background: rgba(40, 167, 69, 0.2);">
                <h2 class="success">🎉 REMPLACEMENT RÉUSSI!</h2>
                <p>La configuration u498346438_remshop1 fonctionne parfaitement!</p>
            </div>
            
            <div style="background: rgba(23, 162, 184, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3 class="info">📊 Informations de Connexion</h3>
                <div style="text-align: center;">
                    <p><strong>Base de données:</strong> <?php echo $dbname; ?></p>
                    <p><strong>Utilisateur:</strong> <?php echo $username; ?></p>
                    <p><strong>Mot de passe:</strong> <?php echo $password; ?></p>
                    <p><strong>Statut:</strong> <span class="success">Connecté</span></p>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🚀 Continuer l'Installation</h3>
                <p>Le remplacement a fonctionné, vous pouvez maintenant installer le système.</p>
                <div style="margin: 20px 0;">
                    <a href="setup_mysql_user.php" class="btn">Configurer MySQL</a>
                    <a href="setup_basic.php" class="btn-secondary">Installer le Système</a>
                </div>
            </div>
            
        <?php } catch (PDOException $e) { ?>
            
            <div class="summary" style="background: rgba(220, 53, 69, 0.2);">
                <h2 class="error">❌ ERREUR DE CONNEXION</h2>
                <p>Le remplacement n'a pas fonctionné correctement.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>🔧 Solutions</h3>
                <p>Veuillez corriger les problèmes avant de continuer:</p>
                <div style="margin: 20px 0;">
                    <a href="replace_calculrem_definitively.php" class="btn">Relancer le Remplacement</a>
                    <a href="setup_mysql_user.php" class="btn-secondary">Configurer MySQL</a>
                </div>
            </div>
            
            <div style="background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3 class="warning">⚠️ Erreur Détectée</h3>
                <p><strong>Message d'erreur:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
                <p><strong>Code d'erreur:</strong> <?php echo $e->getCode(); ?></p>
                
                <h4>Solutions possibles:</h4>
                <ul>
                    <li>Vérifiez que MySQL est en cours d'exécution</li>
                    <li>Vérifiez que l'utilisateur u498346438_remshop1 existe</li>
                    <li>Vérifiez que le mot de passe Remshop104 est correct</li>
                    <li>Exécutez: php setup_mysql_user.php</li>
                    <li>Relancez: php replace_calculrem_definitively.php</li>
                </ul>
            </div>
            
        <?php } ?>
        
        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>📋 Configuration Attendue</h3>
            <p>Après le remplacement, la configuration doit être:</p>
            <ul>
                <li><strong>Base de données:</strong> u498346438_remshop1</li>
                <li><strong>Utilisateur:</strong> u498346438_remshop1</li>
                <li><strong>Mot de passe:</strong> Remshop104</li>
                <li><strong>Hôte:</strong> localhost</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 14px; opacity: 0.8;">
                R.E.Mobiles Système de Gestion<br>
                Test du remplacement u498346438_remshop1 → u498346438_remshop1
            </p>
        </div>
    </div>
</body>
</html>