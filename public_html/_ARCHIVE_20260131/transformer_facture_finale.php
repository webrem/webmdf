<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Accès refusé");
}

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) die("Erreur DB");

/* ============================
   PARAMÈTRE
   ============================ */
$deviceRef = trim($_GET['ref'] ?? '');
if ($deviceRef === '') {
    die("Référence manquante");
}

/* ============================
   VÉRIFIER SI FACTURE EXISTE DÉJÀ
   ============================ */
$refFinale = "FACT-" . $deviceRef;

$check = $conn->prepare("
    SELECT id FROM ventes_historique
    WHERE ref_vente = ? AND type = 'facture_finale'
    LIMIT 1
");
$check->bind_param("s", $refFinale);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Facture finale déjà créée");
}
$check->close();

/* ============================
   RÉCUPÉRER DONNÉES DEVICE
   ============================ */
$stmtDev = $conn->prepare("
    SELECT model, price_repair, price_diagnostic, client_name
    FROM devices
    WHERE ref = ?
    LIMIT 1
");
$stmtDev->bind_param("s", $deviceRef);
$stmtDev->execute();
$stmtDev->bind_result($model, $priceRepair, $priceDiag, $clientNom);

if (!$stmtDev->fetch()) {
    die("Réparation introuvable");
}
$stmtDev->close();

$totalReparation = (float)$priceRepair + (float)$priceDiag;

/* ============================
   TOTAL DES ACOMPTES
   ============================ */
$stmtAcc = $conn->prepare("
    SELECT SUM(montant)
    FROM acomptes_devices
    WHERE device_ref = ?
");
$stmtAcc->bind_param("s", $deviceRef);
$stmtAcc->execute();
$stmtAcc->bind_result($totalAcomptes);
$stmtAcc->fetch();
$stmtAcc->close();

$totalAcomptes = (float)$totalAcomptes;
$resteAPayer   = max(0, $totalReparation - $totalAcomptes);

/* ============================
   CRÉATION FACTURE FINALE
   ============================ */
$designation = $model;
$type        = 'facture_finale';
$userNom     = $_SESSION['username'] ?? 'Admin';

$stmtInsert = $conn->prepare("
    INSERT INTO ventes_historique
    (ref_vente, designation, prix_total, client_nom, vendeur, mode_paiement, type, date_vente)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$modePaiement = ($resteAPayer <= 0) ? 'complet' : 'reste';

$stmtInsert->bind_param(
    "ssdssss",
    $refFinale,
    $designation,
    $totalReparation,
    $clientNom,
    $userNom,
    $modePaiement,
    $type
);
$stmtInsert->execute();
$stmtInsert->close();

/* ============================
   REDIRECTION
   ============================ */
header("Location: converti_pos.php?ref=" . urlencode($refFinale));
exit;
