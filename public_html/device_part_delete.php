<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_GET['id']) || !isset($_GET['ref'])) {
    die("Paramètres manquants.");
}

$id = intval($_GET['id']);
$ref = trim($_GET['ref']);

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Récupère la référence de stock avant suppression
$stmt = $conn->prepare("SELECT stock_ref FROM device_parts WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($stock_ref);
$stmt->fetch();
$stmt->close();

if ($stock_ref) {
    // Supprime la pièce du device_parts
    $del = $conn->prepare("DELETE FROM device_parts WHERE id=?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();

    // Réintègre +1 dans le stock
    $upd = $conn->prepare("UPDATE stock_articles SET quantite = quantite + 1 WHERE reference=?");
    $upd->bind_param("s", $stock_ref);
    $upd->execute();
    $upd->close();
}

// Redirection
header("Location: device_status.php?ref=" . urlencode($ref));
exit;
?>
