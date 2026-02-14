<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 require 'db.php';
$rows = $pdo->query("
SELECT m.*, a.designation
FROM stock_movements m
JOIN stock_articles a ON a.id = m.article_id
ORDER BY m.created_at DESC
")->fetchAll();
?>
<h2>📊 Historique des mouvements</h2>
<table border="1">
<tr><th>Date</th><th>Produit</th><th>Type</th><th>Qté</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?= $r['created_at'] ?></td>
<td><?= $r['designation'] ?></td>
<td><?= $r['type'] ?></td>
<td><?= $r['quantite'] ?></td>
</tr>
<?php endforeach; ?>
</table>
