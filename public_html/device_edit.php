<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['role'])) { 
    header("Location: login.php"); 
    exit; 
}

/* =========================
   CONNEXION BASE DE DONNÉES
   ========================= */
$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) {
    die("Erreur DB : " . $conn->connect_error);
}

/* =========================
   RÉCUPÉRATION RÉF APPAREIL
   ========================= */
$ref = $_GET['ref'] ?? '';
if ($ref === '') {
    die("Réf manquante");
}

/* =========================
   TRAITEMENT FORMULAIRE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $client = trim($_POST['client_name']);
    $phone  = trim($_POST['client_phone']);
    $email  = trim($_POST['client_email']);
    $model  = trim($_POST['model']);
    $repair = (float)$_POST['price_repair'];
    $diag   = (float)$_POST['price_diagnostic'];
    $tech   = trim($_POST['technician_name']);

    $stmt = $conn->prepare("
        UPDATE devices 
        SET 
            client_name=?,
            client_phone=?,
            client_email=?,
            model=?,
            price_repair=?,
            price_diagnostic=?,
            technician_name=?
        WHERE ref=?
    ");
    $stmt->bind_param(
        "ssssddss",
        $client,
        $phone,
        $email,
        $model,
        $repair,
        $diag,
        $tech,
        $ref
    );
    $stmt->execute();
    $stmt->close();

    header("Location: devices_list.php");
    exit;
}

/* =========================
   RÉCUPÉRATION APPAREIL
   ========================= */
$stmt = $conn->prepare("SELECT * FROM devices WHERE ref=?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$res  = $stmt->get_result();
$data = $res->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Appareil introuvable");
}

/* =========================
   RÉCUPÉRATION RÉPARATEURS
   ========================= */
$technicians = [];
$resTech = $conn->query("
    SELECT username
    FROM users
    WHERE role IN ('admin','user')
    ORDER BY username ASC
");


if ($resTech) {
    while ($row = $resTech->fetch_assoc()) {
        $technicians[] = $row['username'];
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Modifier appareil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">

  <h2>✏️ Modifier fiche appareil #<?= htmlspecialchars($ref) ?></h2>

  <form method="post" class="card p-3 shadow">

    <div class="mb-3">
      <label>Client</label>
      <input type="text" name="client_name" class="form-control"
             value="<?= htmlspecialchars($data['client_name']) ?>">
    </div>

    <div class="mb-3">
      <label>Téléphone</label>
      <input type="text" name="client_phone" class="form-control"
             value="<?= htmlspecialchars($data['client_phone']) ?>">
    </div>

    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="client_email" class="form-control"
             value="<?= htmlspecialchars($data['client_email']) ?>">
    </div>

    <div class="mb-3">
      <label>Modèle</label>
      <input type="text" name="model" class="form-control"
             value="<?= htmlspecialchars($data['model']) ?>">
    </div>

    <div class="mb-3">
      <label>Prix Réparation (€)</label>
      <input type="number" step="0.01" name="price_repair" class="form-control"
             value="<?= htmlspecialchars($data['price_repair']) ?>">
    </div>

    <div class="mb-3">
      <label>Prix Diagnostic (€)</label>
      <input type="number" step="0.01" name="price_diagnostic" class="form-control"
             value="<?= htmlspecialchars($data['price_diagnostic']) ?>">
    </div>

    <div class="mb-3">
      <label>Réparateur</label>
      <select name="technician_name" class="form-select">
        <option value="">-- Sélectionner --</option>
        <?php foreach ($technicians as $tech): ?>
          <option value="<?= htmlspecialchars($tech) ?>"
            <?= ($data['technician_name'] === $tech ? 'selected' : '') ?>>
            <?= htmlspecialchars($tech) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-success">💾 Enregistrer</button>
    <a href="devices_list.php" class="btn btn-secondary">⬅️ Annuler</a>

  </form>

</div>
</body>
</html>
