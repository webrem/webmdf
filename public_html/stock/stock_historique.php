<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once 'stock_functions.php';

$hist = $pdo->query("
    SELECT s.*, p.nom 
    FROM stock_mouvements s
    JOIN produits p ON p.id = s.article_id
    ORDER BY s.created_at DESC
")->fetchAll();
?>

<h2>📜 Historique du stock</h2>

<table class="table">
<tr>
 <th>Date</th>
 <th>Article</th>
 <th>Type</th>
 <th>Qté</th>
 <th>Prix</th>
 <th>Motif</th>
 <th>Utilisateur</th>
</tr>
<?php foreach ($hist as $h): ?>
<tr>
 <td><?= $h['created_at'] ?></td>
 <td><?= htmlspecialchars($h['nom']) ?></td>
 <td><?= strtoupper($h['type']) ?></td>
 <td><?= $h['qte'] ?></td>
 <td><?= number_format($h['prix_unitaire'],2) ?> €</td>
 <td><?= $h['motif'] ?></td>
 <td><?= $h['user'] ?></td>
</tr>
<?php endforeach; ?>
</table>
