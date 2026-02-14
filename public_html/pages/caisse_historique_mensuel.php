<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../sync_time.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Accès refusé.");
}

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset("utf8mb4");

$mois = $_GET['mois'] ?? date('Y-m');

$stmt = $conn->prepare("
   SELECT
    c.id,
    c.date_caisse,
    c.heure_ouverture,
    c.montant_ouverture,
    c.heure_fermeture,
    c.total_especes,
    c.total_cb,
    c.montant_coffre,
    c.validated_at,
    u.username AS valide_par
    FROM caisse_jour c
    LEFT JOIN users u ON u.id = c.validated_by
    AND DATE_FORMAT(c.date_caisse, '%Y-%m') = ?
    ORDER BY c.date_caisse ASC
");
$stmt->bind_param("s", $mois);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$sumE = $sumC = 0;

while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $sumE += $r['total_especes'];
    $sumC += $r['total_cb'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Historique mensuel caisse</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
/* ==========================
   CAISSE – HISTORIQUE MENSUEL
   STYLE IDENTIQUE ventes_historique.php
   ========================== */

:root{
  --rem-bg-0: #0b1020;
  --rem-bg-1: #0b1220;
  --rem-surface: rgba(255,255,255,0.04);
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
  min-height: 100vh;
}

/* Container principal */
.main-card{
  background: rgba(10,14,25,.78);
  border: 1px solid var(--rem-border);
  border-radius: var(--rem-radius);
  box-shadow: var(--rem-shadow);
  backdrop-filter: blur(10px);
  padding: 24px;
}

/* Titre */
h1{
  text-align:center;
  font-weight: 800;
  margin-bottom: 1.5rem;
}
h1 i{ color: var(--rem-accent); }

/* Filtres */
.actions label{
  color: var(--rem-muted);
  font-weight: 600;
  margin-right: .5rem;
}
.actions input{
  background: rgba(0,0,0,.25);
  color: var(--rem-text);
  border: 1px solid rgba(255,255,255,.15);
  border-radius: 12px;
  padding: 6px 10px;
}
.actions button{
  margin-left: .5rem;
}

/* Table */
.table{
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 0;
}
.table thead{
  background: linear-gradient(90deg, rgba(75,123,255,.85), rgba(59,213,255,.85));
}
.table thead th{
  font-weight: 700;
  font-size: .85rem;
  border-bottom: 1px solid rgba(255,255,255,.12)!important;
  white-space: nowrap;
}
.table tbody tr{
  background: rgba(255,255,255,.02);
}
.table tbody tr:hover{
  background: rgba(255,255,255,.06);
}
.table td{
  vertical-align: middle;
  color: var(--rem-text);
}

/* Actions */
.actions-cell{
  text-align:center;
}
.btn-action{
  display:block;
  margin-bottom:6px;
  padding:6px 8px;
  font-size:.75rem;
  border-radius:10px;
  text-decoration:none;
  color:var(--rem-text);
  background: rgba(0,0,0,.35);
  border:1px solid rgba(255,255,255,.18);
  transition: all .15s ease;
}
.btn-action:hover{
  background: rgba(59,213,255,.25);
  transform: translateY(-1px);
}

/* Footer total */
tfoot th{
  background: rgba(0,0,0,.45);
  color: var(--rem-accent);
  font-weight: 800;
}
</style>

</head>
<body>

<div class="container py-4">
  <div class="main-card">

<h1>📊 Historique mensuel validé</h1>

<form method="get" class="actions">
<label>Mois :</label>
<input type="month" name="mois" value="<?= htmlspecialchars($mois) ?>">
<button>Afficher</button>
</form>

<table class="table table-dark table-hover align-middle text-center">
<thead>
<tr>
  <th>Date</th>
<th>🟢 Ouverture</th>
<th>💼 Montant ouverture</th>
<th>🔴 Fermeture</th>
<th>💵 Espèces</th>
<th>💳 CB</th>
<th>🔐 Coffre</th>
<th>💰 Total</th>
<th>Validé</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if (!$rows): ?>
<tr><td colspan="6" style="text-align:center">Aucune caisse validée</td></tr>
<?php endif; ?>

<?php foreach ($rows as $r): ?>

<tr>
  <!-- Date -->
  <td><strong><?= date('d/m/Y', strtotime($r['date_caisse'])) ?></strong></td>

  <!-- Ouverture -->
  <td class="text-success">
    <?= htmlspecialchars($r['heure_ouverture'] ?? '-') ?>
  </td>

  <!-- Montant ouverture -->
  <td class="text-info fw-semibold">
    <?= number_format($r['montant_ouverture'] ?? 0,2,',',' ') ?> €
  </td>

  <!-- Fermeture -->
  <td class="text-danger">
    <?= htmlspecialchars($r['heure_fermeture'] ?? '-') ?>
  </td>

  <!-- Espèces -->
  <td class="text-success fw-semibold">
    <?= number_format($r['total_especes'] ?? 0,2,',',' ') ?> €
  </td>

  <!-- CB -->
  <td class="text-info fw-semibold">
    <?= number_format($r['total_cb'] ?? 0,2,',',' ') ?> €
  </td>

  <!-- Coffre -->
  <td class="text-warning fw-semibold">
    <?= number_format($r['montant_coffre'] ?? 0,2,',',' ') ?> €
  </td>

  <!-- Total -->
  <td class="fw-bold text-warning fs-6">
    <?= number_format(($r['total_especes'] ?? 0) + ($r['total_cb'] ?? 0),2,',',' ') ?> €
  </td>

  <!-- Validé -->
  <td>
    <span class="badge bg-success">
      <i class="bi bi-check-circle"></i>
      <?= htmlspecialchars($r['valide_par']) ?>
    </span>
  </td>

  <!-- Actions -->
  <td class="actions-cell">
    <div class="d-flex justify-content-center gap-2">
      <a href="../print_confirmation_ouverture.php?id=<?= (int)$r['id'] ?>" target="_blank"
         class="btn btn-sm btn-outline-success" title="Ticket ouverture">
        <i class="bi bi-box-arrow-in-right"></i>
      </a>

      <a href="../print_confirmation_fermeture.php?id=<?= (int)$r['id'] ?>" target="_blank"
         class="btn btn-sm btn-outline-light" title="Ticket fermeture">
        <i class="bi bi-receipt"></i>
      </a>

      <a href="../print_caisse.php?id=<?= (int)$r['id'] ?>" target="_blank"
         class="btn btn-sm btn-outline-info" title="Imprimer fermeture caisse">
        <i class="bi bi-printer"></i>
      </a>

      <a href="../print_ventes_jour.php?date=<?= htmlspecialchars($r['date_caisse']) ?>" target="_blank"
         class="btn btn-sm btn-outline-warning" title="Imprimer ventes du jour">
        <i class="bi bi-list-check"></i>
      </a>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot>
<tr class="total-mois-row">

  <th class="text-start">TOTAL MOIS</th>
  <th></th>
  <th></th>
  <th></th>

  <!-- Espèces -->
  <th class="text-success fw-bold">
    <?= number_format($sumE, 2, ',', ' ') ?> €
  </th>

  <!-- CB -->
  <th class="text-info fw-bold">
    <?= number_format($sumC, 2, ',', ' ') ?> €
  </th>

  <!-- Coffre -->
  <th></th>

  <!-- Total -->
  <th class="text-warning fw-bold fs-6">
    <?= number_format($sumE + $sumC, 2, ',', ' ') ?> €
  </th>

  <th></th>
  <th></th>

</tr>
</tfoot>


</table>

<div class="actions d-flex gap-2 mt-3">

  <a
    href="export_caisse_comptable.php?mois=<?= urlencode($mois) ?>"
    class="btn btn-outline-success btn-sm"
  >
    <i class="bi bi-download"></i>
    Export expert-comptable
  </a>

  <a
    href="dashboard.php"
    class="btn btn-outline-light btn-sm"
  >
    <i class="bi bi-arrow-left-circle"></i>
    Retour au dashboard
  </a>

</div>



</div>

</div>
<!-- Bootstrap JS (OBLIGATOIRE pour tooltips) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.querySelectorAll('[title]').forEach(el => {
  new bootstrap.Tooltip(el)
})
</script>



<script>
if (window.bootstrap) {
  document.querySelectorAll('[title]').forEach(el => {
    new bootstrap.Tooltip(el)
  })
}
</script>

</body>
</html>
