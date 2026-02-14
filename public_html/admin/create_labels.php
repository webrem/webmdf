<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/ean_tools.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

$articles = $pdo->query("
    SELECT id, designation, prix_vente, ean, quantite
    FROM stock_articles
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Création d’étiquettes</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Visuel pro amélioré */
body {
  background: #f8f9fa;
}
h1.page-title {
  font-size: 1.85rem;
  font-weight: 700;
  color: #2a3f54;
}
.product-card {
  cursor: pointer;
  transition: transform .15s ease-in-out;
}
.product-card:hover {
  transform: scale(1.03);
  background: #f1f4f8;
}
.selected-list {
  max-height: 600px;
  overflow-y: auto;
}
.sidebar-preview {
  background: #ffffff;
  padding: 15px;
  border-radius: 12px;
  border: 1px solid #dee2e6;
}
</style>

</head>
<body class="container py-4">

<h1 class="text-center page-title mb-4">Sélectionnez des articles et créez les étiquettes</h1>

<form method="post" action="generate_labels_pdf.php">

<div class="row g-3">

  <div class="row g-3">

  <!-- ======== COLONNE UNIQUE : ARTICLES + QUANTITÉS ======== -->
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <strong>📦 Sélection des articles et quantités</strong>
      </div>

      <div class="card-body p-2 overflow-auto" style="max-height:650px;">
        <?php
        $newLimit = 25; // nombre d’articles considérés comme récents
        $index = 0;
        ?>
        <?php foreach ($articles as $a): ?>
        <?php $index++; ?>
          <div class="d-flex align-items-center mb-2 product-card border p-2 rounded">

            <!-- Checkbox -->
            <input type="checkbox"
               class="form-check-input me-3 product-checkbox"
               name="products[<?= $a['id'] ?>][selected]">

            <!-- Infos produit -->
            <div class="flex-grow-1">
              <div class="fw-bold">
              <?= htmlspecialchars($a['designation']) ?>
              <?php if ($index <= $newLimit): ?>
                <span class="badge bg-success ms-2">🆕 Nouveau</span>
              <?php endif; ?>
            </div>
              <small class="text-muted">
                <?= number_format($a['prix_vente'],2,',',' ') ?> € —
                EAN: <?= htmlspecialchars($a['ean'] ?: '-') ?>
              </small>
            </div>

            <!-- Quantité -->
            <input type="number"
                   class="form-control ms-3"
                   style="width:90px;"
                   name="products[<?= $a['id'] ?>][qty]"
                   min="1"
                   value="<?= max(1,(int)$a['quantite']) ?>">

          </div>
        <?php endforeach; ?>

      </div>

          <div class="card-footer text-center d-flex justify-content-center gap-3">
    
      <!-- Bouton Générer -->
      <button type="submit" class="btn btn-lg btn-success">
        🖨️ Générer les étiquettes
      </button>
    
      <!-- Bouton Réimprimer -->
      <a href="reimprimer_etiquettes.php" class="btn btn-lg btn-outline-primary">
        🔁 Réimprimer des étiquettes
      </a>
     <!-- Bouton Réimprimer -->
      <a href="stats_etiquettes.php" class="btn btn-lg btn-outline-primary">
        📊 Stats des étiquettes
      </a> 
      <!-- Bouton Réimprimer -->
      <a href="stats_produits_etiquettes.php" class="btn btn-lg btn-outline-primary">
        📊 Stats des étiquettes Imprimée
      </a>
    </div>
      
      
      
    </div>
  </div>

  <!-- ======== COLONNE APERÇU (INCHANGÉE) ======== -->
  <div class="col-lg-4">
  <div class="sidebar-preview text-center">
    <h5 class="text-secondary">Aperçu de l’impression</h5>

    <p class="mb-1"><strong>Format :</strong> 38 × 21,2 mm</p>
    <div class="badge bg-info text-white fs-6 mb-3">65 étiquettes / feuille</div>

    <hr>

    <div class="mb-3">
      <div class="border p-3 rounded">
        <div class="fw-bold fs-5 text-primary" id="totalLabels">0</div>
        <div class="text-muted">Étiquettes à imprimer</div>
      </div>
    </div>

    <div class="mb-3">
      <div class="border p-3 rounded">
        <div class="fw-bold fs-5" id="totalSheets">0</div>
        <div class="text-muted">Feuilles nécessaires</div>
      </div>
    </div>

    <p class="text-muted small mb-0">
      Mise à jour en temps réel avant impression
    </p>
  </div>
</div>


</div>


</form>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updatePreview() {
    let totalLabels = 0;

    document.querySelectorAll('.product-card').forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        const qtyInput = card.querySelector('input[type="number"]');

        if (checkbox.checked) {
            const qty = parseInt(qtyInput.value) || 0;
            totalLabels += qty;
        }
    });

    const sheets = Math.ceil(totalLabels / 65);

    document.getElementById('totalLabels').textContent = totalLabels;
    document.getElementById('totalSheets').textContent = sheets;
}

// Écouteurs
document.querySelectorAll('input[type="checkbox"], input[type="number"]').forEach(el => {
    el.addEventListener('change', updatePreview);
    el.addEventListener('input', updatePreview);
});
</script>


<script>
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function (e) {

        // Si on clique sur un input (checkbox ou quantité), on ne fait rien
        if (e.target.tagName === 'INPUT') {
            return;
        }

        // Sinon on coche / décoche la checkbox
        const checkbox = card.querySelector('.product-checkbox');
        checkbox.checked = !checkbox.checked;

        // Mise à jour de l’aperçu
        updatePreview();
    });
});
</script>

</body>
</html>
