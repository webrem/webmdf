
<?php
ob_clean();
ob_start();
require_once(__DIR__ . '/tcpdf/tcpdf.php');

function arrondirPrix($prix) {
    if ($prix < 45) {
        return 39.99;
    } else {
        $palier = ceil($prix / 5) * 5;
        return $palier - 0.01;
    }
}

$piece = $_POST['piece'];
$prixAchat = floatval($_POST['prixAchat']);
$quantite = intval($_POST['quantite']);
$mainOeuvre = floatval($_POST['mainOeuvre']);
$clientNom = $_POST['clientNom'];
$clientTel = $_POST['clientTel'];
$docType = $_POST['docType'];

$fraisEnvoi = 23.0;
$tauxDouane = 0.25;
$margeMagasin = 0.25;

$totalAchat = $prixAchat * $quantite;
$totalFacture = $totalAchat + $fraisEnvoi;
$fraisDouane = $totalFacture * $tauxDouane;
$coutParPiece = ($totalAchat + $fraisEnvoi + $fraisDouane) / $quantite;
$prixMagasin = $coutParPiece * (1 + $margeMagasin);
$prixFinalBase = $prixMagasin + $mainOeuvre;
$prixFinal = arrondirPrix($prixFinalBase);

// Création du PDF format ticket
$pdf = new TCPDF('P', 'mm', array(80, 130), true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Logo centré
$pdf->Image(__DIR__ . '/logo-rem.png', 20, '', 40, 15, '', '', 'T', false, 300, '', false, false, 0, false, false, true);
$pdf->Ln(20);

// Texte centralisé
$html = "<div style='text-align:center;'>
<h3>*** {$docType} ***</h3>
R.E.Mobiles<br>
106Bis Avenue Général De Gaulle<br>
SIRET : 932 352 149 00011<br>
Whatsapp : 0694274051
<hr style='border:0;border-top:1px dashed #000;'>
Client : {$clientNom}<br>
Tel : {$clientTel}<br>
Pièce : {$piece}<br>
Quantité : {$quantite}<br>
Prix final TTC : " . number_format($prixFinal, 2) . " €<br>
<hr style='border:0;border-top:1px dashed #000;'>
Merci pour votre confiance !<br><br>
</div>";

$pdf->writeHTML($html, true, false, true, false, '');

// QR Code centré
$qrTel = preg_replace('/[^0-9]/', '', $clientTel);
$qrMessage = urlencode("Bonjour {$clientNom}, voici votre {$docType} pour la pièce : {$piece} d’un total de " . number_format($prixFinal, 2) . " €.");
$qrUrl = "https://wa.me/{$qrTel}?text={$qrMessage}";
$pdf->write2DBarcode($qrUrl, 'QRCODE,H', '', '', 18, 18, [], 'C');

$pdf->Ln(20);
$pdf->MultiCell(0, 5, "Scanner ce QR Code pour ouvrir WhatsApp", 0, 'C');

while (ob_get_level()) {
    ob_end_clean();
}
$pdf->Output("ticket_rem.pdf", "I");
exit;
?>
