
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';


session_start();

require_once __DIR__ . '/../sync_time.php';

if (!isset($_SESSION['user_id'])) { header("Location: /../login.php"); exit; }
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB"); }

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
$allowed = [10,30,50,100,200];
if (!in_array($limit, $allowed, true)) $limit = 30;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total = (int)$conn->query("SELECT COUNT(*) AS c FROM historiques WHERE statut='commande'")->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $limit));

$stmt = $conn->prepare("SELECT id, date_enregistrement, client_nom, client_tel, piece, ref_piece, fournisseur, quantite, prix_final 
                        FROM historiques 
                        WHERE statut='commande' 
                        ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>📦 Commandes en cours — R.E.Mobiles</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">


<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="assets/style.css">




</head>
<body class="page-commande">
    
    
<?php include '../header.php'; ?>

<div class="header-banner">📦 Commandes en cours — R.E.Mobiles</div>

<div class="container py-4">
  <?php if (!empty($_GET['msg'])):
    $messages = [
      'acc_ok' => '✅ Acompte ajouté.',
      'acc_del_ok' => '✅ Acompte supprimé.',
      'acc_err' => '❌ Erreur lors du traitement.',
      'updated' => '✏️ Commande mise à jour.',
      'transfert_ok' => '🔧 Commande transférée en réparation.'
    ];
    $type = str_starts_with($_GET['msg'], 'acc_err') ? 'danger' : 'success';
    if (isset($messages[$_GET['msg']])) {
      echo "<div class='alert alert-$type text-center fw-bold'>{$messages[$_GET['msg']]}</div>";
    }
  endif; ?>

  <div class="glass shadow-lg">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
      <h4 class="text-danger fw-bold mb-3 mb-md-0"><i class="bi bi-clipboard2-data"></i> Liste des commandes</h4>
      <span class="badge badge-info px-3 py-2 fw-bold rounded-pill shadow-sm">
        Page <?= $page ?>/<?= $total_pages ?> — Total : <?= $total ?> commandes
      </span>
    </div>

    <div class="table-responsive">
      <table class="table table-dark table-striped text-center align-middle">
        <thead>
          <tr>
            <?php if ($isAdmin): ?><th><input type="checkbox" id="select-all"></th><?php endif; ?>
            <th>ID</th>
            <th>Date</th>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Pièce</th>
            <th>Réf. Fournisseur</th>
            <th>Fournisseur</th>
            <th>Qté</th>
            <th>Prix TTC</th>
            <th>Acomptes</th>
            <th>Reste</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()):
          $qAcc = $conn->prepare("SELECT id, montant, mode_paiement, date_versement, user_nom FROM acomptes_commandes WHERE commande_id=? ORDER BY id ASC");
          $qAcc->bind_param("i", $row['id']);
          $qAcc->execute();
          $accRes = $qAcc->get_result();
          $totalAcc = 0; $listAcc = "";
          while ($acc = $accRes->fetch_assoc()) {
              $totalAcc += (float)$acc['montant'];
              $nomAff = htmlspecialchars($acc['user_nom'] ?: 'Inconnu');
              
              
              
              
              $listAcc .= "<div class='mb-2'>";
$listAcc .= "💰 <strong>".number_format($acc['montant'], 2, ',', ' ')." €</strong> - ".htmlspecialchars($acc['mode_paiement'])."<br>";
$listAcc .= "<span class='text-muted small'>".htmlspecialchars($acc['date_versement'])."</span><br>";
$listAcc .= "<span class='badge-user'>Par: ".htmlspecialchars($acc['user_nom'])."</span>";

// === Recherche du ticket d’acompte correspondant dans ventes_historique ===
$venteId = null;
$stmtVH = $conn->prepare("
    SELECT id 
    FROM ventes_historique 
    WHERE type='acompte' 
      AND CONVERT(ref_vente USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CONCAT('%', CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci)
    ORDER BY id DESC 
    LIMIT 1
");

$stmtVH->bind_param("s", $row['id']);
$stmtVH->execute();
$stmtVH->bind_result($venteId);
$stmtVH->fetch();
$stmtVH->close();

// === Boutons action ===
$listAcc .= "<div class='mt-1 d-flex flex-wrap gap-1'>";

if ($venteId) {
    // 🧾 Impression ticket acompte (80mm)
    $listAcc .= "<a href='/../ticket_pos.php?id=".$venteId."' 
                    target='_blank' 
                    class='btn btn-outline-light btn-sm py-0 px-2' 
                    title='Imprimer le ticket d’acompte (80mm)'>🧾</a>";

    // 📄 Transformation en facture / facture d’acompte
    $listAcc .= "<button type='button' 
                    class='btn btn-light btn-sm py-0 px-2' 
                    title='Transformer cet acompte en facture'
                    onclick='ouvrirModalFacture(".$venteId.")'>📄</button>";
} else {
    $listAcc .= "<span class='text-warning small'>⚠️ Ticket non trouvé</span>";
}

// ❌ Supprimer acompte (si admin)
if ($isAdmin) {
    $listAcc .= "<a href='/../delete_acompte_commande.php?id=".$acc['id']."' 
                    class='btn btn-sm btn-outline-danger py-0 px-2' 
                    onclick=\"return confirm('Supprimer cet acompte ?');\">❌</a>";
}

$listAcc .= "</div></div>";

              
              //$listAcc .= "<div>".number_format($acc['montant'],2)." € - ".htmlspecialchars($acc['mode_paiement'])." (".$acc['date_versement'].") <span class='badge-user'>Par: ".$nomAff."</span>";
              //if ($isAdmin) {
                  //$listAcc .= " <a href='delete_acompte_commande.php?id=".$acc['id']."' class='btn btn-sm btn-outline-danger ms-2' onclick=\"return confirm('Supprimer cet acompte ?');\">❌</a>";
              //}
             // $listAcc .= "</div>";
              
              
              
              
          }
          $qAcc->close();
          $reste = (float)$row['prix_final'] - $totalAcc;
        ?>
          <tr>
            <?php if ($isAdmin): ?><td><input type="checkbox"></td><?php endif; ?>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['date_enregistrement']) ?></td>
            <td><?= htmlspecialchars($row['client_nom']) ?></td>
            <td><?= htmlspecialchars($row['client_tel']) ?></td>
            <td><?= htmlspecialchars($row['piece']) ?></td>
            <td><?= htmlspecialchars($row['ref_piece']) ?></td>
            <td><?= htmlspecialchars($row['fournisseur']) ?></td>
            <td><?= $row['quantite'] ?></td>
            <td class="text-success fw-bold"><?= number_format($row['prix_final'],2,',',' ') ?> €</td>
            <td>
              <div class="text-start"><?= $listAcc ?></div>
              <form method="post" action="/../maj_acompte_commande.php" class="acompte-form">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <div class="input-group input-group-custom">
                  <input type="number" step="1.00" name="acompte" class="form-control acompte-input" placeholder="Montant (€)" required>
                  <select name="mode" class="form-select acompte-select" required>
                    <option disabled selected>Mode</option>
                    <option>CB</option>
                    <option>Espèce</option>
                    <option>Virement</option>
                  </select>
                  <button class="btn acompte-btn" title="Ajouter acompte">➕</button>
                </div>
              </form>
            </td>
            <td class="fw-bold text-warning"><?= number_format($reste,2,',',' ') ?> €</td>
            <td class="d-flex flex-column gap-1">
              <a href="/../ticket_commande.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-success">🧾 Ticket</a>
                <form method="post" action="/../transfer_commande_reparation.php" onsubmit="return confirm('Transférer cette commande en réparation ?');">
                  <input type="hidden" name="commande_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-secondary">🔧 Réparation</button>
                </form>
              <?php if ($isAdmin): ?>
                <a href="/../edit_commande.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">✏️ Modifier</a>
                <a href="/../annuler_commande.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Annuler cette commande ?');">↩ Annuler</a>
                <button class="btn btn-sm btn-danger" onclick="deleteCommande(<?= $row['id'] ?>, this)">🗑 Supprimer</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; $stmt->close(); ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
async function deleteCommande(id, el) {
  if (!confirm("Supprimer définitivement ?")) return;
  const formData = new FormData();
  formData.append("id", id);
  const res = await fetch("/../delete_commande.php", { method:"POST", body:formData });
  const data = await res.json();
  if (data.status === "ok") el.closest("tr").remove();
  else alert("❌ " + data.msg);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- === MODALE CHOIX FACTURE / FACTURE D'ACOMPTE === -->
<div class="modal fade" id="modalFacture" tabindex="-1" aria-labelledby="modalFactureLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-dark">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalFactureLabel">📄 Choisir le type de document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p>Souhaitez-vous générer une <strong>facture classique</strong> ou une <strong>facture d’acompte</strong> ?</p>
        <div class="d-flex justify-content-around mt-3">
          <button id="btnFactureClassique" class="btn btn-outline-success">💼 Facture</button>
          <button id="btnFactureAcompte" class="btn btn-outline-info">💰 Facture d'acompte</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentVenteId = null;
function ouvrirModalFacture(venteId) {
  currentVenteId = venteId;
  const modal = new bootstrap.Modal(document.getElementById('modalFacture'));
  modal.show();
}

document.getElementById('btnFactureClassique').addEventListener('click', () => {
  if (currentVenteId) window.open(`/../convertir_generate.php?id=${currentVenteId}&type=FACTURE`, '_blank');
  bootstrap.Modal.getInstance(document.getElementById('modalFacture')).hide();
});
document.getElementById('btnFactureAcompte').addEventListener('click', () => {
  if (currentVenteId) window.open(`/../convertir_generate.php?id=${currentVenteId}&type=FACTURE_ACOMPTE`, '_blank');
  bootstrap.Modal.getInstance(document.getElementById('modalFacture')).hide();
});
</script>


</body>
</html>