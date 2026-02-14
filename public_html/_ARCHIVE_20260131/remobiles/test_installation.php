<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Test de l'installation - Vérifie que tout fonctionne correctement
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la base de données
$db_config = [
    'host' => 'localhost',
    'name' => 'u498346438_remshop1',
    'user' => 'u498346438_remshop1',
    'pass' => 'Remshop104'
];

// Fonction pour tester la connexion
function test_connection() {
    global $db_config;
    
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
            $db_config['user'],
            $db_config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        return ['success' => true, 'message' => 'Connexion réussie!', 'pdo' => $pdo];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

// Fonction pour tester les tables
function test_tables($pdo) {
    $tables = ['users', 'clients'];
    $results = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $results[$table] = ['exists' => true, 'count' => $count];
        } catch (Exception $e) {
            $results[$table] = ['exists' => false, 'error' => $e->getMessage()];
        }
    }
    
    return $results;
}

// Fonction pour tester l'utilisateur admin
function test_admin_user($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $user = $stmt->fetch();
        
        if ($user) {
            return ['exists' => true, 'user' => $user];
        } else {
            return ['exists' => false];
        }
    } catch (Exception $e) {
        return ['exists' => false, 'error' => $e->getMessage()];
    }
}

// Exécuter les tests
$connection_test = test_connection();
$tables_test = [];
$admin_test = [];

if ($connection_test['success']) {
    $pdo = $connection_test['pdo'];
    $tables_test = test_tables($pdo);
    $admin_test = test_admin_user($pdo);
}

// Afficher les résultats
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test d'Installation - R.E.Mobiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; backdrop-filter: blur(10px);}
        .test-result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: rgba(40, 167, 69, 0.2); border-left: 4px solid #28a745; }
        .error { background: rgba(220, 53, 69, 0.2); border-left: 4px solid #dc3545; }
        .warning { background: rgba(255, 193, 7, 0.2); border-left: 4px solid #ffc107; }
        .info { background: rgba(23, 162, 184, 0.2); border-left: 4px solid #17a2b8; }
        .btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #218838; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        h1, h2 { text-align: center; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 2em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test d'Installation - R.E.Mobiles</h1>
        <p style="text-align: center;">Base de données: u498346438_remshop1</p>
        
        <!-- Test de connexion -->
        <div class="test-result <?php echo $connection_test['success'] ? 'success' : 'error'; ?>">
            <h3>🔌 Test de Connexion</h3>
            <p><?php echo htmlspecialchars($connection_test['message']); ?></p>
            <?php if ($connection_test['success']): ?>
                <p><strong>✅ Connexion établie avec succès!</strong></p>
            <?php else: ?>
                <p><strong>❌ Erreur de connexion</strong></p>
                <p>Vérifiez vos identifiants de base de données</p>
            <?php endif; ?>
        </div>
        
        <?php if ($connection_test['success']): ?>
            <!-- Test des tables -->
            <div class="test-result info">
                <h3>📊 Test des Tables</h3>
                <?php foreach ($tables_test as $table => $result): ?>
                    <p>
                        <strong><?php echo ucfirst($table); ?>:</strong>
                        <?php if ($result['exists']): ?>
                            <span style="color: #28a745;">✅ Existe</span> 
                            (<?php echo $result['count']; ?> enregistrements)
                        <?php else: ?>
                            <span style="color: #dc3545;">❌ N'existe pas</span>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            </div>
            
            <!-- Test de l'utilisateur admin -->
            <div class="test-result <?php echo $admin_test['exists'] ? 'success' : 'warning'; ?>">
                <h3>👤 Test Utilisateur Admin</h3>
                <?php if ($admin_test['exists']): ?>
                    <p><strong>✅ Utilisateur admin trouvé!</strong></p>
                    <p>Username: <?php echo htmlspecialchars($admin_test['user']['username']); ?></p>
                    <p>Nom: <?php echo htmlspecialchars($admin_test['user']['first_name'] . ' ' . $admin_test['user']['last_name']); ?></p>
                    <p><strong>Login: admin / Mot de passe: admin123</strong></p>
                <?php else: ?>
                    <p><strong>⚠️ Utilisateur admin non trouvé</strong></p>
                    <p>Exécutez le script d'installation pour créer l'utilisateur admin</p>
                <?php endif; ?>
            </div>
            
            <!-- Statistiques -->
            <?php if (!empty($tables_test)): ?>
                <div class="stats">
                    <?php foreach ($tables_test as $table => $result): ?>
                        <?php if ($result['exists']): ?>
                            <div class="stat-card">
                                <h3><?php echo $result['count']; ?></h3>
                                <p><?php echo ucfirst($table); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="text-align: center; margin-top: 30px;">
            <h3>Actions Disponibles</h3>
            
            <?php if (!$connection_test['success']): ?>
                <p style="color: #ffc107;">❌ Résolvez d'abord le problème de connexion</p>
            <?php elseif (!$admin_test['exists']): ?>
                <a href="install_remshop1_simple.php" class="btn">Installer le système</a>
            <?php else: ?>
                <a href="login_simple.php" class="btn">Tester la connexion</a>
                <a href="index_simple.php" class="btn btn-secondary">Accéder au tableau de bord</a>
            <?php endif; ?>
            
            <br><br>
            <a href="test_remshop1_connection.php" class="btn btn-secondary">Retester la connexion</a>
        </div>
        
        <!-- Informations -->
        <div class="test-result info" style="margin-top: 30px;">
            <h3>ℹ️ Informations</h3>
            <p><strong>Base de données:</strong> u498346438_remshop1</p>
            <p><strong>Utilisateur:</strong> u498346438_remshop1</p>
            <p><strong>Version:</strong> 2.0.0</p>
            <p><strong>Environnement:</strong> Test indépendant</p>
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);">
            <p>R.E.Mobiles Système de Gestion v2.0.0</p>
        </div>
    </div>
</body>
</html>