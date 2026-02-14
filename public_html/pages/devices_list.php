<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../sync_time.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /../login.php");
    exit;
}

$isAdmin  = ($_SESSION['role'] === 'admin');
$userName = $_SESSION['username'] ?? "Utilisateur";

$utils = $_SERVER['DOCUMENT_ROOT'] . '/device_utils.php';
if (!file_exists($utils)) die("Fichier manquant: " . $utils);
require_once $utils;

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);

if ($conn->connect_error) {
    die("Erreur DB : " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");

$msg = "";

/* ===============================
   AJOUT D’ACOMPTE
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_acompte'])) {

    $deviceRef = trim($_POST['device_ref']);
    $montant   = (float) $_POST['montant'];
    $mode      = trim($_POST['mode_paiement']);

    if ($montant > 0 && $deviceRef !== "") {

        $techNom = $clientNom = "";

        $stmtInfo = $conn->prepare(
            "SELECT technician_name, client_name 
             FROM devices WHERE ref=? LIMIT 1"
        );
        $stmtInfo->bind_param("s", $deviceRef);
        $stmtInfo->execute();
        $stmtInfo->bind_result($techNom, $clientNom);
        $stmtInfo->fetch();
        $stmtInfo->close();

        if (!$techNom)   $techNom   = "Non attribué";
        if (!$clientNom) $clientNom = "Client inconnu";

        $stmt = $conn->prepare(
            "INSERT INTO acomptes_devices 
            (device_ref, montant, mode_paiement, date_versement, user_nom)
            VALUES (?, ?, ?, NOW(), ?)"
        );
        $stmt->bind_param("sdss", $deviceRef, $montant, $mode, $techNom);

        if ($stmt->execute()) {

            /* ====== ✅ CORRECTION UNIQUE ICI ====== */
            $acompteId  = $stmt->insert_id;
            $refAcompte = "DEV-AC-" . $deviceRef . "-" . $acompteId;

            // Mise à jour de l’acompte avec sa vraie référence
            $upd = $conn->prepare(
                "UPDATE acomptes_devices SET ref_acompte=? WHERE id=?"
            );
            $upd->bind_param("si", $refAcompte, $acompteId);
            $upd->execute();
            $upd->close();
            /* ====== FIN CORRECTION ====== */

            $designation = "Acompte ajouté par $techNom (Commande #$deviceRef)";
            $type        = 'acompte';

            $stmtHist = $conn->prepare(
                "INSERT INTO ventes_historique
                (ref_vente, designation, prix_total, client_nom, vendeur, mode_paiement, type, date_vente)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmtHist->bind_param(
                "ssdssss",
                $refAcompte,
                $designation,
                $montant,
                $clientNom,
                $techNom,
                $mode,
                $type
            );
            $stmtHist->execute();
            $stmtHist->close();

            $msg = "✅ Acompte ajouté pour <strong>$techNom</strong>.";
        } else {
            $msg = "❌ Erreur lors de l’ajout de l’acompte.";
        }

        $stmt->close();
    } else {
        $msg = "❌ Montant invalide.";
    }
}

/* ===============================
   LISTE DES APPAREILS
   =============================== */
$result = $conn->query("SELECT * FROM devices ORDER BY created_at DESC");
if (!$result) {
    die("Erreur SQL devices: " . $conn->error);
}

/* ===============================
   RÉCUPÉRATION DES ACOMPTES
   =============================== */
