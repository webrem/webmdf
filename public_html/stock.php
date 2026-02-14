<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB"); }
$conn->set_charset("utf8mb4");

function as_float($v) { return (float)str_replace([',',' '], ['.',''], (string)$v); }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$msg = "";

/* === Suppression multiple === */
if ($isAdmin && isset($_POST['supprimer_selection']) && !empty($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $idList = implode(',', $ids);
    $conn->query("DELETE FROM stock_articles WHERE id IN ($idList)");
    $msg = "🗑 " . count($ids) . " article(s) supprimé(s) avec succès.";
}

/* === Arrondi global du stock === */
if ($isAdmin && isset($_POST['arrondir_stock'])) {
    $res = $conn->query("SELECT id, prix_vente FROM stock_articles");
    $count = 0;
    while ($row = $res->fetch_assoc()) {
        $pv = (float)$row['prix_vente'];
        if ($pv <= 0) continue;

        if ($pv < 5) $pv = 4.99;
        elseif ($pv < 10) $pv = 9.99;
        elseif ($pv < 15) $pv = 14.99;
        elseif ($pv < 20) $pv = 19.99;
        else $pv = ceil($pv / 5) * 5 - 0.01;

        $conn->query("UPDATE stock_articles SET prix_vente=$pv WHERE id=".(int)$row['id']);
        $count++;
    }
    $msg = "✅ $count articles arrondis automatiquement selon la règle R.E.Mobiles.";
}

/* === Modification article === */
if (isset($_POST['corriger_stock'])) {
    $id  = (int)$_POST['id'];
    $designation = trim($_POST['designation'] ?? '');
    $prix_achat  = as_float($_POST['prix_achat'] ?? 0);
    $prix_vente  = as_float($_POST['prix_vente'] ?? 0);
    $qte         = (int)($_POST['quantite'] ?? 0);

    $cout_revient = $prix_achat;

    if ($prix_vente < 5) $prix_vente = 4.99;
    elseif ($prix_vente < 15) $prix_vente = 14.99;
    elseif ($prix_vente < 20) $prix_vente = 19.99;
    else $prix_vente = ceil($prix_vente / 5) * 5 - 0.01;

    if ($isAdmin) {
        $stmt = $conn->prepare("UPDATE stock_articles 
            SET designation=?, prix_achat=?, prix_vente=?, cout_revient=?, quantite=?, updated_at=NOW() 
            WHERE id=?");
        $stmt->bind_param("sdddii", $designation, $prix_achat, $prix_vente, $cout_revient, $qte, $id);
    } else {
        $stmt = $conn->prepare("UPDATE stock_articles SET quantite=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ii", $qte, $id);
    }
    $stmt->execute();
    $msg = "✅ Article mis à jour avec arrondi automatique.";
}

/* === Récupération des stocks === */
$stock  = $conn->query("SELECT * FROM stock_articles ORDER BY designation ASC");
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>📦 Gestion du Stock — R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #111; color: #fff; font-family: "Poppins", sans-serif; }
.btn-accent { background: linear-gradient(90deg, #0d6efd, #0dcaf0); border: none; color: #fff; font-weight: 700; border-radius: 10px; }
.btn-accent:hover { transform: scale(1.05); box-shadow: 0 0 15px rgba(13,202,240,0.4); }
.table-dark th { background: linear-gradient(90deg, #0d6efd, #0dcaf0); color: #fff; }
.table-dark td { background: rgba(255,255,255,0.05); }
.table-dark tr:hover td { background: rgba(13,202,240,0.15); }
.alert-info { background: rgba(13,202,240,0.15); border: 1px solid #0dcaf0; color: #0dcaf0; text-align: center; font-weight: 600; }
.modal-content { background: #1a1a1a; color: #fff; border: 1px solid rgba(13,202,240,0.3); border-radius: 12px; }
.modal .form-control { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; }
.ean-code { font-family: monospace; font-size: 0.9rem; color: #0dcaf0; }
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container py-4">
  <h2 class="text-info text-center mb-4"><i class="bi bi-box-seam"></i> Gestion du Stock</h2>

  <?php if ($msg): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>

  <div class="bg-dark p-4 rounded shadow mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
      <h4 class="fw-bold text-info mb-2"><i class="bi bi-clipboard-data"></i> Stock actuel</h4>
      <div class="d-flex gap-2 flex-wrap">
        <?php if ($isAdmin): ?>
          <a href="stock_add.php" class="btn btn-accent"><i class="bi bi-plus-circle"></i> Ajouter</a>
          <a href="stock_import.php" class="btn btn-outline-info"><i class="bi bi-upload"></i> Import CSV</a>
          <form method="post" class="d-inline">
            <button type="submit" name="arrondir_stock" class="btn btn-outline-warning" onclick="return confirm('Confirmer l’arrondi automatique de tout le stock ?')">
              🧮 Arrondir tout le stock
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <form method="post">
      <?php if ($isAdmin): ?>
      <div class="mb-3 d-flex gap-2">
        <button type="button" class="btn btn-outline-light" id="selectAll">✅ Tout sélectionner</button>
        <button type="submit" name="supprimer_selection" class="btn btn-danger" onclick="return confirm('Supprimer les articles sélectionnés ?')">🗑 Supprimer la sélection</button>
      </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-dark table-striped align-middle text-center">
          <thead>
            <tr>
              <?php if ($isAdmin): ?><th>✔</th><?php endif; ?>
              <th>Réf</th>
              <th>EAN</th>
              <th>Désignation</th>
              <th>Fournisseur</th>
              <th>Prix Achat</th>
              <th>Prix Vente</th>
              <th>Qté</th>
              <th>Maj</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($row = $stock->fetch_assoc()): ?>
            <tr>
              <?php if ($isAdmin): ?>
                <td><input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" class="form-check-input"></td>
              <?php endif; ?>
              <td><?= h($row['reference']) ?></td>
              <td class="ean-code"><?= h($row['ean']) ?></td>
              <td class="text-start"><?= h($row['designation']) ?></td>
              <td><?= h($row['fournisseur']) ?></td>
              <td><?= number_format((float)$row['prix_achat'], 2, ',', ' ') ?> €</td>
              <td class="text-success fw-bold"><?= number_format((float)$row['prix_vente'], 2, ',', ' ') ?> €</td>
              <td><?= (int)$row['quantite'] ?></td>
              <td><?= h($row['updated_at'] ?? '') ?></td>
              <td>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id'] ?>">✏️</button>
              </td>
            </tr>

            <!-- MODALE ÉDITION -->
            <div class="modal fade" id="edit<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header">
                      <h5 class="modal-title text-info"><i class="bi bi-pencil-square"></i> Modifier l’article</h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <label>Désignation</label>
                      <input type="text" name="designation" value="<?= h($row['designation']) ?>" class="form-control mb-2" required>
                      <label>Prix d’achat (€)</label>
                      <input type="number" step="0.01" name="prix_achat" value="<?= (float)$row['prix_achat'] ?>" class="form-control mb-2">
                      <label>Prix de vente (€)</label>
                      <div class="input-group mb-2">
                        <input type="number" step="0.01" name="prix_vente" id="pv<?= $row['id'] ?>" value="<?= (float)$row['prix_vente'] ?>" class="form-control">
                        <button type="button" class="btn btn-outline-info" onclick="recalcPrix(<?= $row['id'] ?>)">🔁</button>
                      </div>
                      <label>Quantité</label>
                      <input type="number" name="quantite" value="<?= (int)$row['quantite'] ?>" class="form-control">
                    </div>
                    <div class="modal-footer">
                      <button type="submit" name="corriger_stock" class="btn btn-accent fw-bold">💾 Enregistrer</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('click', () => {
  document.querySelectorAll('input[name="ids[]"]').forEach(chk => chk.checked = true);
});
function recalcPrix(id) {
  const input = document.getElementById('pv' + id);
  let val = parseFloat(input.value) || 0;
  if (val < 5) val = 4.99;
  else if (val < 10) val = 9.99;
  else if (val < 15) val = 14.99;
  else if (val < 20) val = 19.99;
  else val = Math.ceil(val / 5) * 5 - 0.01;
  input.value = val.toFixed(2);
  input.classList.add('text-success','fw-bold');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
