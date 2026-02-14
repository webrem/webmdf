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
    echo json_encode(["status" => "error", "msg" => "Erreur DB"]);
    exit;
}
$conn->set_charset("utf8mb4");

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "ID invalide"]);
    exit;
}

// --- Vérifie si commande existe
$stmt = $conn->prepare("SELECT id FROM historiques WHERE id=? AND statut='commande'");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["status" => "error", "msg" => "Commande introuvable"]);
    exit;
}
$stmt->close();

// --- Supprimer acomptes liés
$stmtAcc = $conn->prepare("DELETE FROM acomptes WHERE commande_id=?");
$stmtAcc->bind_param("i", $id);
$stmtAcc->execute();
$stmtAcc->close();

// --- Supprimer la commande elle-même
$stmtDel = $conn->prepare("DELETE FROM historiques WHERE id=?");
$stmtDel->bind_param("i", $id);
$stmtDel->execute();
$stmtDel->close();

echo json_encode(["status" => "ok", "msg" => "Commande supprimée avec succès"]);
