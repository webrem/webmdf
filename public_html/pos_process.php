<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// pos_process.php
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Non authentifié']); exit; }

$CURRENT_USER_ID = (int)$_SESSION['user_id'];
$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Erreur DB']); exit; }
$conn->set_charset('utf8mb4');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['items']) || !is_array($input['items'])) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Données invalides']); exit;
}

// Determine column names for qty and price
$cols = [];
$resCols = $conn->query("SHOW COLUMNS FROM stock_articles");
if ($resCols) { while($r=$resCols->fetch_assoc()) $cols[] = $r['Field']; }
$qtyCol = in_array('quantite',$cols,true) ? 'quantite' : (in_array('quantity',$cols,true)?'quantity':null);
$priceCol = null;
foreach (['prix_vente','price','sale_price','prix_achat','prix'] as $c) if (in_array($c,$cols,true)) { $priceCol=$c; break; }
if ($priceCol === null) $priceCol = $cols[0];

$ALLOW_NEGATIVE_SALES = false;

$client_id = isset($input['client_id']) ? (int)$input['client_id'] : null;
$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'espèces';
$items = $input['items'];

try {
    $conn->begin_transaction();

    // compute totals
    $total_ht = 0.0;
    foreach ($items as $it) {
        $qty = (int)$it['qty'];
        $price_unit = (float)$it['price_unit'];
        $total_ht += $qty * $price_unit;
    }
    $total_ttc = $total_ht;

    // generate ref
    $ref = 'SALE-'.date('YmdHis').'-'.substr(bin2hex(random_bytes(3)),0,6);

    $stmt = $conn->prepare("INSERT INTO sales (ref, user_id, client_id, total_ht, total_ttc, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) throw new Exception('Prepare sales failed: '.$conn->error);
    $stmt->bind_param("siidds", $ref, $CURRENT_USER_ID, $client_id, $total_ht, $total_ttc, $payment_method);
    if (!$stmt->execute()) throw new Exception('Insert sale failed: '.$stmt->error);
    $sale_id = $stmt->insert_id;
    $stmt->close();

    // prepare stmts
    $stmtInsertItem = $conn->prepare("INSERT INTO sale_items (sale_id, article_id, qty, price_unit, total) VALUES (?, ?, ?, ?, ?)");
    $stmtSelectStock = $conn->prepare("SELECT ".($qtyCol?$qtyCol:'quantite')." AS quantite, designation FROM stock_articles WHERE id = ? FOR UPDATE");
    $stmtUpdateStock = $conn->prepare("UPDATE stock_articles SET ".($qtyCol?$qtyCol:'quantite')." = ".($qtyCol?$qtyCol:'quantite')." - ? WHERE id = ?");
    $stmtInsertMovement = $conn->prepare("INSERT INTO stock_movements (article_id, type, qty_change, reference, user_id, note, created_at) VALUES (?, 'sale', ?, ?, ?, ?, NOW())");

    foreach ($items as $it) {
        $article_id = (int)$it['article_id'];
        $qty = (int)$it['qty'];
        $price_unit = (float)$it['price_unit'];
        $total = round($qty * $price_unit, 2);

        $stmtSelectStock->bind_param("i", $article_id);
        $stmtSelectStock->execute();
        $res = $stmtSelectStock->get_result();
        if ($res->num_rows == 0) throw new Exception("Article introuvable id={$article_id}");
        $row = $res->fetch_assoc();
        $current_qty = (int)$row['quantite'];
        $designation = $row['designation'];

        if (!$ALLOW_NEGATIVE_SALES && ($current_qty - $qty) < 0) {
            throw new Exception("Stock insuffisant pour {$designation} (id={$article_id}). Stock: {$current_qty}, demandé: {$qty}");
        }

        // insert sale item
        $stmtInsertItem->bind_param("iiidd", $sale_id, $article_id, $qty, $price_unit, $total);
        if (!$stmtInsertItem->execute()) throw new Exception("Insert sale_item failed: ".$stmtInsertItem->error);

        // update stock
        $stmtUpdateStock->bind_param("ii", $qty, $article_id);
        if (!$stmtUpdateStock->execute()) throw new Exception("Update stock failed: ".$stmtUpdateStock->error);

        // insert movement (if table exists)
        if ($conn->query("SHOW TABLES LIKE 'stock_movements'")->num_rows > 0) {
            $note = "Vente ref $ref";
            $stmtInsertMovement->bind_param("iiiss", $article_id, $qty, $ref, $CURRENT_USER_ID, $note);
            $stmtInsertMovement->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success'=>true,'sale_id'=>$sale_id,'ref'=>$ref]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