function getAcomptes($conn, $ref) {
    $stmt = $conn->prepare(
        "SELECT id, montant, mode_paiement, date_versement, user_nom
         FROM acomptes_devices
         WHERE device_ref=?
         ORDER BY date_versement ASC"
    );
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>📱 Réparations — R.E.Mobiles</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/style.css">


</head>

<body class="page-reparations">

<div class="grid-pattern"></div>

<div class="container py-4 fade-in">
<?php include '../header.php'; ?>

<h2>📱 Liste des réparations</h2>

<div class="role-banner">
  👤 Connecté en tant que <strong><?= strtoupper($_SESSION['role']) ?></strong>
</div>

<?php if ($msg): ?>
  <div class="alert text-center"><?= $msg ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover align-middle text-white">

<thead>
<tr>
  <th>Réf</th>
  <th>Client</th>
  <th>Téléphone</th>
  <th>Modèle / Problème</th>
  <th>Technicien</th>
  <th>Total (€)</th>
  <th>Acomptes</th>
  <th>Restant</th>
  <th>Statut</th>
  <th>Actions</th>
</tr>
</thead>

<tbody>
<?php while ($row = $result->fetch_assoc()): 

    $total         = (float)$row['price_repair'] + (float)$row['price_diagnostic'];
    $acomptes      = getAcomptes($conn, $row['ref']);
    $totalAcomptes = array_sum(array_column($acomptes, 'montant'));
    $reste         = $total - $totalAcomptes;

    $model = trim($row['model'] ?? '');
    $desc  = trim($row['description'] ?? '');
?>

<tr>
<td><strong><?= htmlspecialchars($row['ref']) ?></strong></td>
<td><?= htmlspecialchars($row['client_name']) ?></td>
<td><?= htmlspecialchars($row['client_phone']) ?></td>

<td>
<?php
if ($model !== '' && $desc !== '') {
    echo "<strong>" . htmlspecialchars($model) . "</strong><br>";
    echo "<span class='text-muted small'>" . nl2br(htmlspecialchars($desc)) . "</span>";
}
elseif ($model !== '') {
    echo "<strong>" . htmlspecialchars($model) . "</strong>";
}
elseif ($desc !== '') {
    echo nl2br(htmlspecialchars($desc));
}
else {
    echo "<span class='text-muted'>—</span>";
}
?>
</td>

<td><?= htmlspecialchars($row['technician_name'] ?? '') ?></td>
<td><?= number_format($total, 2, ",", " ") ?> €</td>

<td class="text-center">

  <!-- Bouton VOIR -->
  <button
    class="btn btn-outline-info btn-sm w-100 mb-1"
    data-bs-toggle="modal"
    data-bs-target="#modalVoirAcomptes"
    data-ref="<?= htmlspecialchars($row['ref']) ?>"
  >
    👁 Voir
  </button>

  <!-- Bouton AJOUTER -->
  <button
    class="btn btn-success btn-sm w-100"
    data-bs-toggle="modal"
    data-bs-target="#modalAcompte"
    data-ref="<?= htmlspecialchars($row['ref']) ?>"
  >
    ➕
  </button>

</td>

<td>
<?= $reste > 0
 ? "<span class='badge bg-danger'>".number_format($reste,2,","," ")." €</span>"
 : "<span class='badge bg-success'>✅ Soldé</span>"
?>
</td>

<td><?= htmlspecialchars($row['status']) ?></td>

<td>
<a href="/../device_receipt.php?ref=<?= urlencode($row['ref']) ?>"
class="btn btn-light btn-sm">📄</a>
<a href="/../device_status.php?ref=<?= urlencode($row['ref']) ?>"
class="btn btn-outline-light btn-sm">🔄</a>

<?php if ($isAdmin): ?>
<a href="/../device_edit.php?ref=<?= urlencode($row['ref']) ?>"
class="btn btn-warning btn-sm">✏️</a>
<a href="/../device_delete.php?ref=<?= urlencode($row['ref']) ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Supprimer cet enregistrement ?');">🗑</a>
<?php endif; ?>
</td>
</tr>

<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
<script>
  const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
async function deleteAcompte(id, el) {
  if (!confirm("Supprimer cet acompte ?")) return;

  try {
    const res = await fetch("delete_acompte.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: "id=" + encodeURIComponent(id)
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data.success) {
      alert(data.message || "❌ Suppression impossible.");
      return;
    }

    const li = el.closest("li");
    if (li) li.remove();
    location.reload();

  } catch (e) {
    alert("❌ Erreur réseau.");
  }
}
</script>

<div class="modal fade" id="modalAcompte" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">➕ Ajouter un acompte</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="device_ref" id="modal_device_ref">

          <div class="mb-3">
            <label class="form-label">Montant (€)</label>
            <input type="number" step="0.01" min="0"
                   name="montant" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select">
              <option>Espèces</option>
              <option>CB</option>
              <option>Virement</option>
              <option>Chèque</option>
            </select>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
          <button type="submit" name="add_acompte" class="btn btn-success">
            Ajouter
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<script>
const modalAcompte = document.getElementById('modalAcompte');

modalAcompte.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  const ref = button.getAttribute('data-ref');
  document.getElementById('modal_device_ref').value = ref;
});
</script>

