<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Connexion DB
$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);

if ($conn->connect_error) {
    die("Erreur DB : " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
date_default_timezone_set("America/Cayenne");

// Données formulaire
$ref     = trim($_POST['ref'] ?? '');
$montant = (float)($_POST['acompte'] ?? 0);
$mode    = trim($_POST['mode'] ?? '');
$user    = $_SESSION['username'] ?? $_SESSION['role'];

// Sécurité
if ($ref === '' || $montant <= 0 || $mode === '') {
    header("Location: devices_list.php?msg=acc_err");
    exit;
}

// 1️⃣ Insertion de l’acompte (SANS référence unique pour l’instant)
$stmt = $conn->prepare("
    INSERT INTO acomptes_devices 
    (device_ref, montant, mode_paiement, date_versement, user_nom)
    VALUES (?, ?, ?, NOW(), ?)
");
$stmt->bind_param("sdss", $ref, $montant, $mode, $user);

if (!$stmt->execute()) {
    $stmt->close();
    header("Location: devices_list.php?msg=acc_err");
    exit;
}

// 2️⃣ Récupération de l’ID unique de l’acompte
$acompte_id = $stmt->insert_id;
$stmt->close();

// 3️⃣ Génération d’une RÉFÉRENCE UNIQUE d’acompte
$refAcompte = "DEV-AC-" . $ref . "-" . $acompte_id;

// 4️⃣ Mise à jour de l’acompte avec sa référence unique
$upd = $conn->prepare("
    UPDATE acomptes_devices 
    SET ref_acompte = ?
    WHERE id = ?
");
$upd->bind_param("si", $refAcompte, $acompte_id);
$upd->execute();
$upd->close();

// 5️⃣ Redirection OK
header("Location: devices_list.php?msg=acc_ok");
exit;
