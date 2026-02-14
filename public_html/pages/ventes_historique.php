<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header("Location: /../login.php"); exit; }
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");

/* ✅ AJOUT (robustesse) : vérifier la connexion immédiatement (sans supprimer votre vérif plus bas) */
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);

$conn->set_charset('utf8mb4');

$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire



$msg = "";

/* === SUPPRESSION D'UN TICKET (ADMIN) === */
if ($isAdmin && isset($_GET['delete_ref'])) {
    $ref = $_GET['delete_ref'];

    // --- Supprimer la vente si elle existe
    $stmt = $conn->prepare("DELETE FROM ventes WHERE ref_vente = ?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $stmt->close();

    // --- Supprimer si acompte dans ventes_historique
    $stmt2 = $conn->prepare("DELETE FROM ventes_historique WHERE ref_vente = ? AND LOWER(type)='acompte'");
    $stmt2->bind_param("s", $ref);
    $stmt2->execute();
    $stmt2->close();

    // --- Et si c’est un acompte, supprimer aussi dans acomptes_devices
if (strpos($ref, "DEV-AC-") === 0) {

    // DEV-AC-REM0083-175
    if (preg_match('/^DEV-AC-([A-Z0-9]+)-([0-9]+)$/', $ref, $m)) {

        $deviceRef = $m[1]; // REM0083
        $acompteId = (int)$m[2]; // 175

        $stmt3 = $conn->prepare(
            "DELETE FROM acomptes_devices 
             WHERE id = ? AND device_ref = ?"
        );
        $stmt3->bind_param("is", $acompteId, $deviceRef);
        $stmt3->execute();
        $stmt3->close();
    }
}

    $msg = "🗑️ Ticket $ref supprimé sur toutes les tables.";
}


/* === LISTE DES VENDEURS === */
$vendeurs = [];
$resUsers = $conn->query("SELECT username FROM users ORDER BY username ASC");
while ($u = $resUsers->fetch_assoc()) $vendeurs[] = $u['username'];


/* === FILTRE PAR DÉFAUT : AUJOURD’HUI === */
if (empty($_GET['date_debut']) && empty($_GET['date_fin'])) {
    $_GET['date_debut'] = date('Y-m-d');
    $_GET['date_fin']   = date('Y-m-d');
}


/* === FILTRES === */
$whereVentes = "1";
$whereAcomptes = "LOWER(type) IN ('acompte','retrait')";
$params = [];
$types = '';

if (!empty($_GET['vendeur'])) {
    $whereVentes .= " AND v.vendeur = ?";
    $whereAcomptes .= " AND vh.vendeur = ?";
    $params[] = $_GET['vendeur'];
    $types .= 's';
}
if (!empty($_GET['date_debut']) && !empty($_GET['date_fin'])) {
    $whereVentes .= " AND v.date_vente BETWEEN ? AND ?";
    $whereAcomptes .= " AND vh.date_vente BETWEEN ? AND ?";
    $params[] = $_GET['date_debut'] . " 00:00:00";
    $params[] = $_GET['date_fin'] . " 23:59:59";
    $types .= 'ss';
}

/* ✅ AJOUT (fix UNION placeholders) :
   Les filtres sont utilisés dans les 2 SELECT (ventes + acomptes), donc il faut binder les params 2 fois */
if (!empty($params)) {
    $params = array_merge($params, $params);
    $types  = $types . $types;
}

/* === UNION VENTES + ACOMPTES === */
$sql = "
(SELECT 
    CAST(MIN(v.date_vente) AS CHAR CHARACTER SET utf8mb4) AS date_vente,
    CAST(v.ref_vente AS CHAR CHARACTER SET utf8mb4) AS ref_vente,
    CAST(MIN(v.designation) AS CHAR CHARACTER SET utf8mb4) AS designation,
    SUM(v.quantite) AS quantite,
    SUM(v.prix_total) AS prix_total,
    CAST(MIN(v.client_nom) AS CHAR CHARACTER SET utf8mb4) AS client_nom,
    CAST(MIN(v.vendeur) AS CHAR CHARACTER SET utf8mb4) AS vendeur,
    CAST(MIN(v.mode_paiement) AS CHAR CHARACTER SET utf8mb4) AS mode_paiement,
    'Vente' AS type
 FROM ventes v
 WHERE $whereVentes
 GROUP BY v.ref_vente
)


UNION ALL

(SELECT 
    CAST(vh.date_vente AS CHAR CHARACTER SET utf8mb4) AS date_vente,
    CAST(vh.ref_vente AS CHAR CHARACTER SET utf8mb4) AS ref_vente,
    CAST(vh.designation AS CHAR CHARACTER SET utf8mb4) AS designation,
    1 AS quantite,
    vh.prix_total,
    CAST(vh.client_nom AS CHAR CHARACTER SET utf8mb4) AS client_nom,
    CAST(vh.vendeur AS CHAR CHARACTER SET utf8mb4) AS vendeur,
    CAST(vh.mode_paiement AS CHAR CHARACTER SET utf8mb4) AS mode_paiement,
    CASE
  WHEN LOWER(vh.type) = 'retrait' THEN 'Retrait'
  ELSE 'Acompte'
END AS type
 FROM ventes_historique vh
 WHERE $whereAcomptes COLLATE utf8mb4_general_ci)

ORDER BY date_vente DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$ventes = [];
$totalVentes   = 0;
$totalAcomptes = 0;
$totalRetraits = 0;
$totalGlobal   = 0;

while ($v = $res->fetch_assoc()) {
    $ventes[] = $v;

    $type = strtolower($v['type']);
    $montant = (float)$v['prix_total'];

    if ($type === 'acompte') {
        $totalAcomptes += $montant;
    } elseif ($type === 'retrait') {
        $totalRetraits += $montant; // négatif
    } else {
        $totalVentes += $montant;
    }
}

$totalGlobal = $totalVentes + $totalAcomptes + $totalRetraits;

/* === EXPORT CSV === */
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventes_acomptes_export.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Réf Vente', 'Type', 'Produit', 'Qté', 'Total (€)', 'Client', 'Vendeur', 'Paiement'], ';');
    foreach ($ventes as $v) {
        fputcsv($output, [
            $v['date_vente'],
            $v['ref_vente'],
            $v['type'],
            $v['designation'],
            $v['quantite'],
            number_format($v['prix_total'], 2, ',', ' '),
            $v['client_nom'],
            $v['vendeur'],
            $v['mode_paiement']
        ], ';');
    }
    fclose($output);
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Historique des ventes - R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
body {
  background: radial-gradient(circle at top left, #0a0a0a, #1a1a1a);
  color: #fff;
  font-family: "Poppins", sans-serif;
  overflow-x: hidden;
  min-height: 100vh;
}
h2 {
  color: #0dcaf0;
  font-weight: 700;
  text-transform: uppercase;
  text-align: center;
  margin-bottom: 2rem;
}
.main-card {
  background: rgba(20,20,20,0.9);
  border-radius: 20px;
  padding: 25px;
  box-shadow: 0 0 25px rgba(13,202,240,0.2);
}
.table { color: #fff; border-radius: 10px; overflow: hidden; }
.table thead {
  background: linear-gradient(90deg, #0d6efd, #0dcaf0);
  color: #fff;
}
.table tbody tr:hover {
  background: rgba(13,202,240,0.15);
  transform: scale(1.01);
}
.table tbody tr.acompte-row {
  background-color: rgba(0, 255, 100, 0.15) !important;
}
tfoot th {
  background: #111;
  color: #0dcaf0;
}
.btn-glow {
  border: none;
  color: #fff;
  font-weight: 600;
  border-radius: 12px;
  transition: all 0.25s ease;
}
.btn-glow:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 0 20px rgba(13,202,240,0.4);
}
.card.bg-secondary {
  background: rgba(40,40,40,0.8)!important;
  border: 1px solid #0dcaf0;
  box-shadow: 0 0 10px rgba(13,202,240,0.3);
}
.alert-info {
  background: rgba(13,202,240,0.2);
  border: 1px solid #0dcaf0;
  color: #0dcaf0;
}
.export-btn {
  position: absolute;
  right: 25px;
  top: 30px;
}
</style>

<!-- ✅ AJOUT (nouveau style pro) : override non destructif -->
<style>
/* ==========================
   R.E.Mobiles – Sales History Pro (OVERRIDE)
   Ajout non-destructif : override des styles existants
   ========================== */

:root{
  --rem-bg-0: #0b1020;
  --rem-bg-1: #0b1220;
  --rem-surface: rgba(255,255,255,0.04);
  --rem-surface-2: rgba(255,255,255,0.06);
  --rem-border: rgba(255,255,255,0.10);
  --rem-text: #e8eef7;
  --rem-muted: rgba(232,238,247,0.65);
  --rem-accent: #3bd5ff;
  --rem-accent-2: #4b7bff;
  --rem-shadow: 0 12px 30px rgba(0,0,0,0.45);
  --rem-radius: 16px;
}

html{ color-scheme: dark; }

body{
  background:
    radial-gradient(1200px 700px at 10% 0%, rgba(59,213,255,0.12), transparent 50%),
    radial-gradient(900px 600px at 100% 0%, rgba(75,123,255,0.14), transparent 55%),
    linear-gradient(180deg, var(--rem-bg-0), var(--rem-bg-1));
  color: var(--rem-text);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Poppins", Arial, sans-serif;
  line-height: 1.45;
}

/* Titre : plus “pro”, moins “néon” */
h2{
  color: var(--rem-text);
  text-transform: none;
  letter-spacing: .2px;
  font-size: clamp(1.25rem, 2.2vw, 1.6rem);
  margin-bottom: 1.25rem;
}
h2 i{ color: var(--rem-accent); }

/* Cartes : surface + bordure + blur (sobre) */
.main-card{
  background: rgba(10, 14, 25, 0.78);
  border: 1px solid var(--rem-border);
  border-radius: var(--rem-radius);
  box-shadow: var(--rem-shadow);
  backdrop-filter: blur(10px);
}

/* Panneaux (filtres / total) : uniformisés */
.card.bg-secondary{
  background: var(--rem-surface-2)!important;
  border: 1px solid var(--rem-border);
  box-shadow: none;
}
.btn-retrait-centre {
  min-width: 220px;
  text-align: center;
}
/* Labels & champs */
label{
  color: var(--rem-muted);
  font-weight: 600;
  margin-bottom: .35rem;
}
.form-control, .form-select{
  background: rgba(0,0,0,0.25);
  color: var(--rem-text);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 12px;
}
.form-control:focus, .form-select:focus{
  background: rgba(0,0,0,0.25);
  color: var(--rem-text);
  border-color: rgba(59,213,255,0.55);
  box-shadow: 0 0 0 .2rem rgba(59,213,255,0.15);
}

/* Alert : plus lisible */
.alert-info{
  background: rgba(59,213,255,0.12);
  border: 1px solid rgba(59,213,255,0.35);
  color: var(--rem-text);
}

/* Export button : éviter overlap mobile */
.export-btn{
  position: static;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  margin: 0 0 1rem auto;
  border-radius: 12px;
}
@media (min-width: 992px){
  .export-btn{ position: absolute; right: 25px; top: 30px; margin: 0; }
}

/* Boutons : conserver la classe .btn-glow mais la rendre “pro” */
.btn-glow{
  border-radius: 12px;
  box-shadow: none;
}
.btn-glow:hover{
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(0,0,0,0.25);
}

/* Table : lisibilité + pas de “saut” au hover */
.table{ 
  border-color: rgba(255,255,255,0.08)!important;
  margin-bottom: 0;
}
.table thead{
  background: linear-gradient(90deg, rgba(75,123,255,0.75), rgba(59,213,255,0.70));
}
.table thead th{
  font-weight: 700;
  font-size: 0.92rem;
  border-bottom: 1px solid rgba(255,255,255,0.12)!important;
  white-space: nowrap;
}
.table tbody tr:hover{
  background: rgba(255,255,255,0.05);
  transform: none; /* override du scale(1.01) */
}
.table td, .table th{ vertical-align: middle; }

/* Alignement chiffres */
.table td:nth-child(5),
.table td:nth-child(6){
  text-align: right;
  font-variant-numeric: tabular-nums;
}

/* Acompte : indicateur latéral discret */
.table tbody tr.acompte-row{
  background-color: rgba(40, 199, 111, 0.07)!important;
  box-shadow: inset 4px 0 0 rgba(40,199,111,0.65);
}

/* Actions : boutons homogènes */
.table td:last-child .btn{
  min-width: 38px;
  padding: .35rem .5rem;
}

/* Sticky header + zone scroll verticale */
.table-responsive{
  max-height: min(72vh, 880px);
  overflow-y: auto;
  border-radius: 12px;
}
.table-responsive thead th{
  position: sticky;
  top: 0;
  z-index: 2;
}

/* KPI cards */
.sales-kpis{
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}
@media (max-width: 992px){
  .sales-kpis{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.kpi{
  background: var(--rem-surface);
  border: 1px solid var(--rem-border);
  border-radius: 14px;
  padding: 14px;
}
.kpi-label{
  color: var(--rem-muted);
  font-size: .86rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.kpi-label i{ color: var(--rem-accent); }
.kpi-value{
  margin-top: 6px;
  font-size: 1.25rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}
.kpi-value small{ color: var(--rem-muted); font-weight: 700; }

@media (prefers-reduced-motion: reduce){
  *{ transition: none!important; }
}
</style>
</head>

<body>
<div class="container py-4 position-relative">
  <?php include '../header.php'; ?>
  <h2><i class="bi bi-clock-history me-2"></i>Historique des ventes</h2>

  <a href="?export_csv=1" class="btn btn-success btn-glow export-btn">⬇️ Export CSV</a>

  <?php if ($msg): ?>
    <div class="alert alert-info text-center"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  
  <?php if ($isAdmin): ?>
<button
  class="btn btn-danger btn-glow btn-retrait-centre"
  onclick="ouvrirRetraitModal()"
>
  ➖ Retrait espèces
</button>

<?php endif; ?>



  <div class="main-card mb-4">


    <!-- 🔍 FILTRE -->
    <form method="GET" class="card p-3 bg-secondary mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label><i class="bi bi-person-badge"></i> Vendeur</label>
          <select name="vendeur" class="form-select">
            <option value="">-- Tous --</option>
            <?php foreach($vendeurs as $v): ?>
              <option value="<?= htmlspecialchars($v) ?>" <?= ($_GET['vendeur'] ?? '') === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label><i class="bi bi-calendar3"></i> Date début</label>
          <input type="date" name="date_debut" value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
          <label><i class="bi bi-calendar-check"></i> Date fin</label>
          <input type="date" name="date_fin" value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-glow btn-info w-100">🔎 Filtrer</button>
        </div>
      </div>
    </form>

    <!-- 💰 TOTAL PDF -->
    <form method="GET" action="/../ticket_vendeur.php" target="_blank" class="card p-3 bg-secondary mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label><i class="bi bi-person"></i> Vendeur</label>
          <select name="vendeur" class="form-select" required>
            <?php foreach($vendeurs as $v): ?>
              <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label><i class="bi bi-calendar3"></i> Date début</label>
          <input type="date" name="date_debut" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label><i class="bi bi-calendar-check"></i> Date fin</label>
          <input type="date" name="date_fin" class="form-control" required>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-glow btn-warning w-100 fw-bold">🧾 Générer total ventes</button>
        </div>
      </div>
    </form>

    <!-- 🧾 TABLEAU -->
    <div class="table-responsive">
      <table class="table table-dark table-striped table-bordered align-middle">
        <thead>
          <tr>
            <th>Date</th>
            <th>Réf Vente</th>
            <th>Type</th>
            <th>Produit</th>
            <th>Qté</th>
            <th>Total (€)</th>
            <th>Client</th>
            <th>Vendeur</th>
            <th>Paiement</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ventes)): ?>
            <tr><td colspan="10" class="text-center text-muted">Aucune vente trouvée.</td></tr>
          <?php else: foreach ($ventes as $v): ?>
            <tr class="
<?= strtolower($v['type']) === 'acompte' ? 'acompte-row' : '' ?>
<?= strtolower($v['type']) === 'retrait' ? 'table-danger' : '' ?>
">
              <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
              <td><?= htmlspecialchars($v['ref_vente']) ?></td>
              <td>
<?php if (strtolower($v['type']) === 'acompte'): ?>
  <span class="badge bg-success">💰 Acompte</span>
<?php elseif (strtolower($v['type']) === 'retrait'): ?>
  <span class="badge bg-danger">➖ Retrait</span>
<?php else: ?>
  <span class="badge bg-info">🛒 Vente</span>
<?php endif; ?>
</td>

              <td><?= htmlspecialchars($v['designation']) ?></td>
              <td><?= $v['quantite'] ?></td>
              <td><?= number_format($v['prix_total'], 2, ',', ' ') ?></td>
              <td><?= htmlspecialchars($v['client_nom']) ?></td>
              <td><?= htmlspecialchars($v['vendeur']) ?></td>
              <td><?= htmlspecialchars($v['mode_paiement']) ?></td>
              <td class="text-center">
                <a href="/../ticket_pos.php?ref=<?= urlencode($v['ref_vente']) ?>" target="_blank" class="btn btn-sm btn-info btn-glow me-1" title="Imprimer le ticket">🖨️</a>
        <?php if ($isAdmin): ?>
  <button 
    class="btn btn-sm btn-secondary btn-glow me-1"
    onclick="changerPaiement(
      '<?= htmlspecialchars($v['ref_vente']) ?>',
      '<?= htmlspecialchars($v['mode_paiement']) ?>'
    )"
    title="Modifier le mode de paiement"
  >
    💳✏️
  </button>
<?php endif; ?>

                <a href="/../converti_pos.php?ref=<?= urlencode($v['ref_vente']) ?>" target="_blank" class="btn btn-sm btn-warning btn-glow me-1" title="Transformer en facture PDF">🔄 Transformer</a>
                <?php if ($isAdmin): ?>
                  <button class="btn btn-sm btn-danger btn-glow" onclick="deleteVente('<?= htmlspecialchars($v['ref_vente']) ?>', this)" title="Supprimer ce ticket">🗑️</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($ventes)): ?>
        <tfoot>
          <tr class="table-secondary text-dark">
            <th colspan="5" class="text-end">🛒 Total ventes :</th>
            <th colspan="5"><?= number_format($totalVentes, 2, ',', ' ') ?> €</th>
          </tr>
          <tr class="table-success text-dark">
            <th colspan="5" class="text-end">💰 Total acomptes :</th>
            <th colspan="5"><?= number_format($totalAcomptes, 2, ',', ' ') ?> €</th>
          </tr>
          <?php if ($totalRetraits != 0): ?>
            <tr class="table-danger text-dark">
              <th colspan="5" class="text-end">➖ Total retraits :</th>
              <th colspan="5"><?= number_format($totalRetraits, 2, ',', ' ') ?> €</th>
            </tr>
            <?php endif; ?>
          <tr class="table-primary text-dark">
            <th colspan="5" class="text-end">💵 Total global :</th>
            <th colspan="5"><?= number_format($totalGlobal, 2, ',', ' ') ?> €</th>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<script>
async function deleteVente(ref, el) {
  if (!confirm("Supprimer ce ticket ?")) return;

  const formData = new FormData();
  formData.append('ref', ref);

  try {
    const res = await fetch('/../delete_vente.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.status === "ok") {
      const row = el.closest('tr');
      row.style.backgroundColor = "#ff000044";
      row.style.transition = "0.3s";
      setTimeout(() => row.remove(), 300);
    } else {
      alert("❌ Erreur : " + data.msg);
    }
  } catch (e) {
    alert("⚠️ Erreur de communication avec le serveur.");
  }
}

/* ✅ AJOUT : Export CSV conserve les filtres (vendeur + dates) sans modifier le HTML existant */
(() => {
  const btn = document.querySelector('a.export-btn');
  if (!btn) return;

  const url = new URL(window.location.href);
  url.searchParams.set('export_csv', '1');

  // évite toute action non voulue via paramètres
  url.searchParams.delete('delete_ref');

  btn.href = url.pathname + '?' + url.searchParams.toString();
})();

/* ✅ AJOUT : Pré-remplir "Générer total ventes" depuis les filtres (si présents) */
(() => {
  const filterForm = document.querySelector('form[method="GET"]:not([action])');
  const totalForm  = document.querySelector('form[action="/../ticket_vendeur.php"]');
  if (!filterForm || !totalForm) return;

  const vendeur = filterForm.querySelector('select[name="vendeur"]')?.value || '';
  const d1 = filterForm.querySelector('input[name="date_debut"]')?.value || '';
  const d2 = filterForm.querySelector('input[name="date_fin"]')?.value || '';

  if (vendeur) totalForm.querySelector('select[name="vendeur"]').value = vendeur;
  if (d1) totalForm.querySelector('input[name="date_debut"]').value = d1;
  if (d2) totalForm.querySelector('input[name="date_fin"]').value = d2;
})();


</script>
<script>
function changerPaiement(ref, paiementActuel) {

  const modalHtml = `
    <div class="modal fade" id="paiementModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
          <div class="modal-header">
            <h5 class="modal-title">💳 Modifier le mode de paiement</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label class="mb-2">Nouveau mode de paiement</label>
            <select id="newPaiement" class="form-select">
              <option value="Espèces">Espèces</option>
              <option value="Carte Bancaire">Carte Bancaire</option>
              <option value="Virement">Virement</option>
              <option value="Mixte">Mixte</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button class="btn btn-success" id="savePaiement">✅ Enregistrer</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Supprime ancienne modale si existe
  document.getElementById("paiementModal")?.remove();
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  const modal = new bootstrap.Modal(document.getElementById("paiementModal"));
  modal.show();

  const select = document.getElementById("newPaiement");

const mapPaiement = {
  "CB": "Carte Bancaire",
  "CARTE BANCAIRE": "Carte Bancaire",
  "ESPECES": "Espèces",
  "ESPÈCES": "Espèces",
  "VIREMENT": "Virement",
  "MIXTE": "Mixte",
  "AUTRE": "Autre"
};

const normalized = (paiementActuel || "").toString().trim().toUpperCase();

select.value = mapPaiement[normalized] || "Carte Bancaire";


  document.getElementById("savePaiement").onclick = async () => {
    const paiement = document.getElementById("newPaiement").value;
if (!paiement) {
  alert("❌ Mode de paiement invalide");
  return;
}


    const formData = new FormData();
    formData.append("ref_vente", ref);
    formData.append("mode_paiement", paiement);

    try {
      const res = await fetch("update_paiement.php", {
        method: "POST",
        body: formData
      });
      const data = await res.json();

      if (data.status === "ok") {
        modal.hide();
        location.reload();
      } else {
        alert("❌ " + data.msg);
      }
    } catch {
      alert("⚠️ Erreur serveur");
    }
  };
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function ouvrirRetraitModal() {

  const modalHtml = `
  <div class="modal fade" id="retraitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title">➖ Retrait d'espèces</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">💶 Montant du retrait (€)</label>
            <input type="number" step="0.01" min="0.01" id="retraitMontant" class="form-control" placeholder="Ex: 50">
          </div>

          <div class="mb-3">
            <label class="form-label">📝 Motif du retrait</label>
            <input type="text" id="retraitMotif" class="form-control" placeholder="Ex: Achat fournitures">
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-danger" onclick="enregistrerRetrait()">✅ Enregistrer</button>
        </div>
      </div>
    </div>
  </div>
  `;

  document.getElementById("retraitModal")?.remove();
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  const modal = new bootstrap.Modal(document.getElementById("retraitModal"));
  modal.show();
}
</script>

<script>
async function enregistrerRetrait() {

  const montant = parseFloat(document.getElementById("retraitMontant").value);
  const motif   = document.getElementById("retraitMotif").value.trim();

  if (!montant || montant <= 0) {
    alert("❌ Montant invalide");
    return;
  }

  if (!motif) {
    alert("❌ Motif obligatoire");
    return;
  }

  const formData = new FormData();
  formData.append("montant", montant);
  formData.append("motif", motif);

  try {
    const res = await fetch("retrait_especes.php", {
      method: "POST",
      body: formData
    });

    const data = await res.json();

    if (data.status === "ok") {
      location.reload();
    } else {
      alert("❌ " + data.msg);
    }

  } catch {
    alert("⚠️ Erreur serveur");
  }
}
</script>



</body>
</html>
