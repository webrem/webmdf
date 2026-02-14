<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/sync_time.php';


$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB");
$conn->set_charset("utf8mb4");

$ref = $_GET['ref'] ?? '';
$id  = $_GET['id'] ?? '';
$user = $_SESSION['username'] ?? 'Utilisateur inconnu';

// === Recherche du ticket ===
$type_ticket = 'vente';
$data = [];
$total_general = 0;

// --- Si on a un ID, on cherche directement dans ventes_historique ---
if (!empty($id)) {
    $stmt = $conn->prepare("SELECT * FROM ventes_historique WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) die("⚠️ Ticket introuvable (ID non valide)");
    $data = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $type_ticket = 'acompte';
} 
// --- Sinon, on garde le comportement original basé sur la référence ---
else {
    if ($ref === '') die("Référence manquante.");
    $stmt = $conn->prepare("SELECT * FROM ventes WHERE ref_vente=?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $res = $stmt->get_result();

    // Si rien dans ventes → on cherche dans ventes_historique
    if ($res->num_rows === 0) {
        $stmt2 = $conn->prepare("SELECT * FROM ventes_historique WHERE ref_vente=? ORDER BY date_vente DESC LIMIT 1");
        $stmt2->bind_param("s", $ref);
        $stmt2->execute();
        $res = $stmt2->get_result();
        if ($res->num_rows === 0) die("⚠️ Ticket introuvable dans ventes_historique.");
        $type_ticket = 'acompte';
    }

    $data = $res->fetch_all(MYSQLI_ASSOC);
}

foreach ($data as $row) {
    $row['prix_unitaire'] = ($row['quantite'] > 0)
        ? ($row['prix_total'] / $row['quantite'])
        : $row['prix_total'];
    $row['remise_pct'] = isset($row['remise_pct']) ? (int)$row['remise_pct'] : 0;
    $row['remise_montant'] = isset($row['remise_montant']) ? (float)$row['remise_montant'] : 0;
    $total_general += (float)$row['prix_total'];
}

$vente = $data[0];

// === Création du PDF 80mm ===
$pdf = new TCPDF('P', 'mm', [80, 160 + count($data)*6], true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// --- Logo ---
$pageWidth = $pdf->getPageWidth();
$logoWidth = 40;
$x = ($pageWidth - $logoWidth) / 2;
$logoPath = __DIR__ . "/logo-rem.png";
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $x, 5, $logoWidth);
    $pdf->Ln(28);
}

// === Titre selon le type ===
$titre = ($type_ticket === 'acompte') ? "Ticket d'Acompte" : "Ticket de Vente";
$color = ($type_ticket === 'acompte') ? "#0dcaf0" : "#000";

$html = '
<div style="text-align:center;">
  <h3 style="color:'.$color.';"><strong>'.$titre.'</strong></h3>
  <span style="font-size:10px;">
    104B Avenue Général de Gaulle<br>
    97300 Cayenne<br>
    📞 +594 694 27 40 51
  </span>
  <hr>
  Réf : <strong>'.htmlspecialchars($vente['ref_vente']).'</strong><br>
  Date : '.date('d/m/Y H:i', strtotime($vente['date_vente'])).'<br>
  <span style="font-size:10px;">Enregistré par <strong>'.$user.'</strong></span>
  <hr>
</div>
';

// === Détails du contenu ===
if ($type_ticket === 'vente') {
    $html .= '<table cellpadding="3" cellspacing="0" width="100%">
    <tr style="background-color:#ddd;">
      <td><b>Produit</b></td>
      <td align="center"><b>Qté</b></td>
      <td align="right"><b>Total</b></td>
    </tr>';
    foreach ($data as $v) {
        $designation = htmlspecialchars($v['designation']);
        $qte = (int)$v['quantite'];
        $ligne_total = number_format($v['prix_total'],2,',',' ');
        $html .= "<tr>
          <td>$designation</td>
          <td align='center'>$qte</td>
          <td align='right'>$ligne_total €</td>
        </tr>";
    }
    $html .= '</table><hr>';
} else {
    // Ticket d'acompte simple
    $designation = htmlspecialchars($vente['designation'] ?? 'Versement acompte');
    $montant = number_format((float)$vente['prix_total'], 2, ',', ' ');
    $html .= '<table cellpadding="3" cellspacing="0" width="100%">
      <tr><td><b>Désignation :</b></td><td align="right">'.$designation.'</td></tr>
      <tr><td><b>Montant :</b></td><td align="right"><strong>'.$montant.' €</strong></td></tr>
    </table><hr>';
}

// === Infos client + paiement ===
$clientNom = htmlspecialchars($vente['client_nom'] ?? 'Client non précisé');
$clientTel = htmlspecialchars($vente['client_tel'] ?? '—');
$modePaiement = htmlspecialchars($vente['mode_paiement'] ?? '-');

$html .= '
<table width="100%">
  <tr><td><b>Mode de paiement :</b></td><td align="right">'.$modePaiement.'</td></tr>
  <tr><td><b>Client :</b></td><td align="right">'.$clientNom.'</td></tr>
  <tr><td><b>Téléphone :</b></td><td align="right">'.$clientTel.'</td></tr>';

// Ajout éventuel des remises
if (!empty($vente['remise_pct']) && $vente['remise_pct'] > 0) {
    $html .= '<tr><td><b>Remise :</b></td><td align="right">'.$vente['remise_pct'].' %</td></tr>';
}
if (!empty($vente['remise_montant']) && $vente['remise_montant'] > 0) {
    $html .= '<tr><td><b>Remise :</b></td><td align="right">'.number_format($vente['remise_montant'],2,',',' ')." €</td></tr>";
}

$html .= '
  <tr><td><b>Total TTC :</b></td><td align="right"><strong>'.number_format($total_general,2,',',' ')." €</strong></td></tr>
</table>
<hr>
<div style='text-align:center;'>
  <small>".($type_ticket === 'acompte'
    ? "Ce ticket confirme un acompte sur une réparation."
    : "Merci pour votre confiance 🙏")."</small><br>
  <small>www.remobiles.com</small>
</div>
";

// --- Impression PDF ---
$pdf->writeHTML($html, true, false, true, false, '');
ob_clean(); // éviter "Some data already output"
$nomFichier = !empty($vente['ref_vente']) ? $vente['ref_vente'] : 'ticket_'.$id;
$pdf->Output($nomFichier.'.pdf', 'I');
?>
