<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require 'db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=stock.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Reference','Produit','Prix','Quantite']);

$stmt = $pdo->query("SELECT * FROM stock_articles");
while ($row = $stmt->fetch()) {
    fputcsv($out, [$row['reference'], $row['designation'], $row['prix_vente'], $row['quantite']]);
}
fclose($out);
exit;
