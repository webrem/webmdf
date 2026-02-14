<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) exit;

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset("utf8mb4");

$id = (int)($_GET['id'] ?? 0);

/* =========================
   CAISSE
   ========================= */
$stmt = $conn->prepare("
    SELECT c.*, u.username
    FROM caisse_jour c
    LEFT JOIN users u ON u.id = c.user_fermeture
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$caisse = $stmt->get_result()->fetch_assoc();

if (!$caisse || empty($caisse['heure_fermeture'])) {
    die("❌ Caisse non fermée ou introuvable.");
}
/* =========================
   RETRAITS ESPÈCES (INFO TICKET)
   ========================= */
$stmt = $conn->prepare("
    SELECT
        SUM(prix_total) AS retrait_especes
    FROM ventes_historique
    WHERE LOWER(type) = 'retrait'
      AND mode_paiement = 'Espèces'
      AND DATE(date_vente) = ?
      AND date_vente <= ?
");
$stmt->bind_param("ss", $caisse['date_caisse'], $caisse['heure_fermeture']);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();


/* =========================
   LISTE DES RETRAITS (DÉTAIL)
   ========================= */
$stmt = $conn->prepare("
    SELECT
        date_vente,
        designation,
        ABS(prix_total) AS montant
    FROM ventes_historique
    WHERE LOWER(type) = 'retrait'
      AND mode_paiement = 'Espèces'
      AND DATE(date_vente) = ?
      AND date_vente <= ?
    ORDER BY date_vente ASC
");
$stmt->bind_param("ss", $caisse['date_caisse'], $caisse['heure_fermeture']);
$stmt->execute();
$retraitsList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


/*
 prix_total est négatif
*/
$retraitsEspeces = (float)($r['retrait_especes'] ?? 0);


/* =========================
   COMPTAGE ESPÈCES
   ========================= */
$stmt = $conn->prepare("
    SELECT details_json
    FROM caisse_comptage
    WHERE caisse_id = ?
    AND type = 'fermeture'
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$comp = $stmt->get_result()->fetch_assoc();

$details = [];
if ($comp) {
    $details = json_decode($comp['details_json'], true) ?: [];
}

$totalEspeces = (float)$caisse['total_especes'];
$totalCB      = (float)$caisse['total_cb'];
$totalGlobal  = $totalEspeces + $totalCB;
/* =========================
   COFFRE & FOND DE CAISSE
   ========================= */

// Fond de caisse à l'ouverture
$fondCaisse = (float)($caisse['montant_ouverture'] ?? 0);

// Montant envoyé au coffre
$montantCoffre = (float)($caisse['montant_coffre'] ?? 0);

// Argent restant physiquement en caisse
$resteCaisse = round($totalEspeces - $montantCoffre, 2);

/* =========================
   VALIDATION GÉRANT
   ========================= */
$validationError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_caisse'])) {

    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("
        SELECT password
        FROM users
        WHERE id = ?
        AND role = 'admin'
        LIMIT 1
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if (!$admin || !password_verify($password, $admin['password'])) {
        $validationError = "❌ Mot de passe gérant incorrect.";
    } else {
        $stmt = $conn->prepare("
            UPDATE caisse_jour SET
                validated_at = NOW(),
                validated_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $id);
        $stmt->execute();

        header("Location: print_confirmation_fermeture.php?id=".$id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Confirmation fermeture caisse</title>
<style>
body { font-family: Arial, sans-serif; background:#fff; color:#000; }
.ticket { max-width:420px; margin:auto; border:1px dashed #000; padding:20px; }
h1,h2 { text-align:center; }
.line { border-top:1px dashed #000; margin:10px 0; }
table { width:100%; border-collapse:collapse; font-size:12px; }
th,td { border-bottom:1px solid #000; padding:4px; text-align:right; }
th:first-child,td:first-child { text-align:left; }
.center { text-align:center; }
@media print { button { display:none; } }
</style>
</head>
<body>

<div class="ticket">

<h1>🧾 CONFIRMATION FERMETURE</h1>
<p class="center">Document officiel</p>

<div class="line"></div>

<p><strong>Date :</strong> <?= htmlspecialchars($caisse['date_caisse']) ?></p>
<p><strong>Ouverture :</strong> <?= htmlspecialchars($caisse['heure_ouverture']) ?></p>
<p><strong>Fermeture :</strong> <?= htmlspecialchars($caisse['heure_fermeture']) ?></p>
<p><strong>Utilisateur :</strong> <?= htmlspecialchars($caisse['username'] ?? 'Inconnu') ?></p>
<p><strong>Fond de caisse (ouverture) :</strong>
<?= number_format($fondCaisse,2,',',' ') ?> €</p>

<?php if ($retraitsEspeces != 0): ?>
<p style="color:red;">
<strong>➖ Retraits espèces :</strong>
<?= number_format(abs($retraitsEspeces),2,',',' ') ?> €
</p>
<?php endif; ?>

<div class="line"></div>

<h2>💵 Détail espèces</h2>

<?php if (empty($details)): ?>
<p class="center">Aucun détail enregistré</p>
<?php else: ?>
<table>
<thead>
<tr>
    <th>Valeur (€)</th>
    <th>Qté</th>
    <th>Sous-total (€)</th>
</tr>
</thead>
<tbody>
<?php
ksort($details, SORT_NUMERIC);
foreach ($details as $val => $qty):
    $sub = $val * $qty;
?>
<tr>
    <td><?= number_format($val,2,',',' ') ?></td>
    <td><?= (int)$qty ?></td>
    <td><?= number_format($sub,2,',',' ') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<div class="line"></div>

<p><strong>Total espèces :</strong> <?= number_format($totalEspeces,2,',',' ') ?> €</p>
<p><strong>Total CB :</strong> <?= number_format($totalCB,2,',',' ') ?> €</p>
<p><strong>Montant envoyé au coffre :</strong>
<?= number_format($montantCoffre,2,',',' ') ?> €</p>

<p><strong>Reste en caisse :</strong>
<?= number_format($resteCaisse,2,',',' ') ?> €</p>

<div class="line"></div>


<?php if (!empty($retraitsList)): ?>
<div class="line"></div>
<h2 style="color:red;">➖ Détail des retraits</h2>

<table>
<thead>
<tr>
  <th>Heure</th>
  <th>Motif</th>
  <th>Montant (€)</th>
</tr>
</thead>
<tbody>
<?php foreach ($retraitsList as $r): ?>
<tr>
  <td><?= date('H:i', strtotime($r['date_vente'])) ?></td>
  <td><?= htmlspecialchars($r['designation']) ?></td>
  <td style="color:red;">
    <?= number_format($r['montant'],2,',',' ') ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>


<p class="center"><strong>TOTAL ENCAISSÉ :</strong><br>
<?= number_format($totalGlobal,2,',',' ') ?> €</p>

<?php if (!empty($caisse['validated_at'])): ?>
<div class="line"></div>
<p class="center">
🛡️ Journée validée par le gérant<br>
Le <?= date('d/m/Y H:i', strtotime($caisse['validated_at'])) ?>
</p>
<?php endif; ?>

<?php if (empty($caisse['validated_at']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
<div class="line"></div>
<h2>🔐 Validation gérant</h2>

<?php if ($validationError): ?>
<p class="center" style="color:red;"><?= htmlspecialchars($validationError) ?></p>
<?php endif; ?>

<form method="post">
<input type="password"
       name="password"
       placeholder="Mot de passe gérant"
       style="width:100%;padding:8px;margin-bottom:8px"
       required>

<button type="submit"
        name="validate_caisse"
        style="width:100%;padding:10px;background:#000;color:#fff">
✅ Valider définitivement
</button>
</form>
<?php endif; ?>

<div class="line"></div>

<button onclick="window.print()">🖨️ Imprimer</button>
<button onclick="window.location.href='../pages/dashboard.php'"
        style="width:100%;margin-top:8px;padding:8px;">
↩️ Retour au dashboard
</button>

</div>
</body>
</html>
