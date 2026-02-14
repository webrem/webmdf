<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * 🕒 GARDIEN DE SYNCHRONISATION R.E.Mobiles
 * Garantit que PHP + MySQL sont toujours alignés sur l’heure de Cayenne (-03:00)
 * À inclure automatiquement dans toutes les pages (via sync_time.php)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le fuseau PHP (priorité)
date_default_timezone_set('America/Cayenne');

// Si la connexion MySQL existe, la vérifier
if (isset($conn) && $conn instanceof mysqli) {
    // Vérifie si le fuseau actuel MySQL est correct
    $check = $conn->query("SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP()) AS diff");
    if ($check) {
        $diff = $check->fetch_assoc()['diff'] ?? '';
        if (strpos($diff, '-03:00') === false && strpos($diff, '03:00') === false) {
            // Corrige le fuseau MySQL
            $conn->query("SET time_zone = '-03:00'");
        }
    } else {
        // En cas de perte de connexion, tenter de la recréer
        $conn = @new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
        if (!$conn->connect_error) {
            $conn->set_charset("utf8mb4");
            $conn->query("SET time_zone = '-03:00'");
        }
    }
}

// 🔄 Auto-synchronisation quotidienne (vérification à minuit)
$today = date('Y-m-d');
$logFile = __DIR__ . '/time_guard.log';

// On ne resynchronise qu’une fois par jour
$lastSync = @file_get_contents($logFile);
if ($lastSync !== $today) {
    @file_put_contents($logFile, $today);
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->query("SET time_zone = '-03:00'");
    }
    // Journalisation facultative
    file_put_contents(__DIR__ . '/time_guard.log', "Sync ok : $today à " . date('H:i:s') . "\n", FILE_APPEND);
}
?>
