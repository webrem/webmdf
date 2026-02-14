<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Connexion DB
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur DB"]);
    exit;
}
$conn->set_charset("utf8mb4");

// Récupération du terme recherché
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

// Recherche des clients correspondants
$stmt = $conn->prepare("SELECT id, nom, telephone, remise_pct FROM clients WHERE nom LIKE ? ORDER BY nom ASC LIMIT 10");
$like = "%$q%";
$stmt->bind_param("s", $like);
$stmt->execute();
$res = $stmt->get_result();

$clients = [];
while ($row = $res->fetch_assoc()) {
    $clients[] = [
        "id" => (int)$row["id"],
        "nom" => $row["nom"],
        "telephone" => $row["telephone"] ?: "",
        "remise_pct" => (int)$row["remise_pct"]
    ];
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($clients);