<div class="modal fade" id="modalVoirAcomptes" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">💰 Acomptes versés</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-dark" id="voirAcomptesContent">
        Chargement…
      </div>

    </div>
  </div>
</div>

<script>
const modalVoir = document.getElementById('modalVoirAcomptes');
const voirContent = document.getElementById('voirAcomptesContent');

modalVoir.addEventListener('show.bs.modal', async e => {
  const ref = e.relatedTarget.getAttribute('data-ref');
  voirContent.innerHTML = "Chargement…";

  try {
    const res = await fetch("get_acomptes.php?ref=" + encodeURIComponent(ref));

    if (!res.ok) {
      voirContent.innerHTML = "❌ Erreur serveur";
      return;
    }

    const data = await res.json();

    if (!Array.isArray(data) || !data.length) {
      voirContent.innerHTML = "<p class='text-muted'>Aucun acompte versé.</p>";
      return;
    }

    let html = `<ul class="list-group">`;

    data.forEach(a => {
  html += `
    <li class="list-group-item d-flex justify-content-between">
      <div>
        💰 <strong>${Number(a.montant).toFixed(2)} €</strong> – ${a.mode_paiement}<br>
        <small class="text-muted">
          ${new Date(a.date_versement).toLocaleString()} – 👤 ${a.user_nom}
        </small>
      </div>
      ${IS_ADMIN ? `<a href="#" class="text-danger" onclick="openDeleteChoice(${a.id}); return false;">❌</a>` : ``}
    </li>
  `;
});

    html += `</ul>`;
    voirContent.innerHTML = html;

  } catch (err) {
    console.error(err);
    voirContent.innerHTML = "❌ Erreur chargement des acomptes";
  }
});
</script>

<script>
let acompteIdToDelete = null;

/* 1️⃣ Ouvre la fenêtre */
function openDeleteChoice(id) {
  acompteIdToDelete = id;
  new bootstrap.Modal(
    document.getElementById('modalDeleteChoice')
  ).show();
}

/* 2️⃣ SUPPRESSION RÉPARATION */
function deleteAsReparation() {
  if (!acompteIdToDelete) return;

  fetch("/pages/delete_acompte.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
    },
    body: "id=" + encodeURIComponent(acompteIdToDelete)
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) {
      alert(data.message || "Erreur suppression réparation");
      return;
    }
    location.reload();
  })
  .catch(() => alert("Erreur réseau"));
}

/* 3️⃣ SUPPRESSION COMMANDE */
function deleteAsCommande() {
  if (!acompteIdToDelete) return;

  window.location.href =
    "/delete_acompte_commande.php?id=" +
    encodeURIComponent(acompteIdToDelete);
}
</script>
<div class="modal fade" id="modalDeleteChoice" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content text-center">

      <div class="modal-header">
        <h5 class="modal-title">🗑 Supprimer l’acompte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p>Choisir le type d’acompte :</p>

        <button class="btn btn-primary w-100 mb-2"
          onclick="deleteAsReparation()">
          🛠 Réparation
        </button>

        <button class="btn btn-warning w-100"
          onclick="deleteAsCommande()">
          🧾 Commande
        </button>
      </div>

    </div>
  </div>
</div>

</body>
</html>

