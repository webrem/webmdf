<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "msg" => "Accès refusé"]);
    exit;
}

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "msg" => "Erreur de connexion DB"]);
    exit;
}
$conn->set_charset("utf8mb4");

$ref = trim($_POST['ref'] ?? '');
if ($ref === '') {
    echo json_encode(["status" => "error", "msg" => "Référence manquante"]);
    exit;
}

// --- Supprimer dans ventes
$stmt = $conn->prepare("DELETE FROM ventes WHERE ref_vente = ?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$stmt->close();

// --- Supprimer dans ventes_historique
$stmt2 = $conn->prepare(
    "DELETE FROM ventes_historique 
     WHERE ref_vente = ? 
     AND LOWER(type) IN ('acompte','retrait')"
);

$stmt2->bind_param("s", $ref);
$stmt2->execute();
$stmt2->close();

// --- Supprimer dans acomptes_devices si acompte
if (strpos($ref, "POS-ACOMPTE-") === 0) {
    $deviceRef = str_replace("POS-ACOMPTE-", "", $ref);
    $stmt3 = $conn->prepare("DELETE FROM acomptes_devices WHERE device_ref = ?");
    $stmt3->bind_param("s", $deviceRef);
    $stmt3->execute();
    $stmt3->close();
}

echo json_encode(["status" => "ok", "msg" => "Ticket supprimé"]);
