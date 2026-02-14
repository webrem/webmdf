<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();

/* ===============================
   ANNULATION DE L’IMPORT
================================ */
if (isset($_POST['cancel_import'])) {
    unset($_SESSION['CSV_ROWS']);
    header("Location: import_stock.php");
    exit;
}

/* ===============================
   SÉCURITÉ
================================ */
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

/* ===============================
   CHARGEMENT CSV
================================ */
if (isset($_POST['upload']) && isset($_FILES['csv'])) {
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    $rows = [];

    while (($line = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
        $rows[] = $line;
    }
    fclose($fh);

    $_SESSION['CSV_ROWS'] = $rows;
}

$rows = $_SESSION['CSV_ROWS'] ?? [];
$colCount = !empty($rows) ? count($rows[0]) : 0;
?>

<?php include '../header.php'; ?>

<style>
/* ===============================
   STYLE IMPORT / STOCK
   Identique à la page stock.php
================================ */

body{
  background:#f4f6f8;
}

.container{
  background:#fff;
  border-radius:8px;
  padding:25px;
  box-shadow:0 2px 8px rgba(0,0,0,.08);
}

h2{
  margin-top:0;
  font-size:22px;
}

h5{
  margin-top:20px;
  font-size:16px;
  color:#333;
}

hr{
  border:none;
  border-top:1px solid #eee;
  margin:25px 0;
}

.form-control{
  border-radius:4px;
  border:1px solid #ddd;
}

.form-control:focus{
  border-color:#e30613;
  box-shadow:0 0 0 .15rem rgba(227,6,19,.15);
}

.btn{
  border-radius:4px;
  padding:10px 14px;
  font-weight:600;
}

.btn-primary{
  background:#e30613;
  border-color:#e30613;
}

.btn-primary:hover{
  background:#c10510;
  border-color:#c10510;
}

.btn-success{
  background:#333;
  border-color:#333;
}

.btn-success:hover{
  background:#000;
  border-color:#000;
}

.btn-danger{
  background:#999;
  border-color:#999;
  color:#fff;
}

.btn-danger:hover{
  background:#666;
  border-color:#666;
}

.table{
  background:#fff;
  border-collapse:collapse;
}

.table td{
  border:1px solid #eee;
  padding:10px;
  font-size:14px;
}

.table-dark{
  background:#fff;
}

.table-dark td{
  color:#222;
}
</style>

<div class="container py-4">
<h2>📥 Import stock – Mapping des colonnes</h2>

<!-- FORMULAIRE UPLOAD CSV -->
<form method="post" enctype="multipart/form-data">
  <input type="file" name="csv" required class="form-control mb-3">
  <button name="upload" class="btn btn-primary">Charger le fichier</button>
</form>

<?php if ($colCount): ?>
<hr>

<!-- FORMULAIRE MAPPING -->
<form method="post" action="import_stock_confirm.php">
  <h5>🧩 Associer les colonnes</h5>

<?php
$fields = [
  'reference'   => 'Référence',
  'designation' => 'Désignation',
  'prix'        => 'Prix d’achat',
  'prix_ht'     => 'Prix de vente',
  'stock'       => 'Stock',
  'fournisseur' => 'Fournisseur',
  'ean'         => 'EAN'
];

foreach ($fields as $key => $label):
?>
    <label><?= $label ?></label>
    <select name="map[<?= $key ?>]" class="form-control mb-2" required>
      <option value="">— Choisir —</option>
      <?php for ($i=0; $i<$colCount; $i++): ?>
        <option value="<?= $i ?>">Colonne <?= $i+1 ?></option>
      <?php endfor; ?>
    </select>
<?php endforeach; ?>

  <h5 class="mt-4">👁 Aperçu (5 premières lignes)</h5>
  <table class="table table-dark table-bordered">
    <?php foreach (array_slice($rows,0,5) as $r): ?>
      <tr>
        <?php foreach ($r as $cell): ?>
          <td><?= htmlspecialchars($cell) ?></td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </table>

  <!-- BOUTONS ACTION -->
  <div style="margin-top:15px;">
    <button class="btn btn-success">➡ Continuer vers l’import</button>
  </div>
</form>

<!-- BOUTON ANNULER IMPORT -->
<form method="post" style="margin-top:10px;">
  <button
    type="submit"
    name="cancel_import"
    class="btn btn-danger"
    onclick="return confirm('Annuler l’import et supprimer le fichier chargé ?')"
  >
    ❌ Annuler l’import
  </button>
</form>

<?php endif; ?>
</div>
