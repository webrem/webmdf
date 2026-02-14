<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 require 'db.php';
$alertes = $pdo->query("
SELECT * FROM stock_articles WHERE quantite <= seuil
")->fetchAll();
?>
<h2>⚠️ Alertes stock faible</h2>
<?php foreach($alertes as $a): ?>
<p>🔴 <?= $a['designation'] ?> (<?= $a['quantite'] ?> restants)</p>
<?php endforeach; ?>
