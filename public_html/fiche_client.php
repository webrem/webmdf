<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

/* === CONNEXIONS === */
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur connexion principale");
$conn->set_charset("utf8mb4");

$conn_rem = @new mysqli("localhost", "u498346438_remshop", "Remshop1", "u498346438_remshop");
if ($conn_rem->connect_error) { $conn_rem = null; }

/* === CLIENT === */
$client_id = (int)($_GET['id'] ?? 0);
if ($client_id <= 0) die("Client introuvable.");

$client = $conn->query("SELECT * FROM clients WHERE id=$client_id")->fetch_assoc();
if (!$client) die("Client non trouvé.");

$nom = $conn->real_escape_string($client['nom']);
$devices = $conn->query("SELECT ref, model, problem, status, created_at FROM devices WHERE client_name='$nom' ORDER BY id DESC");
$ventes  = $conn->query("SELECT ref_vente, prix_total, mode_paiement, date_vente FROM ventes WHERE client_nom='$nom' ORDER BY id DESC");

/* === Devis / Factures (sécurisé) === */
$devis = false;
if ($conn_rem) {
    try {
        $check = $conn_rem->query("SHOW TABLES LIKE 'documents'");
        if ($check && $check->num_rows > 0) {
            // Vérif des colonnes disponibles
            $cols = [];
            $res = $conn_rem->query("SHOW COLUMNS FROM documents");
            while($r = $res->fetch_assoc()) $cols[] = $r['Field'];

            $refCol = in_array('ref', $cols) ? 'ref' : (in_array('reference', $cols) ? 'reference' : 'id');
            $typeCol = in_array('type_doc', $cols) ? 'type_doc' : (in_array('type', $cols) ? 'type' : 'NULL AS type_doc');
            $montantCol = in_array('montant_total', $cols) ? 'montant_total' : (in_array('total', $cols) ? 'total' : '0 AS montant_total');
            $dateCol = in_array('created_at', $cols) ? 'created_at' : (in_array('date_creation', $cols) ? 'date_creation' : 'NOW() AS created_at');

            $nom_safe = $conn_rem->real_escape_string($nom);
            $query = "SELECT $refCol AS ref, $typeCol AS type_doc, $montantCol AS montant_total, $dateCol AS created_at FROM documents WHERE client_nom='$nom_safe' ORDER BY id DESC";
            $devis = $conn_rem->query($query);
        }
    } catch (Exception $e) {
        $devis = false;
    }
}
/* === MODIFICATION CLIENT === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_client'])) {
    $nom       = trim($_POST['nom']);
    $societe   = trim($_POST['societe']);
    $telephone = trim($_POST['telephone']);
    $email     = trim($_POST['email']);
    $adresse   = trim($_POST['adresse']);
    $type      = trim($_POST['type_client']);
    $remise    = (int)($_POST['remise_pct'] ?? 0);

    if ($nom && $telephone) {
        $stmt = $conn->prepare("UPDATE clients SET nom=?, societe=?, telephone=?, email=?, adresse=?, type_client=?, remise_pct=? WHERE id=?");
        $stmt->bind_param("ssssssii", $nom, $societe, $telephone, $email, $adresse, $type, $remise, $client_id);
        if ($stmt->execute()) {
            $msg = "✅ Informations du client mises à jour avec succès.";
            // On recharge les infos actualisées
            $client = $conn->query("SELECT * FROM clients WHERE id=$client_id")->fetch_assoc();
        } else {
            $msg = "❌ Erreur lors de la mise à jour : " . $conn->error;
        }
    } else {
        $msg = "⚠️ Nom et téléphone sont obligatoires.";
    }
}

?>


<!DOCTYPE html>


<html lang="fr">
<head>
<meta charset="utf-8">
<title>Fiche Client - <?= htmlspecialchars($client['nom']) ?> | R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>


body {
  background: radial-gradient(circle at top left, #080808, #111, #0d0d0d);
  color: #ffffff; /* texte global bien blanc */
  font-family: "Poppins", sans-serif;
  font-weight: 500;
}

h2, h4 {
  color: #0dcaf0;
  font-weight: 800;
  text-transform: uppercase;
}

