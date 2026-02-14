<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 
session_start();

require_once __DIR__ . '/../sync_time.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit;
}
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$isUser  = ($_SESSION['role'] ?? '') === 'user';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur de connexion : " . $conn->connect_error); }

// ---- Recherche client (autocomplétion)
$clientSearch = trim($_GET['client'] ?? '');
$filterClient = $clientSearch ? "AND client_nom LIKE '%" . $conn->real_escape_string($clientSearch) . "%'" : "";

// ---- Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
$allowed = [10,30,50,100,200];
if (!in_array($limit, $allowed, true)) $limit = 30;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ---- Suppression multiple (admin)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected']) && !empty($_POST['selected_ids'])) {
    foreach ($_POST['selected_ids'] as $id) {
        $id = (int)$id;
        $conn->query("DELETE FROM historiques WHERE id=$id");
    }
    $conn->query("ALTER TABLE historiques AUTO_INCREMENT=1");
    $success = "🗑 " . count($_POST['selected_ids']) . " devis supprimé(s).";
}

// ---- Filtre statut
$filterWhere = "(statut IS NULL OR statut='' OR statut='ticket' OR statut='devis') $filterClient";

// ---- Total
$qCount = $conn->query("SELECT COUNT(*) AS c FROM historiques WHERE $filterWhere");
$total = (int)($qCount ? $qCount->fetch_assoc()['c'] : 0);
$total_pages = max(1, ceil($total / $limit));

// ---- Liste paginée
$stmt = $conn->prepare("
SELECT id, date_enregistrement, client_nom, client_tel, piece, ref_piece, fournisseur, quantite, prix_final, IFNULL(statut,'devis') AS statut
FROM historiques
WHERE $filterWhere
ORDER BY id DESC
LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>📜 Historique des Devis — R.E.Mobiles</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="assets/style.css">

</head>

<body class="page-admin">
<body>
    
<div class="grid-pattern"></div>

<?php include '../header.php'; ?>

<div class="header-banner">
  📜 Historique des Devis — R.E.Mobiles
</div>

<div class="container py-4 fade-in">
  <?php if (!empty($success)): ?>
    <div class="alert alert-success text-center fw-bold"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="glass p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2 position-relative">
        <form method="get" class="d-flex gap-2 mb-0">
          <input type="text" id="clientSearch" name="client" placeholder="🔍 Rechercher client..." value="<?= htmlspecialchars($clientSearch) ?>" autocomplete="off">
          <div id="clientSuggestions" class="list-group"></div>
          <button type="submit" class="btn btn-accent btn-sm">Filtrer</button>
        </form>
      </div>
      <div>
        <span class="badge bg-danger px-3 py-2 fw-bold rounded-pill shadow-sm">
          Page <?= $page ?>/<?= $total_pages ?> — Total : <?= $total ?> devis
        </span>
      </div>
    </div>

    <form method="post">
      <div class="d-flex justify-content-start gap-2 mb-3 flex-wrap">
        <button type="button" id="selectAll" class="btn btn-outline-accent btn-sm">✅ Tout sélectionner</button>
        <?php if ($isAdmin): ?>
        <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer les devis sélectionnés ?')">🗑 Supprimer</button>
        <?php endif; ?>
      </div>

      <div class="table-responsive">
        <table class="table table-dark table-striped align-middle text-center">
          <thead>
            <tr>
              <?php if ($isAdmin): ?><th><input type="checkbox" id="checkAll"></th><?php endif; ?>
              <th>ID</th>
              <th>Statut</th>
              <th>Date</th>
              <th>Client</th>
              <th>Téléphone</th>
              <th>Pièce</th>
              <th>Réf. Fournisseur</th>
              <th>Fournisseur</th>
              <th>Qté</th>
              <th>Prix TTC</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <?php if ($isAdmin): ?>
                <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>" class="form-check-input"></td>
                <?php endif; ?>
                <td><?= (int)$row['id'] ?></td>
                <td><span class="badge bg-danger text-light"><?= htmlspecialchars($row['statut']) ?></span></td>
                <td><?= htmlspecialchars($row['date_enregistrement']) ?></td>
                <td><?= htmlspecialchars($row['client_nom']) ?></td>
                <td><?= htmlspecialchars($row['client_tel']) ?></td>
                <td><?= htmlspecialchars($row['piece']) ?></td>
                <td><?= htmlspecialchars($row['ref_piece']) ?></td>
                <td><?= htmlspecialchars($row['fournisseur']) ?></td>
                <td><?= (int)$row['quantite'] ?></td>
                <td class="text-success fw-bold"><?= number_format((float)$row['prix_final'],2,',',' ') ?> €</td>
                <td>
                  <a href="/../telecharger_ticket.php?id=<?= (int)$row['id'] ?>" target="_blank" class="btn btn-sm btn-warning">📄</a>
                  <button type="button" class="btn btn-sm btn-primary" title="Transformer en document A4" onclick="openConvertModal(<?= (int)$row['id'] ?>)">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                  </button>
                  <a href="/../modifier.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-info">✏️</a>
                  <a href="/../valider_commande.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-success">✅</a>
                  <?php if ($isAdmin): ?>
                  <a href="/../supprimer.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce devis ?')">🗑</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; $stmt->close(); ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>

<!-- JS & Modal -->
<script>
document.getElementById('selectAll')?.addEventListener('click',()=> {
  document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb=>cb.checked=true);
});
document.getElementById('checkAll')?.addEventListener('change',(e)=>{
  document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb=>cb.checked=e.target.checked);
});

