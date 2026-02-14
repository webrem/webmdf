<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) {
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

$userNom = $_SESSION['username'] ?? 'Utilisateur';

// Données formulaire
$commande_id = (int)($_POST['id'] ?? 0);
$montant     = (float)($_POST['acompte'] ?? 0);
$mode        = trim($_POST['mode'] ?? '');

// Sécurité
if ($commande_id <= 0 || $montant <= 0 || $mode === '') {
    header("Location: commandes.php?msg=acc_err");
    exit;
}

// Vérifie la commande
$stmt = $conn->prepare("
    SELECT client_nom, prix_final
    FROM historiques
    WHERE id = ? AND statut = 'commande'
");
$stmt->bind_param("i", $commande_id);
$stmt->execute();
$stmt->bind_result($client, $total);

if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: commandes.php?msg=acc_err");
    exit;
}
$stmt->close();

// Table acomptes commandes (sécurisée)
$conn->query("
    CREATE TABLE IF NOT EXISTS acomptes_commandes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        commande_id INT NOT NULL,
        ref_acompte VARCHAR(50) NOT NULL,
        montant DECIMAL(10,2) NOT NULL,
        mode_paiement VARCHAR(50) NOT NULL,
        date_versement DATETIME DEFAULT CURRENT_TIMESTAMP,
        user_nom VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 1️⃣ Insertion de l’acompte (sans ref unique)
$stmt2 = $conn->prepare("
    INSERT INTO acomptes_commandes
    (commande_id, montant, mode_paiement, date_versement, user_nom)
    VALUES (?, ?, ?, NOW(), ?)
");
$stmt2->bind_param("idss", $commande_id, $montant, $mode, $userNom);

if (!$stmt2->execute()) {
    $stmt2->close();
    header("Location: commandes.php?msg=acc_err");
    exit;
}

// 2️⃣ ID unique
$acompte_id = $stmt2->insert_id;
$stmt2->close();

// 3️⃣ Référence UNIQUE d’acompte
$refAcompte = "CMD-AC-" . $commande_id . "-" . $acompte_id;

// 4️⃣ Mise à jour référence
$upd = $conn->prepare("
    UPDATE acomptes_commandes
    SET ref_acompte = ?
    WHERE id = ?
");
$upd->bind_param("si", $refAcompte, $acompte_id);
$upd->execute();
$upd->close();

// 5️⃣ Historique ventes (1 acompte = 1 ref)
$designation = "Acompte sur commande #" . $commande_id;
$type = "acompte";

$stmtH = $conn->prepare("
    INSERT INTO ventes_historique
    (ref_vente, designation, prix_total, client_nom, vendeur, mode_paiement, type, date_vente)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmtH->bind_param(
    "ssdssss",
    $refAcompte,
    $designation,
    $montant,
    $client,
    $userNom,
    $mode,
    $type
);
$stmtH->execute();
$stmtH->close();

// OK
header("Location: commandes.php?msg=acc_ok");
exit;
