<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT reference, designation, marque, modele, etat, prix_vente, quantite
    FROM stock_articles
    WHERE reference LIKE CONCAT('%', ?, '%')
       OR ean LIKE CONCAT('%', ?, '%')
       OR designation LIKE CONCAT('%', ?, '%')
    ORDER BY designation ASC
    LIMIT 20
");
$stmt->bind_param("sss", $q, $q, $q);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
