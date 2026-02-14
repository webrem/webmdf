<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

/* ===============================
   PAGE DE RETOUR INTELLIGENTE
   =============================== */
$redirect = $_SERVER['HTTP_REFERER'] ?? 'commandes.php';

/* ===============================
   SÉCURITÉ
   =============================== */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$redirect}?msg=acc_err");
    exit;
}

/* ===============================
   DB
   =============================== */
$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);

if ($conn->connect_error) {
    header("Location: {$redirect}?msg=db_err");
    exit;
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");

/* ===============================
   ID (GET)
   =============================== */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: {$redirect}?msg=acc_err");
    exit;
}

/* ===============================
   1️⃣ RÉCUP REF ACOMPTE
   =============================== */
$stmt = $conn->prepare(
    "SELECT ref_acompte
     FROM acomptes_commandes
     WHERE id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($refAcompte);
$stmt->fetch();
$stmt->close();

if (!$refAcompte) {
    header("Location: {$redirect}?msg=acc_err");
    exit;
}

/* ===============================
   2️⃣ SUPPRESSION ACOMPTE
   =============================== */
$stmt = $conn->prepare(
    "DELETE FROM acomptes_commandes
     WHERE id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* ===============================
   3️⃣ SUPPRESSION TICKET
   =============================== */
$stmt = $conn->prepare(
    "DELETE FROM ventes_historique
     WHERE ref_vente = ?
     AND type = 'acompte'
     LIMIT 1"
);
$stmt->bind_param("s", $refAcompte);
$stmt->execute();
$stmt->close();

$conn->close();

/* ===============================
   RETOUR À LA PAGE D’ORIGINE
   =============================== */
header("Location: {$redirect}?msg=acc_del_ok");
exit;
