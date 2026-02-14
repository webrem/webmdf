<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// api_get_products.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique

// Connexion (adapte si besoin)
$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { http_response_code(500); echo json_encode([]); exit; }
$conn->set_charset('utf8mb4');

// Vérifier existence colonne et construire mapping prix + quantite
$cols = [];
$resCols = $conn->query("SHOW COLUMNS FROM stock_articles");
if ($resCols) {
    while ($r = $resCols->fetch_assoc()) { $cols[] = $r['Field']; }
} else {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

// Déterminer la colonne prix (prix_vente sinon prix_achat sinon first numeric)
$priceCol = null;
foreach (['prix_vente','price','sale_price','prix','prix_achat','price_buy'] as $c) {
    if (in_array($c, $cols, true)) { $priceCol = $c; break; }
}
if ($priceCol === null) {
    // fallback: find any decimal-like column
    foreach ($cols as $cname) {
        if (stripos($cname,'prix')!==false || stripos($cname,'price')!==false) { $priceCol = $cname; break; }
    }
}
if ($priceCol === null) $priceCol = $cols[0]; // dernier recours

// Quantité
$qtyCol = in_array('quantite',$cols,true) ? 'quantite' : (in_array('quantity',$cols,true)?'quantity':null);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_like = '%'.$q.'%';

$sql = "SELECT id, reference, designation";
$sql .= ", `$priceCol` AS price";
if ($qtyCol) $sql .= ", `$qtyCol` AS quantite";
$sql .= " FROM stock_articles WHERE (reference LIKE ? OR designation LIKE ?) ORDER BY designation LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $q_like, $q_like);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = [
      'id' => (int)$row['id'],
      'reference' => $row['reference'],
      'designation' => $row['designation'],
      'price' => isset($row['price']) ? (float)$row['price'] : 0.0,
      'quantite' => isset($row['quantite']) ? (int)$row['quantite'] : 0
    ];
}
echo json_encode($out);
