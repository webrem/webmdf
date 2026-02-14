<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
require_once __DIR__ . '/stock_utils.php';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem"); 
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);
$conn->set_charset("utf8mb4");

if (!function_exists('post')) {
    function post($key, $default = '') { return isset($_POST[$key]) ? trim($_POST[$key]) : $default; }
}

$msg = '';
$rows = [];

/* 🧹 Réinitialisation */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['download_template'])) {
    unset($_SESSION['IMPORT_ROWS']);
}

/* 📥 Modèle CSV */
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_import_stock.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['reference','designation','mpn','prix_ht','quantite','fournisseur'], ';');
    fputcsv($out, ['EA437','Écran iPhone 16 Pro Max (Original Démonté)','8771621673554','349.11','0','lcd-phone.com'], ';');
    fclose($out);
    exit;
}

/* 🧾 Lecture du CSV */
if (isset($_POST['parse']) && isset($_FILES['csv']) && $_FILES['csv']['error'] === 0) {
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');

    // Lecture première ligne et nettoyage complet des entêtes
    $raw_header = fgetcsv($fh, 0, ';');
    if (!$raw_header) $raw_header = fgetcsv($fh, 0, ',');
    if (!$raw_header) die("Fichier CSV vide ou illisible.");

    $header = [];
    foreach ($raw_header as $h) {
        $clean = strtolower(trim(preg_replace('/[^\P{C}\n]+/u', '', $h))); // supprime caractères invisibles
        $clean = str_replace(['  ', "\t"], ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $header[] = $clean;
    }

    // Normalisation robuste
    $normalized = [];
    foreach ($header as $key) {
        switch (true) {
            case in_array($key, ['reference','ref','réf']):
                $normalized[] = 'reference'; break;
            case in_array($key, ['ean','mpn','ean/mpn','ean / mpn']):
                $normalized[] = 'ean'; break;
            case str_contains($key, 'désig') || $key === 'designation':
                $normalized[] = 'designation'; break;
            case str_contains($key, 'prix'):
                $normalized[] = 'prix_ht'; break;
            case str_contains($key, 'fourn'):
                $normalized[] = 'fournisseur'; break;
            case str_contains($key, 'quant'):
                $normalized[] = 'quantite'; break;
            default:
                $normalized[] = $key;
        }
    }

    while (($r = fgetcsv($fh, 0, ';')) !== false) {
        $line = @array_combine($normalized, $r);
        if (!$line) continue;

        $ref = trim($line['reference'] ?? '');
        $ean = trim($line['ean'] ?? '');
        $designation = trim($line['designation'] ?? '');
        $prix = as_float($line['prix_ht'] ?? 0);
        $fourn = trim($line['fournisseur'] ?? '');
        $qte = 0; // toujours 0 par défaut

        $rows[] = [
            'reference' => $ref,
            'designation' => $designation,
            'ean' => $ean,
            'prix_achat' => $prix,
            'quantite' => $qte,
            'fournisseur' => $fourn
        ];
    }
    fclose($fh);
    $_SESSION['IMPORT_ROWS'] = $rows;
}

/* ✅ Validation de l'import */
if (isset($_POST['commit']) && isset($_SESSION['IMPORT_ROWS'])) {
    $rows = $_SESSION['IMPORT_ROWS'];

    $transport  = as_float(post('frais_tr', 0));
    $douane     = as_float(post('douane_pct', 0));
    $autres     = as_float(post('autres_frais', 0));
    $marge      = as_float(post('marge_pct', 50));

    $created = $updated = 0;

    foreach ($rows as $i => $r) {
        $ref = $r['reference'];
        if (empty($ref)) continue;

        $ean = $r['ean'];
        $pa = as_float($r['prix_achat']);
        $designation = $r['designation'];
        $fourn = $r['fournisseur'];
        $qte = (int)($_POST['quantite'][$i] ?? 0);

        $cout_revient = $pa + $transport + $autres + ($pa * $douane / 100);
        $prix_vente_ttc = $cout_revient * (1 + $marge / 100);

        // Arrondi R.E.Mobiles
        if ($prix_vente_ttc < 5) $prix_vente_ttc = 4.99;
        elseif ($prix_vente_ttc < 10) $prix_vente_ttc = 9.99;
        elseif ($prix_vente_ttc < 15) $prix_vente_ttc = 14.99;
        elseif ($prix_vente_ttc < 20) $prix_vente_ttc = 19.99;
        else $prix_vente_ttc = ceil($prix_vente_ttc / 5) * 5 - 0.01;

        $now = now_guyane();

        $stmt = $conn->prepare("SELECT id, quantite FROM stock_articles WHERE reference=? LIMIT 1");
        bindParamsDynamic($stmt, [$ref]);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($a = $res->fetch_assoc()) {
            $id = $a['id'];
            $newqte = $a['quantite'] + $qte;
            $u = $conn->prepare("UPDATE stock_articles 
                SET designation=?, ean=?, fournisseur=?, prix_achat=?, prix_vente=?, cout_revient=?, quantite=?, updated_at=? 
                WHERE id=?");
            bindParamsDynamic($u, [$designation, $ean, $fourn, $pa, $prix_vente_ttc, $cout_revient, $newqte, $now, $id]);
            $u->execute();
            $updated++;
        } else {
            $i = $conn->prepare("INSERT INTO stock_articles(reference, ean, designation, fournisseur, prix_achat, prix_vente, cout_revient, quantite, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            bindParamsDynamic($i, [$ref, $ean, $designation, $fourn, $pa, $prix_vente_ttc, $cout_revient, $qte, $now, $now]);
            $i->execute();
            $created++;
        }
    }

    unset($_SESSION['IMPORT_ROWS']);
    $msg = "✅ Import terminé — Créés : $created, Mis à jour : $updated";
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>📥 Importation du Stock — R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0d1117;color:#fff;font-family:Poppins,sans-serif;}
h2{color:#0dcaf0;text-transform:uppercase;font-weight:800;margin-bottom:1rem;}
.table thead{background:linear-gradient(90deg,#0d6efd,#0dcaf0);color:#fff;}
.table tbody tr:hover{background:rgba(13,202,240,0.1);}
.glass{background:rgba(20,20,20,0.85);border-radius:16px;padding:1rem;}
input[type=number]{width:80px;text-align:center;}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="container py-4">
  <h2><i class="bi bi-upload"></i> Importation du stock</h2>
  <?php if($msg): ?><div class="alert alert-success text-center"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="glass">
        <h5 class="text-info fw-bold">📂 Charger un fichier CSV</h5>
        <form method="post" enctype="multipart/form-data">
          <input type="file" name="csv" accept=".csv" class="form-control mb-3" required>
          <div class="d-flex justify-content-between">
            <button class="btn btn-info" name="parse">📊 Prévisualiser</button>
            <a href="?download_template=1" class="btn btn-outline-info">⬇️ Modèle CSV</a>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="glass">
        <h5 class="text-info fw-bold">⚙️ Paramètres</h5>
        <form method="post">
          <div class="row g-3 mb-3">
            <div class="col-md-4"><label>Transport (€)</label><input name="frais_tr" class="form-control" value="0"></div>
            <div class="col-md-4"><label>Douane %</label><input name="douane_pct" class="form-control" value="0"></div>
            <div class="col-md-4"><label>Autres frais (€)</label><input name="autres_frais" class="form-control" value="0"></div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label>Marge %</label><input name="marge_pct" class="form-control" value="50"></div>
          </div>

          <?php if (!empty($_SESSION['IMPORT_ROWS'])): $rows = $_SESSION['IMPORT_ROWS']; ?>
          <div class="glass mt-4">
            <h5 class="text-info fw-bold">👁 Aperçu (<?= count($rows) ?> lignes)</h5>
            <div class="table-responsive">
              <table class="table table-dark table-striped table-bordered align-middle">
                <thead>
                  <tr><th>Réf</th><th>Désignation</th><th>EAN / MPN</th><th>Prix HT</th><th>Quantité</th><th>Fournisseur</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['reference']) ?></td>
                    <td><?= htmlspecialchars($r['designation']) ?></td>
                    <td><?= htmlspecialchars($r['ean']) ?></td>
                    <td><?= number_format((float)$r['prix_achat'], 2, ',', ' ') ?> €</td>
                    <td><input type="number" name="quantite[<?= $i ?>]" value="0" min="0"></td>
                    <td><?= htmlspecialchars($r['fournisseur']) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <button class="btn btn-success w-100 fw-bold mt-3" name="commit">✅ Importer les articles</button>
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
