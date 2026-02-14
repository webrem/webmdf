<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// generate_ticket_sale.php
include 'header.php';
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_GET['sale_id'])) { echo "Sale id manquant"; exit; }
$sale_id = (int)$_GET['sale_id'];

$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("DB error");
$conn->set_charset("utf8mb4");

// fetch sale
$stmt = $conn->prepare("SELECT s.*, u.username FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?");
$stmt->bind_param("i",$sale_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows==0) { echo "Vente non trouvée"; exit; }
$sale = $res->fetch_assoc();
$stmt->close();

// items
$stmt = $conn->prepare("SELECT si.*, a.designation FROM sale_items si LEFT JOIN stock_articles a ON a.id = si.article_id WHERE si.sale_id = ?");
$stmt->bind_param("i",$sale_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Ticket <?=$sale['ref']?></title>
<style>
body { font-family: monospace; width: 280px; margin:0; padding:10px; }
h2, h3 { margin:0; padding:0; text-align:center; }
.line { border-top:1px dashed #000; margin:8px 0; }
table { width:100%; font-size:12px; border-collapse:collapse; }
td { vertical-align:top; }
.right { text-align:right; }
</style>
</head><body>
  <h2>R.E.Mobiles</h2>
  <div style="text-align:center;">104B Av. Général de Gaulle<br/>97300 Cayenne</div>
  <div class="line"></div>
  <div>Ref: <?=htmlspecialchars($sale['ref'])?></div>
  <div>Vendeur: <?=htmlspecialchars($sale['username'] ?? 'N/A')?></div>
  <div>Date: <?=htmlspecialchars($sale['created_at'])?></div>
  <div class="line"></div>
  <table>
    <?php foreach($items as $it): ?>
    <tr>
      <td><?=htmlspecialchars($it['designation'])?></td>
      <td class="right"><?=htmlspecialchars($it['qty'])?> x <?=number_format($it['price_unit'],2)?>€</td>
    </tr>
    <?php endforeach; ?>
  </table>
  <div class="line"></div>
  <table>
    <tr><td>Total TTC</td><td class="right"><?=number_format($sale['total_ttc'],2)?>€</td></tr>
    <tr><td>Payé</td><td class="right"><?=htmlspecialchars($sale['payment_method'])?></td></tr>
  </table>
  <div class="line"></div>
  <div style="text-align:center;">Merci pour votre confiance !</div>
</body></html>
