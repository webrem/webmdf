<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// --- Connexion DB ---
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur DB"]);
    exit;
}
$conn->set_charset("utf8mb4");

// --- Récupération du paramètre ---
$q = trim($_GET['nom'] ?? '');
if ($q === '') {
    echo json_encode(["remise_pct" => 0]);
    exit;
}

// --- Recherche du client ---
$stmt = $conn->prepare("SELECT remise_pct FROM clients WHERE nom LIKE ? LIMIT 1");
$like = "%$q%";
$stmt->bind_param("s", $like);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

// --- Réponse JSON ---
header('Content-Type: application/json');
echo json_encode($res ?: ["remise_pct" => 0]);
