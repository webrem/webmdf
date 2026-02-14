<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

// ✅ Vérif session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ Connexion MySQL
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);

// ✅ Données du formulaire
$commande_id = (int)($_POST['commande_id'] ?? 0);

// 🔥 Si aucun réparateur choisi → utilisateur courant
$reparateur_id = isset($_POST['reparateur_id']) && (int)$_POST['reparateur_id'] > 0
    ? (int)$_POST['reparateur_id']
    : (int)($_SESSION['user_id'] ?? 0);

if ($commande_id <= 0 || $reparateur_id <= 0) {
    header("Location: commandes.php?msg=acc_err");
    exit;
}


// 🔹 Récupère la commande
$stmt = $conn->prepare("SELECT * FROM historiques WHERE id=? AND statut='commande'");
$stmt->bind_param("i", $commande_id);
$stmt->execute();
$commande = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$commande) {
    die("❌ Commande introuvable ou déjà transférée.");
}

// 🔹 Récupère le nom du réparateur
$stmtUser = $conn->prepare("SELECT username FROM users WHERE id=?");
$stmtUser->bind_param("i", $reparateur_id);
$stmtUser->execute();
$stmtUser->bind_result($nomReparateur);
$stmtUser->fetch();
$stmtUser->close();

if (empty($nomReparateur)) {
    header("Location: commandes.php?msg=acc_err");
    exit;
}

$conn->begin_transaction();

try {
    // 🔧 Crée un identifiant unique pour la réparation
    $ref = "REP-" . strtoupper(substr(md5(uniqid()), 0, 8));

    // ✅ Ajout colonne traçabilité si elle n’existe pas
    $conn->query("ALTER TABLE devices ADD COLUMN IF NOT EXISTS origin_commande_id INT DEFAULT NULL");
    $conn->query("ALTER TABLE devices ADD COLUMN IF NOT EXISTS reparateur VARCHAR(100) DEFAULT NULL");

    // 🔹 Création de la réparation dans 'devices'
    $stmt = $conn->prepare("
        INSERT INTO devices (
            ref, client_name, client_phone, model, problem, 
            price_repair, price_diagnostic, status, created_at, origin_commande_id, reparateur
        ) VALUES (?, ?, ?, ?, ?, ?, 0, 'En cours', NOW(), ?, ?)
    ");

    $model   = $commande['piece'];
    $problem = "Commande transférée (#{$commande_id}) - Fournisseur : " . $commande['fournisseur'];
    $prix    = (float)$commande['prix_final'];

    $stmt->bind_param(
        "ssssddis",
        $ref,
        $commande['client_nom'],
        $commande['client_tel'],
        $model,
        $problem,
        $prix,
        $commande_id,
        $nomReparateur
    );
    $stmt->execute();
    $stmt->close();

    // 🔹 Transfert des acomptes associés
    $accs = $conn->prepare("SELECT montant, mode_paiement, date_versement, user_nom FROM acomptes_commandes WHERE commande_id=?");
    $accs->bind_param("i", $commande_id);
    $accs->execute();
    $accRes = $accs->get_result();

    while ($acc = $accRes->fetch_assoc()) {
        $stmt = $conn->prepare("
            INSERT INTO acomptes_devices (device_ref, montant, mode_paiement, date_versement, user_nom)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sdsss", $ref, $acc['montant'], $acc['mode_paiement'], $acc['date_versement'], $acc['user_nom']);
        $stmt->execute();
        $stmt->close();
    }
    $accs->close();

    // 🔹 Mise à jour de la commande d’origine
    $stmt = $conn->prepare("UPDATE historiques SET statut='transferee', reparateur=? WHERE id=?");
    $stmt->bind_param("si", $nomReparateur, $commande_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // ✅ Redirection finale avec message de succès
    header("Location: commandes.php?msg=transfert_ok");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Erreur transfert : " . $e->getMessage());
}
?>
