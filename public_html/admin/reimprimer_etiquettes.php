<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__.'/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

$stmt = $pdo->query("
    SELECT h.id, h.filename, h.total_labels, h.created_at, u.username
    FROM print_labels_history h
    LEFT JOIN users u ON u.id = h.user_id
    ORDER BY h.created_at DESC
");
$prints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Réimpression des étiquettes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">

<h1 class="mb-4">🖨️ Historique des impressions d’étiquettes</h1>

<div class="card shadow-sm">
  <div class="card-body p-0">

    <table class="table table-striped table-hover mb-0">
      <thead class="table-dark">
        <tr>
          <th>Date</th>
          <th>Utilisateur</th>
          <th>Étiquettes</th>
          <th>Fichier</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>

        <?php if (empty($prints)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              Aucune impression enregistrée
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($prints as $p): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
            <td><?= htmlspecialchars($p['username'] ?? '—') ?></td>
            <td><?= (int)$p['total_labels'] ?></td>
            <td><?= htmlspecialchars($p['filename']) ?></td>
            <td>
              <a href="../archives/etiquettes/<?= urlencode($p['filename']) ?>"
                 target="_blank"
                 class="btn btn-sm btn-primary">
                🔁 Réimprimer
              </a>
            </td>
          </tr>
        <?php endforeach; ?>

      </tbody>
    </table>

  </div>
</div>

</body>
</html>