// Autocomplétion client
const cInput=document.getElementById('clientSearch');
const cBox=document.getElementById('clientSuggestions');
cInput.addEventListener('input',()=>{
  const q=cInput.value.trim();
  cBox.innerHTML='';
  if(q.length<2)return;
  fetch('/../clients_autocomplete.php?q='+encodeURIComponent(q))
  .then(r=>r.json()).then(data=>{
    cBox.innerHTML='';
    if(!data.length){cBox.innerHTML='<div class="list-group-item">Aucun client trouvé</div>';return;}
    data.forEach(c=>{
      const div=document.createElement('div');
      div.className='list-group-item';
      div.textContent=c.nom+' ('+(c.telephone||'')+')';
      div.onclick=()=>{
        cInput.value=c.nom;
        cBox.innerHTML='';
      };
      cBox.appendChild(div);
    });
  });
});
document.addEventListener('click',e=>{
  if(!e.target.closest('#clientSearch,#clientSuggestions'))cBox.innerHTML='';
});

// Modal transformation
let selectedTicketId=null;
function openConvertModal(id){selectedTicketId=id;new bootstrap.Modal(document.getElementById('convertModal')).show();}
function launchConvert(type){
  if(!selectedTicketId)return;
  const url=`/../convertir_generate.php?id=${selectedTicketId}&type=${type}`;
  window.open(url,'_blank');
  bootstrap.Modal.getInstance(document.getElementById('convertModal')).hide();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- MODALE DE TRANSFORMATION -->
<div class="modal fade" id="convertModal" tabindex="-1" aria-labelledby="convertLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center" style="background:rgba(15,15,15,0.95);border:1px solid #dc3545;color:#fff;">
      <div class="modal-header border-0">
        <h5 class="modal-title w-100 text-danger fw-bold" id="convertLabel">Transformer le ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-4">Choisis le type de document à générer :</p>
        <div class="d-grid gap-2">
          <button class="btn btn-accent" onclick="launchConvert('FACTURE')"><i class="bi bi-file-earmark-text"></i> FACTURE</button>
          <button class="btn btn-accent" onclick="launchConvert('DEVIS')"><i class="bi bi-file-earmark-richtext"></i> DEVIS</button>
          <button class="btn btn-accent" onclick="launchConvert('PROFORMA')"><i class="bi bi-file-earmark-break"></i> PROFORMA</button>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">❌ Fermer</button>
      </div>
    </div>
  </div>
</div>
</body>
</html>
