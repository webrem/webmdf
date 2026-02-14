<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset("utf8mb4");

$now     = new DateTime();
$today   = $now->format('Y-m-d');
$hour    = (int)$now->format('H');

// Table caisse
$check = $conn->query("SHOW TABLES LIKE 'caisse_jour'");
if ($check->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}

// Dernière caisse
$q = $conn->query("SELECT * FROM caisse_jour ORDER BY date_caisse DESC LIMIT 1");
$caisse = $q->fetch_assoc();

/* =========================
   1️⃣ CAISSE NON FERMÉE
   ========================= */
if ($caisse && empty($caisse['heure_fermeture'])) {

    // lendemain matin OU après 23h
    if ($caisse['date_caisse'] < $today || $hour >= 23) {
        header("Location: fermeture_caisse.php?force=1");
        exit;
    }
}

/* =========================
   2️⃣ AVANT 08H
   ========================= */
if ($hour < 8) {
    header("Location: dashboard.php");
    exit;
}

/* =========================
   3️⃣ OUVERTURE DU JOUR
   ========================= */
$stmt = $conn->prepare("
    SELECT id FROM caisse_jour
    WHERE date_caisse = ? AND heure_ouverture IS NOT NULL
    LIMIT 1
");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: choix_ouverture_caisse.php");
    exit;
}

/* =========================
   4️⃣ TOUT EST OK
   ========================= */
header("Location: dashboard.php");
exit;
