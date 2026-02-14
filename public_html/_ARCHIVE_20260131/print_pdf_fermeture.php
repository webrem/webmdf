<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';



session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) exit;


<?php if (!empty($caisse['validated_at'])): ?>
<h3>Validation gérant</h3>
<p>
Validée par : <strong><?= htmlspecialchars($caisse['user_close']) ?></strong><br>
Date : <?= $caisse['validated_at'] ?>
</p>

<p class="small">
Signature gérant :<br>
<?= $caisse['signature_gerant'] ?>
</p>
<?php endif; ?>


$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset("utf8mb4");

$id = (int)($_GET['id'] ?? 0);

/* CAISSE */
$stmt = $conn->prepare("
  SELECT c.*, 
         uo.username AS user_open,
         uf.username AS user_close
  FROM caisse_jour c
  LEFT JOIN users uo ON uo.id = c.user_ouverture
  LEFT JOIN users uf ON uf.id = c.user_fermeture
  WHERE c.id = ?
  LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$caisse = $stmt->get_result()->fetch_assoc();

if (!$caisse || empty($caisse['heure_fermeture'])) {
  die("Caisse non fermée.");
}

/* COMPTAGE ESPÈCES */
$stmt = $conn->prepare("
  SELECT details_json, total_calcule
  FROM caisse_comptage
  WHERE caisse_id = ? AND type='fermeture'
  LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$comp = $stmt->get_result()->fetch_assoc();

$details = $comp ? json_decode($comp['details_json'], true) : [];

/* SIGNATURE NUMÉRIQUE */
$signature = hash(
  'sha256',
  $caisse['id'].'|'.
  $caisse['date_caisse'].'|'.
  $caisse['total_especes'].'|'.
  $caisse['total_cb'].'|'.
  $caisse['user_fermeture'].'|'.
  $caisse['heure_fermeture']
);

$totalGlobal = $caisse['total_especes'] + $caisse['total_cb'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Clôture de caisse</title>
<style>
body { font-family: Arial; background:#fff; }
.page { width:210mm; min-height:297mm; padding:20mm; }
h1 { text-align:center; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th,td { border:1px solid #000; padding:6px; font-size:12px; }
th { background:#eee; }
.small { font-size:11px; }
.right { text-align:right; }
.center { text-align:center; }
@media print { button { display:none; } }
</style>
</head>
<body>

<div class="page">

<h1>R.E.Mobiles</h1>
<p class="center"><strong>CLÔTURE DE CAISSE JOURNALIÈRE</strong></p>

<p>
Date : <strong><?= htmlspecialchars($caisse['date_caisse']) ?></strong><br>
Ouverture : <?= $caisse['heure_ouverture'] ?> (<?= htmlspecialchars($caisse['user_open']) ?>)<br>
Fermeture : <?= $caisse['heure_fermeture'] ?> (<?= htmlspecialchars($caisse['user_close']) ?>)
</p>

<h3>Récapitulatif</h3>
<table>
<tr><th>Type</th><th class="right">Montant</th></tr>
<tr><td>Espèces</td><td class="right"><?= number_format($caisse['total_especes'],2,',',' ') ?> €</td></tr>
<tr><td>Carte bancaire</td><td class="right"><?= number_format($caisse['total_cb'],2,',',' ') ?> €</td></tr>
<tr><th>Total encaissé</th><th class="right"><?= number_format($totalGlobal,2,',',' ') ?> €</th></tr>
</table>

<h3>Détail comptage espèces</h3>
<table>
<tr><th>Valeur</th><th>Qté</th><th class="right">Sous-total</th></tr>
<?php foreach ($details as $val => $qty): ?>
<tr>
<td><?= number_format($val,2,',',' ') ?> €</td>
<td class="center"><?= (int)$qty ?></td>
<td class="right"><?= number_format($val*$qty,2,',',' ') ?> €</td>
</tr>
<?php endforeach; ?>
</table>

<p class="right"><strong>Total compté :</strong>
<?= number_format($comp['total_calcule'] ?? 0,2,',',' ') ?> €
</p>

<h3>Validation</h3>
<p>
Caisse fermée et validée par : <strong><?= htmlspecialchars($caisse['user_close']) ?></strong><br>
Signature numérique :
</p>

<p class="small">
<?= $signature ?>
</p>

<p class="center small">
Document comptable officiel – toute modification invalide la signature
</p>

<button onclick="window.print()">🖨️ Imprimer PDF</button>

</div>
</body>
</html>
