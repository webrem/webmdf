<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin') {
    header("Location: login.php"); exit;
}
$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB: ".$conn->connect_error); }

$id = (int)($_GET['id'] ?? 0);

// Mise à jour
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $stmt = $conn->prepare("UPDATE historiques 
        SET client_nom=?, client_tel=?, piece=?, ref_piece=?, fournisseur=?, quantite=?, prix_final=? 
        WHERE id=?");
    $stmt->bind_param("ssssiddi",
        $_POST['client_nom'], $_POST['client_tel'], $_POST['piece'],
        $_POST['ref_piece'], $_POST['fournisseur'], $_POST['quantite'],
        $_POST['prix_final'], $id);
    $stmt->execute();
    $stmt->close();
    header("Location: commandes.php?msg=updated");
    exit;
}

// Récupération infos actuelles
$stmt = $conn->prepare("SELECT * FROM historiques WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Modifier Commande</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h2>✏️ Modifier la commande #<?= $id ?></h2>
  <form method="post">
    <label>Client</label>
    <input type="text" name="client_nom" class="form-control" value="<?= htmlspecialchars($data['client_nom']) ?>">
    <label>Téléphone</label>
    <input type="text" name="client_tel" class="form-control" value="<?= htmlspecialchars($data['client_tel']) ?>">
    <label>Pièce</label>
    <input type="text" name="piece" class="form-control" value="<?= htmlspecialchars($data['piece']) ?>">
    <label>Réf. Fournisseur</label>
    <input type="text" name="ref_piece" class="form-control" value="<?= htmlspecialchars($data['ref_piece']) ?>">
    <label>Fournisseur</label>
    <input type="text" name="fournisseur" class="form-control" value="<?= htmlspecialchars($data['fournisseur']) ?>">
    <label>Quantité</label>
    <input type="number" name="quantite" class="form-control" value="<?= (int)$data['quantite'] ?>">
    <label>Prix TTC</label>
    <input type="number" step="0.01" name="prix_final" class="form-control" value="<?= number_format((float)$data['prix_final'],2,'.','') ?>">
    <button type="submit" class="btn btn-primary mt-3">💾 Enregistrer</button>
    <a href="commandes.php" class="btn btn-secondary mt-3">Annuler</a>
  </form>
</div>
</body>
</html>
