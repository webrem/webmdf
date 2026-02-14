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

$stmt = $conn->prepare("
    SELECT
        c.*,
        u.username
    FROM caisse_jour c
    LEFT JOIN users u ON u.id = c.user_ouverture
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$caisse = $stmt->get_result()->fetch_assoc();

if (!$caisse || empty($caisse['heure_ouverture'])) {
    die("❌ Caisse introuvable ou non ouverte.");
}

$details = json_decode($caisse['details_ouverture'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Confirmation ouverture caisse</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #fff;
    color: #000;
}
.ticket {
    max-width: 420px;
    margin: auto;
    border: 1px dashed #000;
    padding: 20px;
}
h1 {
    text-align: center;
    margin-bottom: 5px;
}
.line {
    border-top: 1px dashed #000;
    margin: 10px 0;
}
.center {
    text-align: center;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
td {
    padding: 4px 0;
}
.right {
    text-align: right;
}
@media print {
    button { display: none; }
}
</style>
</head>
<body>

<div class="ticket">

<h1>🟢 CAISSE OUVERTE</h1>
<p class="center">Confirmation officielle d’ouverture</p>

<div class="line"></div>

<p><strong>Date caisse :</strong> <?= htmlspecialchars($caisse['date_caisse']) ?></p>
<p><strong>Ouverture :</strong> <?= htmlspecialchars($caisse['heure_ouverture']) ?></p>
<p><strong>Ouvert par :</strong> <?= htmlspecialchars($caisse['username'] ?? 'Inconnu') ?></p>

<div class="line"></div>

<p><strong>Détail du fond de caisse :</strong></p>

<table>
<?php
ksort($details);
foreach ($details as $valeur => $quantite):
?>
<tr>
    <td><?= number_format((float)$valeur, 2, ',', ' ') ?> €</td>
    <td class="right">× <?= (int)$quantite ?></td>
    <td class="right">
        <?= number_format($valeur * $quantite, 2, ',', ' ') ?> €
    </td>
</tr>
<?php endforeach; ?>
</table>

<div class="line"></div>

<p><strong>Total fond de caisse :</strong>
<?= number_format($caisse['montant_ouverture'], 2, ',', ' ') ?> €
</p>

<div class="line"></div>

<p class="center" style="font-size:12px;">
Document généré automatiquement<br>
R.E.Mobiles — Système de caisse
</p>

<button onclick="window.print()">🖨️ Imprimer la confirmation</button>

<button onclick="window.location.href='../pages/dashboard.php'"
        style="width:100%;margin-top:8px;padding:8px;">
↩️ Retour au dashboard
</button>

</div>

</body>
</html>
