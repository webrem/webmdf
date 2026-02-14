<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/sync_time.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "msg" => "Non connecté"]);
    exit;
}

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "msg" => $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8mb4');

$montant = floatval($_POST['montant'] ?? 0);
$motif   = trim($_POST['motif'] ?? '');

if ($montant <= 0 || $motif === '') {
    echo json_encode(["status" => "error", "msg" => "Montant ou motif invalide"]);
    exit;
}

$montantNegatif = -abs($montant);
$vendeur = $_SESSION['username'] ?? 'admin';
$ref = "RET-" . date("Ymd-His");
$designation = "Retrait - " . $motif;

/* TEST PREPARE */
$sql = "
INSERT INTO ventes_historique
(date_vente, ref_vente, designation, prix_total, client_nom, vendeur, mode_paiement, type)
VALUES (NOW(), ?, ?, ?, 'Caisse', ?, 'Espèces', 'retrait')
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg" => "PREPARE FAIL",
        "sql_error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param("ssds", $ref, $designation, $montantNegatif, $vendeur);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "msg" => "EXECUTE FAIL",
        "sql_error" => $stmt->error
    ]);
    exit;
}

$stmt->close();

echo json_encode(["status" => "ok"]);
exit;
