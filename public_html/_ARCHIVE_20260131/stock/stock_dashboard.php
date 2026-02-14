<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';


// ✅ Debug (à enlever quand tout est OK)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

date_default_timezone_set('America/Cayenne');

/* ---------------- KPI stock ---------------- */
$stats = null;
try {
    $stats = $pdo->query("SELECT * FROM stock_stats LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $stats = null;
}

if (!$stats) {
    $rows = [];
    try {
        $rows = $pdo->query("SELECT prix_vente, quantite FROM stock_articles")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = [];
    }

    $total = count($rows);
    $valeur = 0.0;
    $faible = 0;
    $rupture = 0;

    foreach ($rows as $r) {
        $q = (int)($r['quantite'] ?? 0);
        $p = (float)($r['prix_vente'] ?? 0);
        $valeur += $p * $q;
        if ($q === 0) $rupture++;
        elseif ($q <= 3) $faible++;
    }

    $stats = [
        'total_produits' => $total,
        'valeur_stock'   => $valeur,
        'stock_faible'   => $faible,
        'rupture'        => $rupture,
    ];
}

/* ---------------- TOP 10 produits (valeur stock) ---------------- */
$topStock = [];
try {
    $topStock = $pdo->query("
        SELECT designation, (prix_vente * quantite) AS valeur
        FROM stock_articles
        ORDER BY valeur DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $topStock = [];
}

/* ---------------- TOP 5 réparations (TOUTE LA PERIODE) ---------------- */
$topRepairs = [];
$repairsInfo = ['ok' => false, 'message' => ''];

// test model_aliases
$hasAliases = false;
try {
    $pdo->query("SELECT 1 FROM model_aliases LIMIT 1");
    $hasAliases = true;
} catch (Throwable $e) {
    $hasAliases = false;
}

try {
    $minDate = $pdo->query("SELECT MIN(created_at) FROM repairs")->fetchColumn();
    if (!$minDate) {
        $minDate = '1970-01-01 00:00:00';
    }

    if ($hasAliases) {
        $sql = "
            SELECT
                COALESCE(
                    (
                      SELECT ma.canonical
                      FROM model_aliases ma
                      WHERE LOWER(r.appareil) LIKE CONCAT('%', LOWER(ma.alias), '%')
                      ORDER BY CHAR_LENGTH(ma.alias) DESC
                      LIMIT 1
                    ),
                    r.appareil
                ) AS modele,
                COUNT(*) AS ventes
            FROM repairs r
            WHERE r.created_at >= :start
            GROUP BY modele
            ORDER BY ventes DESC
            LIMIT 5
        ";
    } else {
        $sql = "
            SELECT r.appareil AS modele, COUNT(*) AS ventes
            FROM repairs r
            WHERE r.created_at >= :start
            GROUP BY r.appareil
            ORDER BY ventes DESC
            LIMIT 5
        ";
        $repairsInfo['message'] = "model_aliases introuvable: top basé sur appareil tel quel.";
    }

    $st = $pdo->prepare($sql);
    $st->execute([':start' => $minDate]);
    $topRepairs = $st->fetchAll(PDO::FETCH_ASSOC);

    $repairsInfo['ok'] = true;
    if (empty($topRepairs)) {
        $repairsInfo['message'] = "Aucune réparation enregistrée pour le moment.";
    }
} catch (Throwable $e) {
    $repairsInfo['ok'] = false;
    $repairsInfo['message'] = "Erreur TOP réparations: " . $e->getMessage();
}

/* ---------------- Alertes stock (10 lignes minimum) ---------------- */
$alerts = [];
$alertInfo = ['message' => ''];
$TARGET = 10;

// 1) Alertes (quantite <= seuil)
try {
    $alerts = $pdo->query("
        SELECT
          id,
          reference,
          designation,
          quantite,
          COALESCE(seuil_min, 3) AS seuil_min
        FROM stock_articles
        WHERE quantite <= COALESCE(seuil_min, 3)
        ORDER BY (quantite = 0) DESC, quantite ASC, designation ASC
        LIMIT $TARGET
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $alerts = $pdo->query("
        SELECT
          id,
          reference,
          designation,
          quantite,
          3 AS seuil_min
        FROM stock_articles
        WHERE quantite <= 3
        ORDER BY (quantite = 0) DESC, quantite ASC, designation ASC
        LIMIT $TARGET
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// 2) Compléter si < 10
if (count($alerts) < $TARGET) {
    $missing = $TARGET - count($alerts);
    $ids = array_map(fn($r) => (int)$r['id'], $alerts);

    $whereNotIn = '';
    if (!empty($ids)) {
        $whereNotIn = "AND id NOT IN (" . implode(',', $ids) . ")";
    }

    try {
        $more = $pdo->query("
            SELECT
              id,
              reference,
              designation,
              quantite,
              COALESCE(seuil_min, 3) AS seuil_min
            FROM stock_articles
            WHERE 1=1
              $whereNotIn
            ORDER BY quantite ASC, designation ASC
            LIMIT $missing
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $more = $pdo->query("
            SELECT
              id,
              reference,
              designation,
              quantite,
              3 AS seuil_min
            FROM stock_articles
            WHERE 1=1
              $whereNotIn
            ORDER BY quantite ASC, designation ASC
            LIMIT $missing
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    $alerts = array_merge($alerts, $more);
}

if (empty($alerts)) {
    $alertInfo['message'] = "Aucun produit trouvé dans le stock.";
}

/* ---------------- Data charts ---------------- */
$topStockLabels = array_map(fn($r)=> (string)($r['designation'] ?? ''), $topStock);
$topStockValues = array_map(fn($r)=> (float)($r['valeur'] ?? 0), $topStock);

$topRepLabels = array_map(fn($r)=> (string)($r['modele'] ?? ''), $topRepairs);
$topRepValues = array_map(fn($r)=> (int)($r['ventes'] ?? 0), $topRepairs);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard Stock — R.E.Mobiles</title>

<link rel="stylesheet" href="../assets/css/dashboard_stock.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>
<?php include '../header.php'; ?>

<div class="ds-shell">

  <div class="ds-topbar">
    <div class="ds-title">
      <div class="ds-badge"><i class="bi bi-clipboard-data"></i></div>
      <div>
        <h1>Dashboard Stock</h1>
        <p class="ds-subtitle">KPI + TOP produits + TOP réparations (toute la période)</p>
      </div>
    </div>

    <div class="ds-actions">
      <a class="btn btn-primary" href="add_stock.php"><i class="bi bi-plus-circle"></i> Nouveau produit</a>
      <a class="btn btn-soft" href="stock.php"><i class="bi bi-box-seam"></i> Liste stock</a>
      <a class="btn btn-soft" href="import_stock.php"><i class="bi bi-upload"></i> Import CSV</a>
      <a class="btn btn-soft" href="export_stock.php"><i class="bi bi-download"></i> Export CSV</a>
    </div>
  </div>

  <section class="ds-kpis">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="bi bi-grid-3x3-gap"></i></div>
      <div class="kpi-meta">
        <div class="kpi-label">Produits</div>
        <div class="kpi-value"><?= (int)$stats['total_produits'] ?></div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
      <div class="kpi-meta">
        <div class="kpi-label">Valeur stock</div>
        <div class="kpi-value"><?= number_format((float)$stats['valeur_stock'], 2, ',', ' ') ?> €</div>
      </div>
    </div>

    <div class="kpi-card warn">
      <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="kpi-meta">
        <div class="kpi-label">Stock faible</div>
        <div class="kpi-value"><?= (int)$stats['stock_faible'] ?></div>
      </div>
    </div>

    <div class="kpi-card danger">
      <div class="kpi-icon"><i class="bi bi-x-octagon"></i></div>
      <div class="kpi-meta">
        <div class="kpi-label">Ruptures</div>
        <div class="kpi-value"><?= (int)$stats['rupture'] ?></div>
      </div>
    </div>
  </section>

  <section class="ds-grid">

    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <i class="bi bi-bar-chart-line"></i>
          <span>Top produits (valeur stock)</span>
        </div>
        <div class="panel-hint">Top 10 — prix_vente × quantite</div>
      </div>
      <div class="panel-body">
        <canvas id="chartTopStock"></canvas>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <i class="bi bi-wrench-adjustable-circle"></i>
          <span>Top 5 téléphones (réparations)</span>
        </div>
        <div class="panel-hint">Basé sur repairs.appareil</div>
      </div>
      <div class="panel-body">
        <?php if (!$repairsInfo['ok']): ?>
          <div class="notice">
            <b>Top réparations indisponible.</b><br>
            <span><?= htmlspecialchars($repairsInfo['message']) ?></span>
          </div>
        <?php else: ?>
          <?php if (!empty($repairsInfo['message'])): ?>
            <div class="notice"><span><?= htmlspecialchars($repairsInfo['message']) ?></span></div>
          <?php endif; ?>
          <canvas id="chartTopRepairs"></canvas>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel panel-wide">
      <div class="panel-head">
        <div class="panel-title">
          <i class="bi bi-bell"></i>
          <span>Alertes stock</span>
        </div>
        <div class="panel-hint">Quantité ≤ seuil (Top 10 affiché)</div>
      </div>

      <div class="panel-body">
        <?php if (empty($alerts)): ?>
          <div class="empty">
            <i class="bi bi-check2-circle"></i>
            <div>
              <div class="empty-title">Aucune alerte</div>
              <div class="empty-sub"><?= htmlspecialchars($alertInfo['message']) ?></div>
            </div>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="ds-table">
              <thead>
                <tr>
                  <th>Référence</th>
                  <th>Produit</th>
                  <th class="t-center">Stock</th>
                  <th class="t-center">Seuil</th>
                  <th class="t-right">Statut</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($alerts as $a):
                $q = (int)($a['quantite'] ?? 0);
                $s = (int)($a['seuil_min'] ?? 3);

                if ($q === 0) { $status = 'Rupture'; $cls = 'badge-danger'; }
                elseif ($q <= $s) { $status = 'Faible'; $cls = 'badge-warn'; }
                else { $status = 'Low Faible'; $cls = 'badge-lowfaible'; }
              ?>
                <tr>
                  <td><?= htmlspecialchars($a['reference'] ?? '') ?></td>
                  <td><?= htmlspecialchars($a['designation'] ?? '') ?></td>
                  <td class="t-center"><b><?= $q ?></b></td>
                  <td class="t-center"><?= $s ?></td>
                  <td class="t-right"><span class="badge <?= $cls ?>"><?= $status ?></span></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </section>

  <footer class="ds-footer">
    <div class="ds-footer-left">
      <div class="foot-brand">R.E.Mobiles — Stock</div>
      <div class="foot-sub">Dashboard interne</div>
    </div>
    <div class="ds-footer-links">
      <a href="#" onclick="alert('FAQ à ajouter');return false;">FAQ</a>
      <a href="#" onclick="alert('Support à ajouter');return false;">Support</a>
      <a href="#" onclick="alert('Conditions à ajouter');return false;">Conditions</a>
    </div>
  </footer>

</div>

<script>
const topStockLabels = <?= json_encode($topStockLabels, JSON_UNESCAPED_UNICODE) ?>;
const topStockValues = <?= json_encode($topStockValues, JSON_UNESCAPED_UNICODE) ?>;

const topRepLabels   = <?= json_encode($topRepLabels, JSON_UNESCAPED_UNICODE) ?>;
const topRepValues   = <?= json_encode($topRepValues, JSON_UNESCAPED_UNICODE) ?>;

const gridColor = 'rgba(255,255,255,0.08)';
const tickColor = 'rgba(255,255,255,0.75)';

function euros(v){
  try { return new Intl.NumberFormat('fr-FR', { style:'currency', currency:'EUR' }).format(v); }
  catch(e){ return (Math.round(v*100)/100).toFixed(2) + ' €'; }
}

if (document.getElementById('chartTopStock')) {
  new Chart(document.getElementById('chartTopStock'), {
    type: 'bar',
    data: { labels: topStockLabels, datasets: [{ label: 'Valeur stock', data: topStockValues, borderWidth: 0, borderRadius: 10 }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display:false }, tooltip: { callbacks: { label: (ctx)=> ' ' + euros(ctx.parsed.y) } } },
      scales: {
        x: { ticks:{ color: tickColor }, grid:{ color:'transparent' } },
        y: { ticks:{ color: tickColor, callback:(v)=> euros(v) }, grid:{ color: gridColor } }
      }
    }
  });
}

if (document.getElementById('chartTopRepairs') && Array.isArray(topRepLabels) && topRepLabels.length) {
  new Chart(document.getElementById('chartTopRepairs'), {
    type: 'bar',
    data: { labels: topRepLabels, datasets: [{ label:'Réparations', data: topRepValues, borderWidth: 0, borderRadius: 10 }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display:false } },
      scales: {
        x: { ticks:{ color: tickColor }, grid:{ color:'transparent' } },
        y: { ticks:{ color: tickColor, precision:0 }, grid:{ color: gridColor }, beginAtZero:true }
      }
    }
  });
}
</script>

</body>
</html>
OK