<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../sync_time.php';

// Sécurité
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode invalide']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur DB']);
    exit;
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");

// 1️⃣ récupérer la référence UNIQUE de l’acompte
$stmt = $conn->prepare("
    SELECT ref_acompte 
    FROM acomptes_devices 
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($refAcompte);
$stmt->fetch();
$stmt->close();

if (!$refAcompte) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Acompte introuvable']);
    exit;
}

// 2️⃣ supprimer UNIQUEMENT l’acompte
$stmtDel = $conn->prepare("
    DELETE FROM acomptes_devices 
    WHERE id = ?
    LIMIT 1
");
$stmtDel->bind_param("i", $id);
$stmtDel->execute();
$stmtDel->close();

// 3️⃣ supprimer UNIQUEMENT le ticket lié
$stmtVH = $conn->prepare("
    DELETE FROM ventes_historique
    WHERE ref_vente = ?
    AND type = 'acompte'
    LIMIT 1
");
$stmtVH->bind_param("s", $refAcompte);
$stmtVH->execute();
$stmtVH->close();

$conn->close();

echo json_encode(["success"=>true]);
exit;