.card {
  background: rgba(15, 15, 15, 0.95);
  border: 1px solid rgba(13, 202, 240, 0.35);
  border-radius: 14px;
  box-shadow: 0 0 25px rgba(13, 202, 240, 0.15);
  color: #fff;
  font-weight: 600;
}

/* texte à l'intérieur des cartes et colonnes */
.card strong,
.card div,
.card p,
.card td {
  color: #ffffff !important;
  font-weight: 600;
}

/* tableau */
.table {
  color: #fff;
  background: rgba(0,0,0,0.4);
  border-collapse: separate;
  border-spacing: 0 3px;
}

.table th {
  color: #0dcaf0;
  font-weight: 700;
  text-transform: uppercase;
  background-color: rgba(13, 202, 240, 0.1);
}

.table td {
  color: #fdfdfd;
  font-weight: 600;
}

/* badges (statuts) */
.badge {
  font-weight: 700;
  font-size: 0.9rem;
  padding: 5px 8px;
}

/* Titres de sections */
h4 {
  border-bottom: 1px solid rgba(13,202,240,0.3);
  padding-bottom: 4px;
  margin-bottom: 12px;
}

/* Liens et boutons */
a, a:visited {
  color: #0dcaf0;
  text-decoration: none;
  font-weight: 600;
}

a:hover {
  color: #0d6efd;
  text-shadow: 0 0 5px rgba(13,202,240,0.4);
}




