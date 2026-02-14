<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
header('Content-Type: application/json; charset=utf-8');

// --- Connexion SQL ---
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

// --- Vérifie paramètre ---
$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// --- Requête de recherche ---
$sql = "SELECT reference, designation, quantite 
        FROM stock_articles
        WHERE reference LIKE CONCAT('%', ?, '%') 
           OR designation LIKE CONCAT('%', ?, '%')
        ORDER BY designation ASC
        LIMIT 15";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $q, $q);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = [
        'reference'   => $row['reference'],
        'designation' => $row['designation'],
        'quantite'    => (int)$row['quantite']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
