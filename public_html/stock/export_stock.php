<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

/*
  IMPORTANT :
  On désactive l’affichage des erreurs pour éviter
  toute pollution du CSV (HTML, warnings, deprecated)
*/
ini_set('display_errors', '0');
error_reporting(0);

// Headers CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=stock.csv');

// BOM UTF-8 pour Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// En-tête CSV (séparateur , / escape explicite)
fputcsv(
    $output,
    ['reference','designation','prix_vente','quantite','seuil'],
    ',',
    '"',
    '\\'
);

// Données
$stmt = $pdo->query("
    SELECT reference, designation, prix_vente, quantite, seuil
    FROM stock_articles
    ORDER BY designation
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv(
        $output,
        [
            $row['reference'],
            $row['designation'],
            $row['prix_vente'],
            $row['quantite'],
            $row['seuil']
        ],
        ',',
        '"',
        '\\'
    );
}

fclose($output);
exit;
