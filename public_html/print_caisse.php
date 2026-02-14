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

$id = intval($_GET['id']);

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

if (!$caisse) exit;

/* =========================
   ACOMPTES DU JOUR
   ========================= */
$stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN mode_paiement = 'Espèces' THEN prix_total ELSE 0 END) AS acompte_especes,
        SUM(CASE WHEN mode_paiement = 'Carte Bancaire' THEN prix_total ELSE 0 END) AS acompte_cb
    FROM ventes_historique
    WHERE LOWER(type) = 'acompte'
    AND DATE(date_vente) = ?
");
$stmt->bind_param("s", $caisse['date_caisse']);
$stmt->execute();
$acomptes = $stmt->get_result()->fetch_assoc();

$acompteEspeces = (float)($acomptes['acompte_especes'] ?? 0);
$acompteCB      = (float)($acomptes['acompte_cb'] ?? 0);

/* =========================
   TOTAUX
   ========================= */
$totalVentes   = (float)$caisse['total_especes'] + (float)$caisse['total_cb'];
$totalAcomptes = $acompteEspeces + $acompteCB;
$totalGlobal   = $totalVentes + $totalAcomptes;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ticket fermeture caisse</title>
<style>
body { font-family: Arial; background:#fff; color:#000; }
.ticket { max-width: 420px; margin:auto; }
h1 { text-align:center; }
.line { border-top:1px dashed #000; margin:10px 0; }
.section { margin-top:10px; }
strong { font-weight:bold; }
@media print { button { display:none; } }
</style>
</head>
<body>

<div class="ticket">

<h1>FERMETURE CAISSE</h1>

<div class="line"></div>

<p>Date caisse : <strong><?= htmlspecialchars($caisse['date_caisse']) ?></strong></p>
<p>Ouverture : <?= $caisse['heure_ouverture'] ?></p>
<p>Fermeture : <?= $caisse['heure_fermeture'] ?></p>

<div class="line"></div>

<div class="section">
<strong>🛒 VENTES</strong><br>
💵 Espèces : <?= number_format($caisse['total_especes'],2,',',' ') ?> €<br>
💳 CB : <?= number_format($caisse['total_cb'],2,',',' ') ?> €<br>
➡️ <strong>Total ventes :</strong> <?= number_format($totalVentes,2,',',' ') ?> €
</div>

<div class="line"></div>

<div class="section">
<strong>💰 ACOMPTES</strong><br>
💵 Espèces : <?= number_format($acompteEspeces,2,',',' ') ?> €<br>
💳 CB : <?= number_format($acompteCB,2,',',' ') ?> €<br>
➡️ <strong>Total acomptes :</strong> <?= number_format($totalAcomptes,2,',',' ') ?> €
</div>

<div class="line"></div>

<p><strong>💵 TOTAL ENCAISSÉ :</strong> <?= number_format($totalGlobal,2,',',' ') ?> €</p>

<div class="line"></div>

<p>Fermé par : <?= htmlspecialchars($caisse['username']) ?></p>

<button onclick="window.print()">🖨️ Imprimer</button>

</div>

</body>
</html>
