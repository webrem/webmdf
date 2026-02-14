<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 🕒 Fuseau horaire
date_default_timezone_set('America/Cayenne');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Méthode non autorisée');
}

require_once('config.php');
require_once('tcpdf/tcpdf.php');

// === Données client ===
$nom = $_POST['client_nom'] ?? '';
$adresse = $_POST['client_adresse'] ?? '';
$telephone = $_POST['client_tel'] ?? '';
$emailClient = $_POST['client_email'] ?? '';
$paiement = $_POST['mode_paiement'] ?? '';

// === Données panier ===
$produits = $_SESSION['panier'] ?? [];
if (empty($produits)) {
  exit("Erreur : aucun produit.");
}

$type_document = 'FACTURE';
$prefix = 'FAC';
$date_now = date('Ymd');
$ref_code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, rand(3, 4)));
$reference = $prefix . $date_now . '-REF' . $ref_code;

// === Totaux ===
$total = 0;
foreach ($produits as $item) {
  $total += $item['prix'] * $item['quantite'];
}

// === Répertoires ===
$mois_fr = [
  'January'=>'janvier','February'=>'fevrier','March'=>'mars','April'=>'avril','May'=>'mai','June'=>'juin',
  'July'=>'juillet','August'=>'aout','September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'decembre'
];
$mois_nom = $mois_fr[date('F')] ?? strtolower(date('F'));
$dossier_nom = strtolower($type_document) . 's-' . $mois_nom;
$base_dir = __DIR__ . '/devis/' . $dossier_nom;
if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);

$filename = $reference . '.pdf';
$filepath = $base_dir . '/' . $filename;
$chemin_fichier_en_base = 'devis/' . $dossier_nom . '/' . $filename;

// === Logo ===
$logoPath = 'logo.png';
if (!file_exists($logoPath)) {
  exit("Erreur : logo manquant.");
}

// === TCPDF ===
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('R.E.Mobiles');
$pdf->SetTitle($type_document);
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

// Logo + entête société
$pdf->Image($logoPath, 15, 14, 30);
$pdf->SetXY(50, 14);
$pdf->SetFont('dejavusans', '', 10);
$pdf->MultiCell(0, 5,
"R.E.Mobiles
Société par actions simplifiée (SASU)
RCS : 932 352 149 R.C.S. Cayenne
104 bis Avenue Général de Gaulle
97300 Cayenne
Tél : +594 694 27 40 51",
0, 'L'
);

$pdf->Ln(25);
$pdf->SetFont('dejavusans','B',14);
$pdf->SetFillColor(200,50,50);
$pdf->SetTextColor(255);
$pdf->Cell(0,12,$type_document,0,1,'C',1);

$pdf->SetTextColor(0);
$pdf->SetFont('dejavusans','',10);
$pdf->Cell(0,6,"Référence : $reference",0,1,'C');
$pdf->Ln(6);

// Infos client
$pdf->SetFont('dejavusans','',10);
$pdf->Cell(0,6,"Client : $nom",0,1);
if(!empty($adresse)) $pdf->MultiCell(0,5,"Adresse : $adresse",0,'L');
if(!empty($telephone)) $pdf->Cell(0,6,"Téléphone : $telephone",0,1);
if(!empty($emailClient)) $pdf->Cell(0,6,"Email : $emailClient",0,1);

$pdf->Ln(5);

// === Tableau des articles ===
$html = '<style>
table { border-collapse: collapse; }
th { background-color: #c83232; color:#fff; font-weight:bold; }
td, th { border:1px solid #555; padding:5px; font-size:10pt; }
</style><table><tr>
<th>Qté</th><th>Désignation</th><th>PU (€)</th><th>Total (€)</th></tr>';
foreach($produits as $p){
  $tt = number_format($p['prix']*$p['quantite'],2,',',' ');
  $html .= "<tr>
  <td>{$p['quantite']}</td>
  <td>".htmlspecialchars($p['designation'])."</td>
  <td>".number_format($p['prix'],2,',',' ')."</td>
  <td>$tt</td>
  </tr>";
}
$html .= "<tr><td colspan='3' align='right'><b>Total TTC</b></td>
<td><b>".number_format($total,2,',',' ')." €</b></td></tr>";
$html .= "</table>";
$pdf->writeHTML($html,true,false,false,false,'');

$pdf->Ln(5);
$pdf->MultiCell(0,5,"Mode de paiement : $paiement",0,'L');
$pdf->Ln(5);

// Conditions facture
$pdf->SetFont('dejavusans','',9);
$pdf->MultiCell(0,5,
"Conditions de règlement : Paiement sous 7 jours à réception.
Pénalités de retard : 10 % + 40 € forfaitaires (Art. D441-5 C. Com.)",
0,'L'
);

$pdf->Ln(10);
$pdf->SetFont('dejavusans','I',9);
$pdf->Cell(0,5,"Merci pour votre confiance. Pour toute question : +594 694 27 40 51",0,1,'C');
$pdf->Ln(4);
$pdf->Cell(0,6,"Fait à Cayenne, le ".date('d/m/Y'),0,1,'R');

$pdf->Output($filepath,'F');

// Enregistrer en DB
$date_creation=(new DateTime("now",new DateTimeZone("America/Cayenne")))->format('Y-m-d H:i:s');
$stmt=$conn->prepare("INSERT INTO documents(type_document,nom_client,telephone,email,date_creation,reference,montant_total,chemin_fichier)
VALUES(?,?,?,?,?,?,?,?)");
$stmt->execute([$type_document,$nom,$telephone,$emailClient,$date_creation,$reference,$total,$chemin_fichier_en_base]);

header("Content-Type: application/pdf");
readfile($filepath);
exit;
