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
        <?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

        session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
        if (!isset($_SESSION["user_id"])) {
            header("Location: login_simple.php");
            exit;
        }
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=u498346438_remshop1;charset=utf8mb4", "u498346438_remshop1", "Remshop104");
            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $client_count = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
        ?>
        
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
</html>