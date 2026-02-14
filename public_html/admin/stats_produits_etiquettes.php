<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__.'/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

$products = $pdo->query("
    SELECT
        product_id,
        designation,
        SUM(quantity) AS total_labels,
        COUNT(DISTINCT history_id) AS print_count
    FROM print_labels_items
    GROUP BY product_id, designation
    ORDER BY total_labels DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Statistiques par produit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">

<h1 class="mb-4">📦 Statistiques d’étiquettes par produit</h1>

<div class="card shadow-sm">
  <div class="card-body p-0">

    <table class="table table-striped table-hover mb-0">
      <thead class="table-dark">
        <tr>
          <th>Produit</th>
          <th>Total étiquettes</th>
          <th>Impressions</th>
        </tr>
      </thead>
      <tbody>

        <?php foreach ($products as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['designation']) ?></td>
          <td><?= (int)$p['total_labels'] ?></td>
          <td><?= (int)$p['print_count'] ?></td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
        <tr>
          <td colspan="3" class="text-center text-muted py-4">
            Aucun produit imprimé pour le moment
          </td>
        </tr>
        <?php endif; ?>

      </tbody>
    </table>

  </div>
</div>

</body>
</html>
