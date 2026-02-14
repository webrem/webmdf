<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

$servername = "localhost";
$username = "u498346438_calculrem";
$password = "Calculrem1";
$dbname = "u498346438_calculrem";

// Connexion BDD
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    die("❌ Erreur connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_user = trim($_POST['username']);
    $plain_pass = trim($_POST['password']);
    $role = ($_POST['role'] === 'admin') ? 'admin' : 'user';

    if ($new_user === '' || $plain_pass === '') {
        $msg = "⚠️ Merci de remplir tous les champs.";
    } else {
        // Vérifier si l’utilisateur existe déjà
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $new_user);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = "⚠️ L'utilisateur '$new_user' existe déjà.";
        } else {
            $stmt->close();
            // Créer le hash
            $hash = password_hash($plain_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $new_user, $hash, $role);
            if ($stmt->execute()) {
                $msg = "✅ Utilisateur '$new_user' créé avec succès (rôle : $role).";
            } else {
                $msg = "❌ Erreur SQL : " . $conn->error;
            }
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Créer un utilisateur</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container py-5">
  <h2 class="mb-4">👤 Créer un utilisateur</h2>

  <?php if ($msg): ?>
    <div class="alert alert-info"><?=$msg?></div>
  <?php endif; ?>

  <form method="post" class="card p-4 bg-secondary">
    <div class="mb-3">
      <label class="form-label">Nom d’utilisateur</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mot de passe</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Rôle</label>
      <select name="role" class="form-select">
        <option value="user">Utilisateur</option>
        <option value="admin">Administrateur</option>
      </select>
    </div>
    <button type="submit" class="btn btn-success">Créer</button>
    <a href="dashboard.php" class="btn btn-outline-light">⬅ Retour au dashboard</a>
  </form>
</div>
</body>
</html>
