<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/sync_time.php';

// 🔒 Connexion base principale clients
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur DB"]);
    exit;
}

$nom  = trim($_POST['nom'] ?? '');
$tel  = trim($_POST['telephone'] ?? '');
$mail = trim($_POST['email'] ?? '');

if ($nom === '' || $tel === '') {
    echo json_encode(["status" => "error", "message" => "Champs manquants"]);
    exit;
}

// 🔍 Vérifie si le client existe déjà
$stmt = $conn->prepare("SELECT id FROM clients WHERE nom = ? OR telephone = ? LIMIT 1");
$stmt->bind_param("ss", $nom, $tel);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO clients (nom, telephone, email, date_creation) VALUES (?, ?, ?, NOW())");
    $insert->bind_param("sss", $nom, $tel, $mail);
    $insert->execute();
    echo json_encode(["status" => "ok", "message" => "Client ajouté"]);
    $insert->close();
} else {
    echo json_encode(["status" => "exists", "message" => "Client déjà présent"]);
}

$stmt->close();
$conn->close();
?>
