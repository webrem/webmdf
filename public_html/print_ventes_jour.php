<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';
if (!isset($_SESSION['user_id'])) exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset("utf8mb4");

$date = $_GET['date'] ?? date('Y-m-d');

$rows = [];
$totE = 0;
$totC = 0;

/* =========================
   FOND DE CAISSE DU JOUR
   ========================= */
$stmt = $conn->prepare("
    SELECT montant_ouverture
    FROM caisse_jour
    WHERE date_caisse = ?
    LIMIT 1
");
$stmt->bind_param("s", $date);
$stmt->execute();
$resFond = $stmt->get_result()->fetch_assoc();

$fondCaisse = (float)($resFond['montant_ouverture'] ?? 0);


/* =========================
   1️⃣ VENTES DU JOUR
   ========================= */
$stmt = $conn->prepare("
    SELECT
        id,
        date_vente,
        designation,
        mode_paiement,
        prix_total,
        paiement_especes,
        paiement_cb
    FROM ventes
    WHERE vente_principale = 1
    AND DATE(date_vente) = ?
");
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();

while ($v = $res->fetch_assoc()) {

    if ($v['mode_paiement'] === 'Espèces') {
        $montant = (float)$v['prix_total'];
        $totE += $montant;
        $paiement = 'Espèces';
    }
    elseif ($v['mode_paiement'] === 'Carte Bancaire') {
        $montant = (float)$v['prix_total'];
        $totC += $montant;
        $paiement = 'Carte Bancaire';
    }
    elseif ($v['mode_paiement'] === 'Mixte') {
        $montant = (float)$v['paiement_especes'] + (float)$v['paiement_cb'];
        $totE += (float)$v['paiement_especes'];
        $totC += (float)$v['paiement_cb'];
        $paiement = 'Mixte';
    } else {
        continue;
    }

    $rows[] = [
        'id' => $v['id'],
        'date_vente' => $v['date_vente'],
        'designation' => $v['designation'],
        'type' => 'Vente',
        'paiement' => $paiement,
        'montant' => $montant
    ];
}

/* =========================
   2️⃣ ACOMPTES DU JOUR
   ========================= */
$stmt = $conn->prepare("
    SELECT
        id,
        date_vente,
        designation,
        mode_paiement,
        prix_total
    FROM ventes_historique
    WHERE LOWER(type) = 'acompte'
    AND DATE(date_vente) = ?
");
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();

while ($v = $res->fetch_assoc()) {

    $montant = (float)$v['prix_total'];
    $mode = strtoupper($v['mode_paiement']); // NORMALISATION

    if (strpos($mode, 'ESP') === 0) {
        // Tout ce qui commence par ESP = espèces
        $totE += $montant;
    }
    elseif ($mode === 'CARTE BANCAIRE' || $mode === 'CB') {
        $totC += $montant;
    }

    $rows[] = [
        'id' => $v['id'],
        'date_vente' => $v['date_vente'],
        'designation' => $v['designation'],
        'type' => 'Acompte',
        'paiement' => $v['mode_paiement'],
        'montant' => $montant
    ];
}


/* =========================
   3️⃣ TRI CHRONOLOGIQUE
   ========================= */
usort($rows, function($a, $b) {
    return strtotime($a['date_vente']) <=> strtotime($b['date_vente']);
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ventes & acomptes du <?= htmlspecialchars($date) ?></title>
<style>
body { font-family: Arial; }
table { width:100%; border-collapse:collapse; }
th,td { border:1px solid #000; padding:6px; font-size:12px; }
th { background:#eee; }
.acompte { background:#e6ffe6; }
@media print { button { display:none; } }
</style>
</head>
<body>

<h2>Ventes & acomptes du <?= htmlspecialchars($date) ?></h2>

<table>
<tr>
    <th>#</th>
    <th>Heure</th>
    <th>Type</th>
    <th>Désignation</th>
    <th>Paiement</th>
    <th>Montant</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr class="<?= $r['type'] === 'Acompte' ? 'acompte' : '' ?>">
    <td><?= (int)$r['id'] ?></td>
    <td><?= date('H:i', strtotime($r['date_vente'])) ?></td>
    <td><?= strtoupper($r['type']) ?></td>
    <td><?= htmlspecialchars($r['designation']) ?></td>
    <td><?= htmlspecialchars($r['paiement']) ?></td>
    <td><?= number_format($r['montant'], 2, ',', ' ') ?> €</td>
</tr>
<?php endforeach; ?>

<tr>
    <th colspan="5">TOTAL ESPÈCES</th>
    <th><?= number_format($totE, 2, ',', ' ') ?> €</th>
</tr>
<tr>
    <th colspan="5">FOND DE CAISSE</th>
    <th><?= number_format($fondCaisse, 2, ',', ' ') ?> €</th>
</tr>
<tr>
    <th colspan="5">TOTAL CB</th>
    <th><?= number_format($totC, 2, ',', ' ') ?> €</th>
</tr>
<tr>
    <th colspan="5">TOTAL GÉNÉRAL</th>
    <th><?= number_format($totE + $totC, 2, ',', ' ') ?> €</th>
</tr>

</table>

<button onclick="window.print()">🖨️ Imprimer</button>

</body>
</html>
