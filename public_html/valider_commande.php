<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupération de l'ID devis
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID devis invalide.");
}
$devisId = (int)$_GET['id'];

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB"); }

// Vérifier que le devis existe bien
$stmt = $conn->prepare("SELECT id FROM historiques WHERE id=? AND (statut IS NULL OR statut='' OR statut='devis' OR statut='ticket')");
$stmt->bind_param("i", $devisId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("⚠️ Devis introuvable ou déjà validé.");
}
$stmt->close();

// Transformer le devis en commande
$stmt = $conn->prepare("UPDATE historiques SET statut='commande', updated_at=NOW() WHERE id=?");
$stmt->bind_param("i", $devisId);
$stmt->execute();
$stmt->close();

// Facultatif : enregistrer qui a validé (admin ou user)
$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$log = $conn->prepare("INSERT INTO validations (devis_id, user_id, role, date_validation) VALUES (?, ?, ?, NOW())");
$log->bind_param("iis", $devisId, $userId, $role);
$log->execute();
$log->close();

$conn->close();

// Retour vers la liste des devis
header("Location: admin.php?msg=devis_valide");
exit;
