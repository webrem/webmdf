<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB");
$conn->set_charset("utf8mb4");

$msg = "";

/* === AJOUT CLIENT === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $nom = trim($_POST['nom']);
    $societe = trim($_POST['societe']);
    $telephone = trim($_POST['telephone']);
    $email = trim($_POST['email']);
    $adresse = trim($_POST['adresse']);
    $type = trim($_POST['type_client']);
    $remise = (int)($_POST['remise_pct'] ?? 0);

    if ($nom && $telephone) {
        $stmt = $conn->prepare("INSERT INTO clients (nom, societe, telephone, email, adresse, type_client, remise_pct) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssi", $nom, $societe, $telephone, $email, $adresse, $type, $remise);
        $stmt->execute();
        $msg = "✅ Client ajouté avec succès !";
    } else {
        $msg = "⚠️ Nom et téléphone obligatoires.";
    }
}

/* === IMPORT CSV : CHARGEMENT === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_clients'])) {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file, 1000, ',');
        $_SESSION['csv_preview'] = [];
        while (($data = fgetcsv($file, 1000, ',')) !== false) {
            $_SESSION['csv_preview'][] = $data;
        }
        $_SESSION['csv_header'] = $header;
        fclose($file);
        header("Location: clients.php?preview=1");
        exit;
    }
}

/* === IMPORT CSV : CONFIRMATION === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $map = $_POST['map'] ?? [];
    $rows = $_SESSION['csv_preview'] ?? [];
    $count = 0;
    foreach ($rows as $r) {
        $nom       = trim($r[$map['nom']] ?? '');
        $societe   = trim($r[$map['societe']] ?? '');
        $telephone = trim($r[$map['telephone']] ?? '');
        $email     = trim($r[$map['email']] ?? '');
        $adresse   = trim($r[$map['adresse']] ?? '');
        $remise    = (int)($r[$map['remise_pct']] ?? 0);
        $type = 'Particulier';
        if ($nom && $telephone) {
            $stmt = $conn->prepare("INSERT INTO clients (nom, societe, telephone, email, adresse, type_client, remise_pct) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssi", $nom, $societe, $telephone, $email, $adresse, $type, $remise);
            $stmt->execute();
            $count++;
        }
    }
    unset($_SESSION['csv_preview'], $_SESSION['csv_header']);
    $msg = "✅ $count clients importés avec succès.";
}

/* === SUPPRESSION MULTIPLE === */
if ($isAdmin && isset($_POST['delete_multiple']) && !empty($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $conn->query("DELETE FROM clients WHERE id IN (" . implode(',', $ids) . ")");
    $msg = "🗑️ " . count($ids) . " clients supprimés avec succès.";
}

/* === SUPPRESSION SIMPLE === */
if ($isAdmin && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM clients WHERE id = $id");
    $msg = "🗑️ Client supprimé.";
}

/* === EXPORT CSV === */
if (isset($_GET['export']) && $isAdmin) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=clients_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nom','Société','Téléphone','Email','Adresse','Type','Remise %','Date']);
    $res = $conn->query("SELECT nom,societe,telephone,email,adresse,type_client,remise_pct,created_at FROM clients ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) fputcsv($out, $row);
    fclose($out); exit;
}

/* === SUPPRESSION DES DOUBLONS === */
if ($isAdmin && isset($_POST['delete_duplicates'])) {
    $sql = "
        DELETE c1 FROM clients c1
        INNER JOIN clients c2 
        WHERE 
            c1.id > c2.id 
            AND c1.nom = c2.nom 
            AND c1.telephone = c2.telephone
    ";
    if ($conn->query($sql)) {
        $deleted = $conn->affected_rows;
        $msg = "✅ $deleted doublon(s) supprimé(s) avec succès.";
    } else {
        $msg = "❌ Erreur lors de la suppression : " . $conn->error;
    }
}

/* === RECHERCHE === */
$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM clients";
if ($search !== '') {
    $like = "%" . $conn->real_escape_string($search) . "%";
    $sql .= " WHERE nom LIKE '$like' OR societe LIKE '$like' OR telephone LIKE '$like' OR email LIKE '$like'";
}
$sql .= " ORDER BY id DESC";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Gestion Clients - R.E.Mobiles</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<style>
:root {
    --primary:#0dcaf0;
    --primary-dark:#0b5ed7;
    --dark:#0a0a0a;
    --darker:#000;
    --light:#fff;
}
body {
    background: linear-gradient(135deg,var(--dark) 0%,#1a1a1a 50%,var(--darker) 100%);
    color: #e8f6ff; /* Texte global plus clair */
    font-family:'Inter',sans-serif;
    min-height:100vh;
    overflow-x:hidden;
}

.card, .bg-dark {
    background: rgba(255,255,255,0.05)!important;
    backdrop-filter: blur(15px);
    border:1px solid rgba(13,202,240,0.2)!important;
    border-radius:16px!important;
    box-shadow:0 8px 32px rgba(0,0,0,0.4);
    color: #f2f9ff !important; /* Texte clair dans les cartes */
}
.grid-pattern {
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background-image:
        linear-gradient(rgba(13,202,240,0.1) 1px,transparent 1px),
        linear-gradient(90deg,rgba(13,202,240,0.1) 1px,transparent 1px);
    background-size:50px 50px;
    animation:gridMove 20s linear infinite;
    z-index:-1;
}
@keyframes gridMove {0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.card, .bg-dark {
    background: rgba(255,255,255,0.05)!important;
    backdrop-filter: blur(15px);
    border:1px solid rgba(13,202,240,0.2)!important;
    border-radius:16px!important;
    box-shadow:0 8px 32px rgba(0,0,0,0.4);
}
h2 {
    color:var(--primary);
    text-shadow:0 0 10px rgba(13,202,240,0.6);
    font-weight:700;
    margin-bottom:2rem;
}
input, textarea, select {
    background:rgba(255,255,255,0.08)!important;
    border:1px solid rgba(255,255,255,0.1)!important;
    color:white!important;
    border-radius:10px!important;
}
input:focus, textarea:focus, select:focus {
    border-color:var(--primary)!important;
    box-shadow:0 0 0 3px rgba(13,202,240,0.2)!important;
}
.table {
    color:#fff;
    background:transparent;
}
.table thead th {
    color:var(--primary);
    border-bottom:2px solid var(--primary);
}
.table-striped>tbody>tr:nth-of-type(odd){
    background-color:rgba(255,255,255,0.05);
}
.btn-green{background:linear-gradient(135deg,#198754,#0b8e61);border:none;color:white;}
.btn-red{background:linear-gradient(135deg,#dc3545,#b02a37);border:none;color:white;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;}
.btn-warning{background:linear-gradient(135deg,#ffc107,#e0a800);border:none;color:#000;}
.table-hover tbody tr:hover{background-color:rgba(13,202,240,0.1);}
.fade-in{opacity:0;transform:translateY(20px);animation:fadeInUp .8s ease forwards;}
@keyframes fadeInUp{to{opacity:1;transform:translateY(0)}}
.alert-info{
    background-color:rgba(13,202,240,0.15);
    border-color:rgba(13,202,240,0.4);
    color:var(--primary);
    border-left:4px solid var(--primary);
}
/* ✅ Colonnes avec retour à la ligne automatique (au lieu de …) */

/* Nom */
.table th:nth-child(2),
.table td:nth-child(2) {
    width: 130px;
    max-width: 140px;
    white-space: normal;
    word-break: break-word;
}

/* Société */
.table th:nth-child(3),
.table td:nth-child(3) {
    width: 120px;
    max-width: 130px;
    white-space: normal;
    word-break: break-word;
}

/* Téléphone */
.table th:nth-child(4),
.table td:nth-child(4) {
    width: 150px;
    max-width: 160px;
    white-space: normal;
    word-break: break-word;
}

/* Email */
.table th:nth-child(5),
.table td:nth-child(5) {
    width: 230px;
    max-width: 240px;
    white-space: normal;
    word-break: break-word;
}

/* Adresse */
.table th:nth-child(6),
.table td:nth-child(6) {
    width: 100px;
    max-width: 110px;
    white-space: normal;
    word-break: break-word;
}

/* ✅ Lisibilité améliorée pour les labels et placeholders */
.form-label {
    color: #cfe9ff !important;
    font-weight: 500;
}

::placeholder {
    color: #b8d9f7 !important;
    opacity: 1;
}

</style>
</head>
<body class="container py-4 position-relative">
<div class="grid-pattern"></div>

<?php include 'header.php'; ?>

<h2 class="text-center fw-bold fade-in">👤 Gestion des Clients</h2>

<?php if ($msg): ?><div id="alert-msg" class="alert alert-info text-center fade-in"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- ======== Prévisualisation CSV ======== -->
<?php
if (isset($_GET['preview']) && !empty($_SESSION['csv_preview'])):
    $header = $_SESSION['csv_header'];
    $rows = $_SESSION['csv_preview'];
?>
<div class="card text-white p-3 mb-4 fade-in">
  <h5>📋 Prévisualisation de l’import CSV</h5>
  <form method="post">
    <input type="hidden" name="confirm_import" value="1">
    <div class="row g-2 mb-3">
      <?php $fields = ['nom','societe','telephone','email','adresse','remise_pct'];
      foreach ($fields as $f): ?>
        <div class="col-md-2">
          <label class="form-label"><?= ucfirst($f) ?></label>
          <select name="map[<?= $f ?>]" class="form-select bg-dark text-light border-info">
            <option value="">-- Ignorer --</option>
            <?php foreach ($header as $i => $h): ?>
              <option value="<?= $i ?>"><?= htmlspecialchars($h) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="max-height:250px;overflow:auto;">
      <table class="table table-sm table-dark table-striped align-middle">
        <thead><tr><?php foreach ($header as $h): ?><th><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody><?php foreach (array_slice($rows,0,10) as $r): ?><tr><?php foreach ($r as $v): ?><td><?= htmlspecialchars($v) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
      </table>
    </div>
    <button type="submit" class="btn btn-success mt-3">✅ Importer ces données</button>
    <a href="clients.php" class="btn btn-secondary mt-3">❌ Annuler</a>
  </form>
</div>
<?php endif; ?>

<!-- ======== Formulaire ajout client ======== -->
<form method="post" enctype="multipart/form-data" class="mb-4 p-3 rounded card fade-in">
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Société</label><input type="text" name="societe" class="form-control" placeholder="Facultatif"></div>
    <div class="col-md-2"><label class="form-label">Téléphone *</label><input type="text" name="telephone" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">Type</label><select name="type_client" class="form-select"><option>Particulier</option><option>Entreprise</option></select></div>
    <div class="col-md-12"><label class="form-label">Adresse</label><textarea name="adresse" class="form-control" rows="2"></textarea></div>
    <div class="col-md-3"><label class="form-label">Remise automatique (%)</label><select name="remise_pct" class="form-select"><?php foreach([0,5,10,15,20,25,30] as $r) echo "<option value='$r'>$r%</option>"; ?></select></div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" name="add_client" class="btn btn-green">💾 Enregistrer</button>
    <?php if ($isAdmin): ?>
      <a href="?export=1" class="btn btn-primary">📤 Exporter CSV</a>
      <label class="btn btn-warning mb-0">📥 Importer CSV <input type="file" name="csv_file" hidden onchange="this.form.submit()"><input type="hidden" name="import_clients" value="1"></label>
    <?php endif; ?>
  </div>
</form>

<?php if ($isAdmin): ?>
<form method="post" class="fade-in" style="margin-bottom: 15px;">
    <button type="submit" name="delete_duplicates" class="btn btn-red" onclick="return confirm('Supprimer les doublons clients ?');">
        🗑️ Éliminer les doublons
    </button>
</form>
<?php endif; ?>

<form method="get" class="mb-3 fade-in">
  <input type="text" name="q" class="form-control" placeholder="🔎 Rechercher un client..." value="<?= htmlspecialchars($search) ?>">
</form>

<form method="post" id="deleteForm" class="fade-in">
  <input type="hidden" name="delete_multiple" value="1">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5>📄 Liste des clients</h5>
    <?php if ($isAdmin): ?><button type="submit" class="btn btn-red btn-sm" onclick="return confirm('Supprimer les clients sélectionnés ?')">🗑 Supprimer la sélection</button><?php endif; ?>
  </div>

  <table class="table table-striped table-dark table-hover align-middle">
    <thead>
      <tr>
        <th><input type="checkbox" id="selectAll"></th>
        <th>Nom</th><th>Société</th><th>Téléphone</th><th>Email</th><th>Adresse</th><th>Type</th><th>Remise</th><th>Date</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($c = $res->fetch_assoc()): ?>
      <tr>
        <td><input type="checkbox" name="selected_ids[]" value="<?= $c['id'] ?>"></td>
        <td><?= htmlspecialchars($c['nom']) ?></td>
        <td><?= htmlspecialchars($c['societe']) ?></td>
        <td><?= htmlspecialchars($c['telephone']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= htmlspecialchars($c['adresse']) ?></td>
        <td><?= htmlspecialchars($c['type_client']) ?></td>
        <td><?= (int)$c['remise_pct'] ?>%</td>
        <td><?= htmlspecialchars($c['created_at']) ?></td>
        <td>
          <a href="fiche_client.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">👁 Voir fiche</a>
          <?php if ($isAdmin): ?><a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Supprimer ce client ?')">🗑 Suppr.</a><?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</form>

<script>
document.getElementById('selectAll')?.addEventListener('change', e=>{
  document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb=>cb.checked=e.target.checked);
});
const alertBox=document.getElementById('alert-msg');
if(alertBox){setTimeout(()=>{alertBox.style.transition="opacity .5s";alertBox.style.opacity="0";setTimeout(()=>alertBox.remove(),600)},3000);}
</script>
</body>
</html>
