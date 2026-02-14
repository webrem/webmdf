<!DOCTYPE html>
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
                <label for="username">Nom d'utilisateur</label>
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
</html><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

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
            echo "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } catch (Exception $e) {
        echo "Erreur lors de la connexion: " . $e->getMessage();
    }
} else {
    header("Location: login_simple.php");
}
?>