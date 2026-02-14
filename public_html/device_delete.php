<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// === Vérification admin ===
if ($_SESSION['role'] !== 'admin') {
    die("Accès refusé : réservé aux administrateurs.");
}

require_once __DIR__ . '/device_utils.php';

// --- Connexion DB ---
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    die("Erreur DB : " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$ref = $_GET['ref'] ?? '';
if ($ref === '') {
    die("Référence manquante.");
}

/* === 1. Suppression des photos associées === */
$photos = get_photos_by_ref($ref);
foreach ($photos as $photo) {
    $filePath = __DIR__ . '/' . $photo;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}
$stmt = $conn->prepare("DELETE FROM device_photos WHERE device_ref=?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$stmt->close();

/* === 2. Suppression des acomptes liés === */
$stmt = $conn->prepare("DELETE FROM acomptes_devices WHERE device_ref=?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$stmt->close();

/* === 3. Suppression des acomptes dans ventes_historique === */
$refAcompte = "POS-ACOMPTE-" . $ref;
$stmt = $conn->prepare("DELETE FROM ventes_historique WHERE ref_vente=? AND type='acompte'");
$stmt->bind_param("s", $refAcompte);
$stmt->execute();
$stmt->close();

/* === 4. Suppression de l’appareil principal === */
$stmt = $conn->prepare("DELETE FROM devices WHERE ref=?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$stmt->close();

header("Location: devices_list.php?msg=deleted");
exit;