.nav-tabs .nav-link { color: #0dcaf0; }
.nav-tabs .nav-link.active { background-color: #0dcaf0; color: #000; font-weight: 600; }
.btn-return { background: linear-gradient(90deg,#0d6efd,#0dcaf0); color: #fff; border:none; border-radius:8px; font-weight:600; }
.btn-return:hover { transform:scale(1.03); box-shadow:0 0 15px rgba(13,202,240,0.5); }
.btn-edit { background: linear-gradient(90deg,#20c997,#198754); border:none; color:white; font-weight:600; border-radius:8px; }
.btn-edit:hover { box-shadow:0 0 15px rgba(32,201,151,0.4); }
.btn-repair { background: linear-gradient(90deg,#6610f2,#6f42c1); color:white; border:none; border-radius:8px; }
.btn-repair:hover { box-shadow:0 0 10px rgba(111,66,193,0.5); }
</style>
</head>
<body class="container py-4">

<?php include 'header.php'; ?>

<?php if (!empty($msg)): ?>
  <div id="alert-msg" class="alert alert-info text-center my-2"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>


  <h2>👤 Fiche Client — <?= htmlspecialchars($client['nom']) ?></h2>
  <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editClientModal">✏️ Modifier le client</button>
</div>

<ul class="nav nav-tabs mb-4" id="clientTabs">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#infos">📋 Infos</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#devices">📱 Réparations</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#devis">🧾 Devis & Factures</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ventes">🛒 Ventes</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#historique">📜 Historique complet</button></li>
</ul>

<div class="tab-content">
  <!-- Infos -->
  <div class="tab-pane fade show active" id="infos">
    <div class="card p-4 mb-4">
      <h4>Informations principales</h4>
      <div class="row g-3 mt-2">
        <div class="col-md-4"><strong>Nom :</strong> <?= htmlspecialchars($client['nom']) ?></div>
        <div class="col-md-4"><strong>Société :</strong> <?= htmlspecialchars($client['societe'] ?: '-') ?></div>
        <div class="col-md-4"><strong>Type :</strong> <?= htmlspecialchars($client['type_client']) ?></div>
        <div class="col-md-4"><strong>Téléphone :</strong> <span class="text-info"><?= htmlspecialchars($client['telephone']) ?></span></div>
        <div class="col-md-4"><strong>Email :</strong> <?= htmlspecialchars($client['email']) ?></div>
        <div class="col-md-4"><strong>Remise :</strong> <?= (int)$client['remise_pct'] ?>%</div>
        <div class="col-md-12"><strong>Adresse :</strong><br><?= nl2br(htmlspecialchars($client['adresse'])) ?></div>
        <div class="col-md-12 text-end mt-3">
          <a href="clients.php" class="btn btn-return me-2">⬅ Retour</a>
          <a href="device_register.php?client=<?= urlencode($client['nom']) ?>" class="btn btn-success">➕ Nouvelle réparation</a>
          <a href="index.php?client=<?= urlencode($client['nom']) ?>" class="btn btn-warning">🧾 Nouveau devis</a>
        </div>
      </div>
    </div>
  </div>

<!-- Devices -->
<div class="tab-pane fade" id="devices">
  <div class="card p-4 mb-4">
    <h4>📱 Appareils enregistrés</h4>

    <style>
      /* Styles responsives pour les boutons d’action */
      @media (max-width: 768px) {
        .btn-action-group {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }
      }
      @media (min-width: 769px) {
        .btn-action-group {
          display: inline-flex;
          flex-direction: row;
          gap: 6px;
        }
      }
    </style>

    <?php if ($devices->num_rows): ?>
      <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mt-3">
          <thead>
            <tr>
              <th>Réf</th>
              <th>Modèle</th>
              <th>Problème</th>
              <th>Statut</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while($d=$devices->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($d['ref']) ?></td>
                <td><?= htmlspecialchars($d['model']) ?></td>
                <td><?= htmlspecialchars($d['problem']) ?></td>
                <td><span class="badge bg-info"><?= htmlspecialchars($d['status']) ?></span></td>
                <td><?= htmlspecialchars($d['created_at']) ?></td>
                <td>
                  <div class="btn-action-group">
                    <!-- Ticket PDF atelier -->
                    <a href="device_receipt.php?ref=<?= urlencode($d['ref']) ?>"
                       class="btn btn-sm btn-outline-light">
                      🧾 Voir
                    </a>

                    <!-- Suivi public client -->
                    <a href="https://r-e-mobiles.com/device_status.php?ref=<?= urlencode($d['ref']) ?>"
                       class="btn btn-sm btn-repair"
                       target="_blank">
                      🔧 Réparation
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-muted">Aucun appareil enregistré.</p>
    <?php endif; ?>
  </div>
</div>


  <!-- Devis -->
  <div class="tab-pane fade" id="devis">
    <div class="card p-4 mb-4">
      <h4>🧾 Devis & Factures</h4>
      <?php if ($devis && $devis->num_rows): ?>
      <table class="table table-dark table-striped mt-3">
        <thead><tr><th>Réf</th><th>Type</th><th>Montant (€)</th><th>Date</th></tr></thead>
        <tbody>
          <?php while($dv=$devis->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($dv['ref']) ?></td>
              <td><?= htmlspecialchars($dv['type_doc']) ?></td>
              <td class="text-success fw-bold"><?= number_format($dv['montant_total'],2,',',' ') ?></td>
              <td><?= htmlspecialchars($dv['created_at']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?><p class="text-muted">Aucun devis trouvé.</p><?php endif; ?>
    </div>
  </div>

<!-- Ventes -->
<div class="tab-pane fade" id="ventes">
  <div class="card p-4 mb-4">
    <h4>🛒 Commandes & Ventes</h4>

    <style>
      @media (max-width: 768px) {
        .btn-action-group {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }
      }
      @media (min-width: 769px) {
        .btn-action-group {
          display: inline-flex;
          flex-direction: row;
          gap: 6px;
        }
      }
    </style>

    <?php if ($ventes->num_rows): ?>
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle mt-3">
        <thead>
          <tr>
            <th>Réf</th>
            <th>Total (€)</th>
            <th>Paiement</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($v = $ventes->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($v['ref_vente']) ?></td>
              <td class="text-warning fw-bold"><?= number_format($v['prix_total'], 2, ',', ' ') ?></td>
              <td><?= htmlspecialchars($v['mode_paiement']) ?></td>
              <td><?= htmlspecialchars($v['date_vente']) ?></td>
              <td>
                <div class="btn-action-group">
                  <!-- Voir le ticket de vente -->
                  <a href="ticket_pos.php?ref=<?= urlencode($v['ref_vente']) ?>" 
                     class="btn btn-sm btn-outline-light" 
                     target="_blank">
                    🧾 Ticket
                  </a>

                  <!-- WhatsApp partage -->
                  <?php 
                    $lien_ticket = "https://r-e-mobiles.com/ticket_pos.php?ref=" . urlencode($v['ref_vente']);
                    $msg_ticket = rawurlencode("Bonjour, voici le reçu de votre achat : $lien_ticket");
                    $tel = preg_replace('/\D+/', '', $client['telephone']);
                  ?>
                  <a href="https://wa.me/<?= $tel ?>?text=<?= $msg_ticket ?>" 
                     class="btn btn-sm btn-success" target="_blank">
                    💬 WhatsApp
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-muted">Aucune commande trouvée.</p>
    <?php endif; ?>
  </div>
</div>


  <!-- Historique -->
  <div class="tab-pane fade" id="historique">
    <div class="card p-4 mb-4">
      <h4>📜 Historique complet</h4>
      <p class="text-muted">Résumé combiné de toutes les interactions du client.</p>
      <ul class="list-group">
        <?php
        $history = [];
        // Devices
        $res = $conn->query("SELECT 'Réparation' AS type, ref AS ref, model AS details, created_at FROM devices WHERE client_name='$nom'");
        while($r=$res->fetch_assoc()) $history[]=$r;
        // Ventes
        $res = $conn->query("SELECT 'Vente' AS type, ref_vente AS ref, CONCAT(prix_total,'€ - ',mode_paiement) AS details, date_vente AS created_at FROM ventes WHERE client_nom='$nom'");
        while($r=$res->fetch_assoc()) $history[]=$r;
        // Devis
        if ($devis && $devis->num_rows>0){
          $res = $conn_rem->query("SELECT 'Document' AS type, $refCol AS ref, CONCAT($typeCol,' - ',ROUND($montantCol,2),'€') AS details, $dateCol AS created_at FROM documents WHERE client_nom='$nom'");
          if($res) while($r=$res->fetch_assoc()) $history[]=$r;
        }
        usort($history, fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));
        foreach($history as $h){
          echo "<li class='list-group-item bg-dark text-light mb-1'><strong>{$h['type']} :</strong> {$h['ref']} — {$h['details']} <span class='float-end text-secondary'>{$h['created_at']}</span></li>";
        }
        ?>
      </ul>
    </div>
  </div>
</div>

<!-- MODALE DE MODIFICATION CLIENT -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-info">
      <form method="POST">
        <div class="modal-header border-info">
          <h5 class="modal-title" id="editClientLabel">✏️ Modifier le client</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nom *</label>
              <input type="text" name="nom" class="form-control bg-dark text-white border-info" 
                     value="<?= htmlspecialchars($client['nom']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Société</label>
              <input type="text" name="societe" class="form-control bg-dark text-white border-info"
                     value="<?= htmlspecialchars($client['societe']) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Téléphone *</label>
              <input type="text" name="telephone" class="form-control bg-dark text-white border-info"
                     value="<?= htmlspecialchars($client['telephone']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control bg-dark text-white border-info"
                     value="<?= htmlspecialchars($client['email']) ?>">
            </div>

            <div class="col-md-12">
              <label class="form-label">Adresse</label>
              <textarea name="adresse" class="form-control bg-dark text-white border-info"
                        rows="2"><?= htmlspecialchars($client['adresse']) ?></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Type</label>
              <select name="type_client" class="form-select bg-dark text-white border-info">
                <option value="Particulier" <?= $client['type_client']=='Particulier'?'selected':'' ?>>Particulier</option>
                <option value="Entreprise" <?= $client['type_client']=='Entreprise'?'selected':'' ?>>Entreprise</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Remise automatique (%)</label>
              <select name="remise_pct" class="form-select bg-dark text-white border-info">
                <?php foreach([0,5,10,15,20,25,30] as $r): ?>
                  <option value="<?= $r ?>" <?= ((int)$client['remise_pct']==$r)?'selected':'' ?>><?= $r ?>%</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-info">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Annuler</button>
          <button type="submit" name="update_client" class="btn btn-info fw-bold">💾 Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const alertBox = document.getElementById('alert-msg');
  if (alertBox) {
    setTimeout(() => {
      alertBox.style.transition = "opacity 0.5s ease";
      alertBox.style.opacity = "0";
      setTimeout(() => alertBox.remove(), 600);
    }, 3000); // 3 secondes avant disparition
  }
});
</script>


</body>
</html>
