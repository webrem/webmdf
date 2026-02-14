<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php'; // garde ton db.php actuel (doit fournir $pdo)


// (optionnel) sécurité admin, comme stock.php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

$stats = null;
try {
    $stats = $pdo->query("SELECT * FROM stock_stats LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $stats = null;
}

// Fallback si stock_stats n'existe pas (ou vide) -> on calcule vite fait depuis stock_articles
if (!$stats) {
    $rows = $pdo->query("SELECT prix_vente, quantite FROM stock_articles")->fetchAll(PDO::FETCH_ASSOC);
    $total_produits = count($rows);
    $valeur_stock = 0.0;
    $stock_faible = 0;
    $rupture = 0;

    foreach ($rows as $r) {
        $q = (int)($r['quantite'] ?? 0);
        $p = (float)($r['prix_vente'] ?? 0);
        $valeur_stock += ($p * $q);
        if ($q === 0) $rupture++;
        elseif ($q <= 3) $stock_faible++;
    }

    $stats = [
        'total_produits' => $total_produits,
        'valeur_stock' => $valeur_stock,
        'stock_faible' => $stock_faible,
        'rupture' => $rupture,
    ];
}

// ---- Données pour widgets / charts ----

// Top produits (valeur)
$topProducts = [];
try {
    $topProducts = $pdo->query("
        SELECT designation,
               (prix_vente * quantite) AS valeur
        FROM stock_articles
        ORDER BY valeur DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $topProducts = [];
}

// Valeur par catégorie (si colonne categorie existe)
$byCategory = [];
try {
    $byCategory = $pdo->query("
        SELECT COALESCE(NULLIF(categorie,''),'Sans catégorie') AS categorie,
               SUM(prix_vente * quantite) AS valeur
        FROM stock_articles
        GROUP BY categorie
        ORDER BY valeur DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $byCategory = [];
}

// Alertes (rupture / faible)
$alerts = [];
try {
    // seuil_min si dispo, sinon fallback 3
    $alerts = $pdo->query("
        SELECT reference, designation, quantite,
               COALESCE(seuil_min, 3) AS seuil_min
        FROM stock_articles
        WHERE quantite <= COALESCE(seuil_min, 3)
        ORDER BY quantite ASC, designation ASC
        LIMIT 12
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // schéma minimal (sans seuil_min)
    try {
        $alerts = $pdo->query("
            SELECT reference, designation, quantite, 3 AS seuil_min
            FROM stock_articles
            WHERE quantite <= 3
            ORDER BY quantite ASC, designation ASC
            LIMIT 12
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        $alerts = [];
    }
}





/* =====================================================
   COMMISSIONS VENDEURS — MOIS EN COURS
   LOGIQUE STRICTEMENT IDENTIQUE AU TICKET
===================================================== */

/* =====================================================
   COMMISSIONS VENDEURS — LOGIQUE IDENTIQUE AU TICKET
   (VENTES POS FIXÉES)
===================================================== */

/* ================= COMMISSIONS — VERSION RÉPARÉE ================= */

date_default_timezone_set('America/Cayenne');
$debutMois = date('Y-m-01 00:00:00');
$finMois   = date('Y-m-t 23:59:59');

$commissions = [];

$sqlCommissions = "
SELECT 
    vendeur,
    SUM(total_ventes)        AS total_ventes,
    SUM(total_acompte_rep)   AS total_acompte_rep,
    SUM(total_acompte_cmd)   AS total_acompte_cmd
FROM (

    /* VENTES POS — ALIGNÉ AU TICKET */
    SELECT 
        vendeur COLLATE utf8mb4_unicode_ci AS vendeur,
        SUM(prix_total) AS total_ventes,
        0 AS total_acompte_rep,
        0 AS total_acompte_cmd
    FROM ventes
    WHERE date_vente BETWEEN ? AND ?
    GROUP BY vendeur

    UNION ALL

    /* ACOMPTES RÉPARATIONS */
    SELECT
        vendeur COLLATE utf8mb4_unicode_ci,
        0,
        SUM(prix_total),
        0
    FROM ventes_historique
    WHERE type='acompte'
      AND (ref_vente LIKE '%REM%' OR ref_vente LIKE 'POS-ACOMPTE-%')
      AND date_vente BETWEEN ? AND ?
    GROUP BY vendeur

    UNION ALL

    /* ACOMPTES COMMANDES */
    SELECT
        vendeur COLLATE utf8mb4_unicode_ci,
        0,
        0,
        SUM(prix_total)
    FROM ventes_historique
    WHERE type='acompte'
      AND (
          ref_vente LIKE 'ACOMPTE-%'
          OR ref_vente LIKE 'CMD-%'
          OR ref_vente LIKE 'COMMANDE-%'
      )
      AND ref_vente NOT LIKE '%REM%'
      AND date_vente BETWEEN ? AND ?
    GROUP BY vendeur

) t
GROUP BY vendeur
ORDER BY vendeur ASC
";

$stmt = $pdo->prepare($sqlCommissions);
$stmt->execute([
    $debutMois, $finMois,
    $debutMois, $finMois,
    $debutMois, $finMois
]);

$commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* === COMMISSIONS (STRICTEMENT COMME LE TICKET) === */
foreach ($commissions as &$c) {
    $c['commission_vente']      = round($c['total_ventes'] * 0.15, 2);
    $c['commission_reparation'] = round($c['total_acompte_rep'] * 0.20, 2);
    $c['commission_commande']   = round($c['total_acompte_cmd'] * 0.20, 2);

    $c['total_commission'] =
        $c['commission_vente']
      + $c['commission_reparation']
      + $c['commission_commande'];
}
unset($c);






// JSON pour JS (Charts)
$topLabels = array_map(fn($r) => (string)($r['designation'] ?? ''), $topProducts);
$topValues = array_map(fn($r) => (float)($r['valeur'] ?? 0), $topProducts);

$catLabels = array_map(fn($r) => (string)($r['categorie'] ?? 'Sans catégorie'), $byCategory);
$catValues = array_map(fn($r) => (float)($r['valeur'] ?? 0), $byCategory);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard Stock — R.E.Mobiles</title>

<link rel="stylesheet" href="../assets/css/dashboard_stock.css">


<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>

<?php include '../header.php'; ?>

<div class="ds-shell">

  <!-- Topbar -->
  <div class="ds-topbar">
    <div class="ds-title">
      <div class="ds-badge"><i class="bi bi-graph-up-arrow"></i></div>
      <div>
        <h1>Dashboard Stock</h1>
        <p class="ds-subtitle">Vue rapide sur l’état du stock R.E.Mobiles</p>
      </div>
    </div>

    <div class="ds-actions">
      <a class="btn btn-primary" href="add_stock.php"><i class="bi bi-plus-circle"></i> Nouveau produit</a>
      <a class="btn btn-soft" href="stock.php"><i class="bi bi-box-seam"></i> Liste stock</a>
      <a class="btn btn-soft" href="import_stock.php"><i class="bi bi-upload"></i> Import CSV</a>
      <a class="btn btn-soft" href="export_stock.php"><i class="bi bi-download"></i> Export CSV</a>
    </div>
  </div>

  <!-- KPIs -->
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

  <!-- Main grid -->
  <section class="ds-grid">
    <!-- Chart: Top products -->
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <i class="bi bi-bar-chart-line"></i>
          <span>Top produits (valeur)</span>
        </div>
        <div class="panel-hint">Top 10 selon prix_vente × quantite</div>
      </div>
      <div class="panel-body">
        <canvas id="chartTopProducts" height="120"></canvas>
      </div>
    </div>

    <!-- Chart: Categories -->
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <i class="bi bi-pie-chart"></i>
          <span>Répartition par catégorie</span>
        </div>
        <div class="panel-hint">
          <?= empty($byCategory) ? "Colonne categorie non trouvée ou données indisponibles." : "Top 8 catégories (valeur)." ?>
        </div>
      </div>
      <div class="panel-body">
        <canvas id="chartCategories" height="120"></canvas>
      </div>
    </div>

    <!-- Alerts table -->
    <div class="panel panel-wide">
      <div class="panel-head">
        <div class="panel-title">
          <i class="bi bi-bell"></i>
          <span>Alertes stock</span>
        </div>
        <div class="panel-hint">Produits à surveiller (quantité ≤ seuil)</div>
      </div>

      <div class="panel-body">
        <?php if (empty($alerts)): ?>
          <div class="empty">
            <i class="bi bi-check2-circle"></i>
            <div>
              <div class="empty-title">Aucune alerte</div>
              <div class="empty-sub">Ton stock est OK selon les seuils actuels.</div>
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
                  $status = ($q === 0) ? 'Rupture' : 'Faible';
                  $cls = ($q === 0) ? 'badge-danger' : 'badge-warn';
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

  <!-- Footer -->
  <footer class="ds-footer">
    <div class="ds-footer-left">
      <div class="foot-brand">R.E.Mobiles — Stock</div>
      <div class="foot-sub">Tableau de bord interne</div>
    </div>
    <div class="ds-footer-links">
      <a href="#" onclick="alert('FAQ à ajouter');return false;">FAQ</a>
      <a href="#" onclick="alert('Support à ajouter');return false;">Support</a>
      <a href="#" onclick="alert('CGU à ajouter');return false;">Conditions</a>
    </div>
  </footer>

<!-- ================= COMMISSIONS VENDEURS ================= -->
<section class="ds-commissions">

<?php if (empty($commissions)): ?>
  <div class="empty">
    <i class="bi bi-info-circle"></i>
    <div>
      <div class="empty-title">Aucune commission</div>
      <div class="empty-sub">Aucune donnée vendeur disponible.</div>
    </div>
  </div>
<?php else: ?>

<?php foreach ($commissions as $c): ?>
  <div class="commission-card">

    <div class="commission-head">
      <i class="bi bi-person-badge"></i>
      <strong><?= htmlspecialchars($c['vendeur']) ?></strong>
    </div>

    <div class="commission-block">
      <span>🟦 Ventes POS (15%)</span>
      <small><?= number_format($c['total_ventes'],2,',',' ') ?> €</small>
      <b><?= number_format($c['commission_vente'],2,',',' ') ?> €</b>
    </div>

    <div class="commission-block">
      <span>🟩 Acomptes réparations (20%)</span>
      <small><?= number_format($c['total_acompte_rep'],2,',',' ') ?> €</small>
      <b><?= number_format($c['commission_reparation'],2,',',' ') ?> €</b>
    </div>

    <div class="commission-block">
      <span>🟨 Acomptes commandes (20%)</span>
      <small><?= number_format($c['total_acompte_cmd'],2,',',' ') ?> €</small>
      <b><?= number_format($c['commission_commande'],2,',',' ') ?> €</b>
    </div>

    <div class="commission-total">
      Commission totale
      <span><?= number_format($c['total_commission'],2,',',' ') ?> €</span>
    </div>

    <a class="btn btn-soft btn-sm"
       href="../ticket_vendeur.php?vendeur=<?= urlencode($c['vendeur']) ?>&date_debut=<?= date('Y-m-01') ?>&date_fin=<?= date('Y-m-d') ?>">
      <i class="bi bi-receipt"></i> Ticket commission
    </a>

  </div>
<?php endforeach; ?>

<?php endif; ?>
</section>


</div>

<script>
/* ---------- Charts ---------- */
const topLabels = <?= json_encode($topLabels, JSON_UNESCAPED_UNICODE) ?>;
const topValues = <?= json_encode($topValues, JSON_UNESCAPED_UNICODE) ?>;

const catLabels = <?= json_encode($catLabels, JSON_UNESCAPED_UNICODE) ?>;
const catValues = <?= json_encode($catValues, JSON_UNESCAPED_UNICODE) ?>;

const gridColor = 'rgba(255,255,255,0.08)';
const tickColor = 'rgba(255,255,255,0.75)';

function euros(v){
  try {
    return new Intl.NumberFormat('fr-FR', { style:'currency', currency:'EUR' }).format(v);
  } catch(e){
    return (Math.round(v*100)/100).toFixed(2) + ' €';
  }
}

if (document.getElementById('chartTopProducts')) {
  new Chart(document.getElementById('chartTopProducts'), {
    type: 'bar',
    data: {
      labels: topLabels,
      datasets: [{
        label: 'Valeur',
        data: topValues,
        borderWidth: 0,
        borderRadius: 10,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ' ' + euros(ctx.parsed.y)
          }
        }
      },
      scales: {
        x: {
          ticks: { color: tickColor, maxRotation: 0, autoSkip: true },
          grid: { color: 'transparent' }
        },
        y: {
          ticks: { color: tickColor, callback: (v) => euros(v) },
          grid: { color: gridColor }
        }
      }
    }
  });
}

if (document.getElementById('chartCategories')) {
  // si pas de données catégorie, on met un placeholder
  const hasData = Array.isArray(catLabels) && catLabels.length > 0;

  new Chart(document.getElementById('chartCategories'), {
    type: 'doughnut',
    data: {
      labels: hasData ? catLabels : ['Données indisponibles'],
      datasets: [{
        data: hasData ? catValues : [1],
        borderWidth: 0,
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: tickColor, boxWidth: 12, boxHeight: 12 }
        },
        tooltip: {
          callbacks: {
            label: (ctx) => ' ' + ctx.label + ': ' + euros(ctx.parsed)
          }
        }
      }
    }
  });
}
</script>

</body>
</html>
