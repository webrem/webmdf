<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// stock_audit.php
include 'header.php';
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die('DB error');
$conn->set_charset('utf8mb4');

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$sql = "SELECT sm.*, a.reference AS art_ref, a.designation AS art_name, u.username FROM stock_movements sm LEFT JOIN stock_articles a ON a.id = sm.article_id LEFT JOIN users u ON u.id = sm.user_id WHERE 1=1";
if ($filter) {
    $f = $conn->real_escape_string($filter);
    $sql .= " AND (a.reference LIKE '%".$f."%' OR a.designation LIKE '%".$f."%')";
}
$sql .= " ORDER BY sm.created_at DESC LIMIT 500";
$res = $conn->query($sql);
?>
<?php include 'header.php'; ?>
<h2 class="text-center mb-4">📊 Tableau de bord — Audit stock</h2>
<div class="container py-3">
  <form class="mb-3"><input name="filter" class="form-control" placeholder="référence ou nom" value="<?=htmlspecialchars($filter)?>"></form>
  <table class="table table-sm table-striped">
    <thead><tr><th>Date</th><th>Article</th><th>Type</th><th>Qty</th><th>Ref</th><th>User</th><th>Note</th></tr></thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?=htmlspecialchars($row['created_at'])?></td>
        <td><?=htmlspecialchars($row['art_ref'].' — '.$row['art_name'])?></td>
        <td><?=htmlspecialchars($row['type'])?></td>
        <td><?=htmlspecialchars($row['qty_change'])?></td>
        <td><?=htmlspecialchars($row['reference'])?></td>
        <td><?=htmlspecialchars($row['username'])?></td>
        <td><?=htmlspecialchars($row['note'])?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
