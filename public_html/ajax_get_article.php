<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

header('Content-Type: application/json');
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { echo json_encode([]); exit; }
$conn->set_charset("utf8mb4");

$ref = trim($_GET['ref'] ?? '');
if ($ref === '') { echo json_encode([]); exit; }

$stmt = $conn->prepare("SELECT reference, designation, prix_vente FROM stock_articles WHERE reference LIKE CONCAT('%', ?, '%') OR ean LIKE CONCAT('%', ?, '%') OR designation LIKE CONCAT('%', ?, '%') LIMIT 1");
$stmt->bind_param("sss", $ref, $ref, $ref);
$stmt->execute();
$res = $stmt->get_result();
$article = $res->fetch_assoc();

echo json_encode($article ?: []);
