<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
require_once __DIR__ . '/../sync_time.php';

header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$conn->set_charset('utf8mb4');

$ref = trim($_GET['ref'] ?? '');
if ($ref === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
  SELECT
    id,
    montant,
    mode_paiement,
    date_versement,
    user_nom,
    ref_acompte
  FROM acomptes_devices
  WHERE device_ref = ?
  ORDER BY date_versement ASC
");

$stmt->bind_param("s", $ref);
$stmt->execute();

$res  = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

echo json_encode($data);
