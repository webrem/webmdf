<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique

$total = 0; $i = 0;

if (empty($_SESSION['panier'])) {
  echo '<p class="text-muted mt-3">Aucun article ajouté.</p>';
  exit;
}
?>
<div class="table-responsive mt-3">
  <table class="table table-dark table-striped align-middle text-center">
    <thead>
      <tr>
        <th>#</th>
        <th>Référence</th>
        <th>Désignation</th>
        <th>Qté</th>
        <th>PU</th>
        <th>Total</th>
        <th>❌</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($_SESSION['panier'] as $index => $item):
        $t = $item['prix'] * $item['quantite']; $total += $t; ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td><?= htmlspecialchars($item['reference']) ?></td>
          <td class="text-start"><?= htmlspecialchars($item['designation']) ?></td>
          <td><?= $item['quantite'] ?></td>
          <td><?= number_format($item['prix'], 2, ',', ' ') ?></td>
          <td class="fw-bold text-success"><?= number_format($t, 2, ',', ' ') ?></td>
          <td><button class="btn btn-danger btn-sm btn-supprimer" data-index="<?= $index ?>">✖</button></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="5" class="text-end">TOTAL (€)</th>
        <th colspan="2" class="text-warning fs-5"><?= number_format($total, 2, ',', ' ') ?></th>
      </tr>
    </tfoot>
  </table>
</div>
