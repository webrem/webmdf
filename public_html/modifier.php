<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();

require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ accessible à tous les utilisateurs connectés
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB : " . $conn->connect_error); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die("ID invalide"); }

// --- Enregistrer les modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom   = trim($_POST['client_nom'] ?? '');
    $tel   = trim($_POST['client_tel'] ?? '');
    $piece = trim($_POST['piece'] ?? '');
    $ref   = trim($_POST['ref_piece'] ?? '');
    $four  = trim($_POST['fournisseur'] ?? '');
    $qte   = (int)($_POST['quantite'] ?? 1);
    $prix  = (float)($_POST['prix_final'] ?? 0);

    $stmt = $conn->prepare("UPDATE historiques 
        SET client_nom=?, client_tel=?, piece=?, ref_piece=?, fournisseur=?, quantite=?, prix_final=? 
        WHERE id=?");
    $stmt->bind_param("sssssdii", $nom, $tel, $piece, $ref, $four, $qte, $prix, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php?updated=1");
    exit;
}

// --- Charger les données actuelles
$stmt = $conn->prepare("SELECT * FROM historiques WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
$stmt->close();

if (!$data) { die("Document introuvable"); }
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>✏️ Modifier Ticket #<?= $id ?> — R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  background: radial-gradient(circle at top left, #0a0a0a, #1a1a1a);
  color: #fff;
  font-family: "Poppins", sans-serif;
  min-height: 100vh;
}

/* Bande supérieure */
.header-banner {
  background: linear-gradient(90deg, #0d6efd, #0dcaf0);
  color: #fff;
  text-align: center;
  font-weight: 700;
  padding: 12px;
  border-radius: 0 0 20px 20px;
  box-shadow: 0 0 25px rgba(13,202,240,0.3);
  text-transform: uppercase;
}

/* Carte effet verre */
.glass {
  background: rgba(20,20,20,0.85);
  border: 1px solid rgba(13,202,240,0.25);
  border-radius: 18px;
  backdrop-filter: blur(10px);
  box-shadow: 0 0 20px rgba(13,202,240,0.15);
  transition: 0.3s;
}
.glass:hover { box-shadow: 0 0 25px rgba(13,202,240,0.35); }

/* Boutons */
.btn-accent {
  background: linear-gradient(90deg, #0dcaf0, #0b5ed7);
  color: #fff;
  font-weight: 700;
  border: none;
  border-radius: 12px;
  transition: all 0.25s ease;
}
.btn-accent:hover {
  transform: scale(1.05);
  box-shadow: 0 0 20px rgba(13,202,240,0.5);
}
.btn-outline-accent {
  border: 2px solid #0dcaf0;
  color: #0dcaf0;
  font-weight: 700;
  border-radius: 12px;
}
.btn-outline-accent:hover {
  background: #0dcaf0;
  color: #000;
  box-shadow: 0 0 15px rgba(13,202,240,0.4);
}

/* Champs */
label { color: #0dcaf0; font-weight: 600; }
.form-control {
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.2);
  color: #fff;
  border-radius: 10px;
}
.form-control:focus {
  border-color: #0dcaf0;
  box-shadow: 0 0 0 0.2rem rgba(13,202,240,0.25);
}
</style>
</head>
<body>

<div class="header-banner">
  ✏️ Modifier Ticket #<?= $id ?> — R.E.Mobiles
</div>

<div class="container py-5">
  <div class="glass p-4 mx-auto" style="max-width:700px;">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-6">
          <label>Nom du client</label>
          <input type="text" name="client_nom" class="form-control" value="<?= htmlspecialchars($data['client_nom']) ?>" required>
        </div>
        <div class="col-md-6">
          <label>Téléphone</label>
          <input type="text" name="client_tel" class="form-control" value="<?= htmlspecialchars($data['client_tel']) ?>" required>
        </div>

        <div class="col-md-6">
          <label>Pièce</label>
          <input type="text" name="piece" class="form-control" value="<?= htmlspecialchars($data['piece']) ?>" required>
        </div>
        <div class="col-md-6">
          <label>Réf. Fournisseur</label>
          <input type="text" name="ref_piece" class="form-control" value="<?= htmlspecialchars($data['ref_piece']) ?>">
        </div>

        <div class="col-md-6">
          <label>Fournisseur</label>
          <input type="text" name="fournisseur" class="form-control" value="<?= htmlspecialchars($data['fournisseur']) ?>">
        </div>
        <div class="col-md-3">
          <label>Quantité</label>
          <input type="number" name="quantite" class="form-control" min="1" value="<?= htmlspecialchars($data['quantite']) ?>">
        </div>
        <div class="col-md-3">
          <label>Prix TTC (€)</label>
          <input type="number" step="0.01" name="prix_final" class="form-control" value="<?= htmlspecialchars($data['prix_final']) ?>" required>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-accent px-4">
          <i class="bi bi-save"></i> Enregistrer
        </button>
        <a href="admin.php" class="btn btn-outline-accent px-4">
          <i class="bi bi-arrow-left"></i> Retour
        </a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
