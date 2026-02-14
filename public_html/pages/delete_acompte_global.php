<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../sync_time.php';

/* ========= LOG ========= */
function logA($msg, $data = []) {
    $line = '['.date('Y-m-d H:i:s')."] $msg";
    if ($data) $line .= ' | '.json_encode($data, JSON_UNESCAPED_UNICODE);
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'].'/logs/delete_acompte.log',
        $line.PHP_EOL,
        FILE_APPEND
    );
}

/* ========= SÉCURITÉ ========= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    logA('REFUS ACCÈS', $_SESSION);
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Accès refusé']);
    exit;
}

/* ========= REF ========= */
$ref = trim($_POST['ref'] ?? '');
logA('REF REÇUE', ['ref'=>$ref]);

if ($ref === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Référence manquante']);
    exit;
}

/* ========= DB ========= */
$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
if ($conn->connect_error) {
    logA('DB ERROR', ['err'=>$conn->connect_error]);
    http_response_code(500);
    exit;
}
$conn->set_charset('utf8mb4');
$conn->begin_transaction();

try {

    /* 1️⃣ ventes_historique */
    $stmt = $conn->prepare("
        DELETE FROM ventes_historique
        WHERE LOWER(type)='acompte' AND ref_vente=?
    ");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    logA('DELETE ventes_historique', ['rows'=>$stmt->affected_rows]);
    $stmt->close();

    /* 2️⃣ acomptes_devices */
    if (preg_match('/^DEV-AC-([A-Z0-9]+)-([0-9]+)$/', $ref, $m)) {
        $stmt = $conn->prepare("
            DELETE FROM acomptes_devices
            WHERE device_ref=? AND id=?
        ");
        $stmt->bind_param("si", $m[1], $m[2]);
        $stmt->execute();
        logA('DELETE acomptes_devices', ['rows'=>$stmt->affected_rows]);
        $stmt->close();
    }

    $conn->commit();
    logA('SUCCESS');

    echo json_encode(['success'=>true]);

} catch (Throwable $e) {
    $conn->rollback();
    logA('EXCEPTION', ['msg'=>$e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur suppression']);
}

$conn->close();
