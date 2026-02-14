<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ob_clean();
ob_start();
require_once(__DIR__ . '/tcpdf/tcpdf.php');
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur MySQL : " . $conn->connect_error); }

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM historiques WHERE id = $id LIMIT 1");
if (!$result || $result->num_rows === 0) { die("Ticket non trouvé."); }

$data = $result->fetch_assoc();

function arrondirPrix($prix) {
    return ($prix < 45) ? 39.99 : ceil($prix / 5) * 5 - 0.01;
}

$prixFinal = arrondirPrix($data['prix_final']);

// --- Format ticket thermique
$pdf = new TCPDF('P', 'mm', array(80, 130), true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 11); // police en gras

// --- Logo
$pdf->Image(__DIR__ . '/logo-rem.png', 20, '', 40, 40, '', '', 'T', false, 300, '', false, false, 0, false, false, true);
$pdf->Ln(20);

// --- Contenu ticket
$html = "<div style='text-align:center; font-weight:bold;'>
<h3>*** TICKET ***</h3>
R.E.Mobiles<br>
106Bis Avenue Général De Gaulle<br>
SIRET : 932 352 149 00011<br>
Whatsapp : 0694274051
<hr style='border:0;border-top:1px dashed #000;'>
Client : {$data['client_nom']}<br>
Tel : {$data['client_tel']}<br>
Pièce : {$data['piece']}<br>
Quantité : {$data['quantite']}<br>
Prix final TTC : " . number_format($prixFinal, 2) . " €<br>
<hr style='border:0;border-top:1px dashed #000;'>
MERCI POUR VOTRE CONFIANCE !<br><br>
</div>";

$pdf->writeHTML($html, true, false, true, false, '');

// --- Nettoyage buffer
while (ob_get_level()) {
    ob_end_clean();
}
$pdf->Output("ticket_rem.pdf", "I");
exit;
?>
