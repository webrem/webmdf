<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__.'/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

/* =========================
   STATS GLOBALES
   ========================= */

// Total étiquettes imprimées
$totalLabels = $pdo->query("
    SELECT COALESCE(SUM(total_labels), 0)
    FROM print_labels_history
")->fetchColumn();

// Total impressions
$totalPrints = $pdo->query("
    SELECT COUNT(*)
    FROM print_labels_history
")->fetchColumn();

// =========================
// ÉTIQUETTES PAR JOUR (30j)
// =========================
$perDay = $pdo->query("
    SELECT DATE(created_at) as day, SUM(total_labels) as total
    FROM print_labels_history
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day DESC
")->fetchAll(PDO::FETCH_ASSOC);

// =========================
// PAR UTILISATEUR
// =========================
$perUser = $pdo->query("
    SELECT u.username, SUM(h.total_labels) as total
    FROM print_labels_history h
    LEFT JOIN users u ON u.id = h.user_id
    GROUP BY h.user_id
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Statistiques étiquettes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">

<h1 class="mb-4">📊 Statistiques d’impression des étiquettes</h1>

<!-- ======== CARTES GLOBALES ======== -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">Étiquettes imprimées</h6>
        <div class="fs-2 fw-bold text-primary"><?= (int)$totalLabels ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">Impressions</h6>
        <div class="fs-2 fw-bold"><?= (int)$totalPrints ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">Moyenne / impression</h6>
        <div class="fs-2 fw-bold text-success">
          <?= $totalPrints > 0 ? round($totalLabels / $totalPrints, 1) : 0 ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ======== PAR JOUR ======== -->
<div class="card shadow-sm mb-4">
  <div class="card-header">
    📅 Étiquettes imprimées (30 derniers jours)
  </div>
  <div class="card-body p-0">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>Date</th>
          <th>Total étiquettes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($perDay as $d): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($d['day'])) ?></td>
          <td><?= (int)$d['total'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($perDay)): ?>
        <tr>
          <td colspan="2" class="text-center text-muted py-3">
            Aucune donnée sur la période
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ======== PAR UTILISATEUR ======== -->
<div class="card shadow-sm">
  <div class="card-header">
    🧑 Impression par utilisateur
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Utilisateur</th>
          <th>Total étiquettes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($perUser as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['username'] ?? '—') ?></td>
          <td><?= (int)$u['total'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($perUser)): ?>
        <tr>
          <td colspan="2" class="text-center text-muted py-3">
            Aucun historique utilisateur
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
