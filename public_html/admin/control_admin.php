<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
include '../header.php';

/* DEBUG (à retirer après validation) */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ============================
   SÉCURITÉ ADMIN
============================ */
$isAdmin = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') $isAdmin = true;
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') $isAdmin = true;
if (!$isAdmin) die("Accès refusé");

/* ============================
   CONNEXION DB
============================ */
$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
if ($conn->connect_error) die("Erreur DB");
$conn->set_charset("utf8mb4");

/* ============================
   UTILISATEURS (rapport vendeur)
============================ */
$users = $conn->query("
    SELECT username
    FROM users
    WHERE role IN ('admin','user')
    ORDER BY username ASC
");

/* ============================
   VALEURS PAR DÉFAUT (rapport)
============================ */
$dateDebut  = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin    = $_GET['date_fin'] ?? date('Y-m-d');
$vendeurSel = $_GET['vendeur'] ?? '';

/* ============================
   DÉTECTION COLONNE PANNE (repairs)
============================ */
$panneColumn = null;
$possiblePanneCols = ['problem','issue','note','details','title'];

$res = $conn->query("
    SELECT COLUMN_NAME 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'repairs'
");
$repairCols = [];
while ($c = $res->fetch_assoc()) {
    $repairCols[] = $c['COLUMN_NAME'];
}
foreach ($possiblePanneCols as $col) {
    if (in_array($col, $repairCols)) {
        $panneColumn = $col;
        break;
    }
}

/* ============================
   DÉTECTION COLONNE PIÈCE (repair_parts)
============================ */
$pieceColumn = null;
$possiblePieceCols = ['part_name','piece','designation','label','title'];

$res = $conn->query("
    SELECT COLUMN_NAME 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'repair_parts'
");
$partCols = [];
while ($c = $res->fetch_assoc()) {
    $partCols[] = $c['COLUMN_NAME'];
}
foreach ($possiblePieceCols as $col) {
    if (in_array($col, $partCols)) {
        $pieceColumn = $col;
        break;
    }
}

/* ============================
   KPIs
============================ */
$ventesNb  = $conn->query("SELECT COUNT(*) nb FROM ventes")->fetch_assoc()['nb'] ?? 0;
$repairsNb = $conn->query("SELECT COUNT(*) nb FROM repairs")->fetch_assoc()['nb'] ?? 0;

$caMois = 0;
$res = $conn->query("
    SELECT SUM(total) ca
    FROM ventes
    WHERE DATE_FORMAT(date_vente,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
");
if ($res && $r = $res->fetch_assoc()) $caMois = $r['ca'] ?? 0;





/* ============================
   ÉTAPE 7 — STOCK FAIBLE
============================ */
$stockFaible = [];
$stockTableUsed = null;

$stockTables = ['stock','stock_articles','stock_stats'];

foreach ($stockTables as $table) {

    $cols = [];
    $res = $conn->query("
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = '$table'
    ");
    if (!$res || $res->num_rows === 0) continue;

    while ($c = $res->fetch_assoc()) {
        $cols[] = $c['COLUMN_NAME'];
    }

    $prodColStock = null;
    foreach (['produit','designation','label','name','article'] as $c) {
        if (in_array($c, $cols)) { $prodColStock = $c; break; }
    }

    $qtyColStock = null;
    foreach (['quantite','qty','stock','quantity'] as $c) {
        if (in_array($c, $cols)) { $qtyColStock = $c; break; }
    }

    if ($prodColStock && $qtyColStock) {
        $stockTableUsed = $table;
        $sql = "
            SELECT `$prodColStock` AS produit, `$qtyColStock` AS stock
            FROM `$table`
            ORDER BY `$qtyColStock` ASC
            LIMIT 10
        ";
        $res2 = $conn->query($sql);
        if ($res2) {
            while ($r = $res2->fetch_assoc()) {
                $stockFaible[] = $r;
            }
        }
        break;
    }
}

?>





<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{margin:0;background:#0f0f0f;color:#fff;font-family:Segoe UI}
.dashboard{max-width:1100px;margin:auto;padding:30px}
.card{background:#1a1a1a;border-radius:12px;padding:25px;margin-bottom:25px}
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}
.kpi{background:#000;padding:20px;border-left:4px solid #d4af37}
.note{color:#aaa;font-size:13px}
label{font-size:13px;color:#ccc}
select,input{background:#000;color:#fff;border:1px solid #333;padding:8px;border-radius:6px}
.btn{background:#c0392b;color:#fff;border:none;padding:10px 16px;border-radius:6px;cursor:pointer}
ul{padding-left:20px}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:8px;border-bottom:1px solid #333}
th{color:#d4af37;text-align:left}
</style>
</head>

<body>
<div class="dashboard">

<!-- RAPPORT VENDEUR -->
<div class="card">
<h2>🧾 Génération rapport vendeur (PDF A4)</h2>
<form method="get" action="rapport_vendeurs_a4.php" target="_blank">
<label>Vendeur</label><br>
<select name="vendeur" required>
<option value="">-- Sélectionner un utilisateur --</option>
<?php while ($u = $users->fetch_assoc()): ?>
<option value="<?= htmlspecialchars($u['username']) ?>" <?= ($vendeurSel === $u['username']) ? 'selected' : '' ?>>
<?= htmlspecialchars($u['username']) ?>
</option>
<?php endwhile; ?>
</select>
<br><br>
<label>Du</label><br>
<input type="date" name="date_debut" value="<?= $dateDebut ?>" required>
<br><br>
<label>Au</label><br>
<input type="date" name="date_fin" value="<?= $dateFin ?>" required>
<br><br>
<button type="submit" class="btn">🖨 Générer le rapport</button>
</form>
<div class="note">Le rapport inclut ventes, acomptes, réparations et commissions.</div>
</div>

<!-- KPIs -->
<div class="kpi-grid">
<div class="kpi">Ventes<br><strong><?= $ventesNb ?></strong></div>
<div class="kpi">Réparations<br><strong><?= $repairsNb ?></strong></div>
<div class="kpi">CA mois<br><strong><?= number_format($caMois,2,',',' ') ?> €</strong></div>
</div>



<!-- ÉTAPE 7 -->
<div class="card"><h2>📦 Produits en stock faible</h2>
<?php if (!$stockTableUsed): ?><div class="note">Aucune table de stock exploitable détectée.</div>
<?php elseif (!$stockFaible): ?><div class="note">Aucun produit en stock.</div>
<?php else: ?><div class="note">Source stock : <?= htmlspecialchars($stockTableUsed) ?></div>
<table><thead><tr><th>#</th><th>Produit</th><th>Stock</th></tr></thead>
<tbody><?php foreach ($stockFaible as $i => $s): ?><tr><td><?= $i+1 ?></td><td><?= htmlspecialchars($s['produit']) ?></td><td style="color:<?= ((int)$s['stock'] <= 2 ? '#c0392b' : '#d4af37') ?>"><?= (int)$s['stock'] ?></td></tr><?php endforeach; ?></tbody></table>
<?php endif; ?>
</div>



</div>

<script>
new Chart(document.getElementById('caChart'),{
type:'line',
data:{labels:<?= json_encode($labels) ?>,datasets:[{label:'CA mensuel',data:<?= json_encode($caData) ?>,borderColor:'#d4af37'}]}
});
new Chart(document.getElementById('repChart'),{
type:'bar',
data:{labels:<?= json_encode($labels) ?>,datasets:[{label:'Réparations',data:<?= json_encode($repData) ?>,backgroundColor:'#c0392b'}]}
});
</script>

</body>
</html>
