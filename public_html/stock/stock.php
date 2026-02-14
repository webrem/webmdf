<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

$articles = $pdo->query("SELECT * FROM stock_articles ORDER BY designation")->fetchAll(PDO::FETCH_ASSOC);

$totalProduits = count($articles);
$valeurStock = 0;
$stockFaible = 0;
$rupture = 0;

foreach ($articles as $a) {
    $valeurStock += ((float)$a['prix_vente']) * ((int)$a['quantite']);
    if ((int)$a['quantite'] === 0) $rupture++;
    elseif ((int)$a['quantite'] <= 3) $stockFaible++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Stock – R.E.Mobiles</title>

<!-- ✅ CSS séparé -->
<link rel="stylesheet" href="../assets/css/stock.css">
</head>

<body>

<?php include '../header.php'; ?>

<h1>📦 Gestion du stock</h1>
<p class="subtitle">R.E.Mobiles</p>

<!-- KPI -->
<div class="kpi">
    <div class="kpi-card">Produits<br><span><?= $totalProduits ?></span></div>
    <div class="kpi-card">Valeur stock<br><span><?= number_format($valeurStock,2,',',' ') ?> €</span></div>
    <div class="kpi-card">Stock faible<br><span><?= $stockFaible ?></span></div>
    <div class="kpi-card">Ruptures<br><span><?= $rupture ?></span></div>
</div>

<!-- ACTIONS -->
<div class="actions-bar">
    <a href="add_stock.php">➕ Nouveau produit</a>
    <a href="import_stock.php" class="secondary">📥 Import CSV</a>
    <a href="export_stock.php" class="secondary">📤 Export CSV</a>
</div>

<form method="post" action="stock_bulk_delete.php">
    <button type="submit" class="secondary" onclick="return confirm('Supprimer les produits sélectionnés ?')">
        🗑 Supprimer la sélection
    </button>

    <table>
        <thead>
            <tr>
                <th><input type="checkbox" onclick="toggle(this)"></th>
                <th>Référence</th>
                <th>Produit</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
            <tr>
                <td><input type="checkbox" name="ids[]" value="<?= (int)$a['id'] ?>"></td>
                <td><?= htmlspecialchars($a['reference'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['designation'] ?? '') ?></td>
                <td><?= number_format((float)$a['prix_vente'],2,',',' ') ?> €</td>
                <td><?= (int)$a['quantite'] ?></td>
                <td>
                <?php
                    if ((int)$a['quantite'] === 0) echo '<span class="out">Rupture</span>';
                    elseif ((int)$a['quantite'] <= 3) echo '<span class="warn">Faible</span>';
                    else echo '<span class="ok">OK</span>';
                ?>
                </td>
                <td class="actions-cell">
                    <a href="edit_stock.php?id=<?= (int)$a['id'] ?>" title="Modifier">✏️</a>
                    <a href="move_stock.php?id=<?= (int)$a['id'] ?>" title="Déplacer">📦</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</form>

<script>
function toggle(source){
    document.querySelectorAll('input[name="ids[]"]').forEach(cb=>cb.checked=source.checked);
}
</script>

</body>
</html>
