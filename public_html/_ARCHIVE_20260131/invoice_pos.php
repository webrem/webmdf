<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
require_once __DIR__.'/tcpdf/tcpdf.php';

// Connexion DB
$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if($conn->connect_error) die("Erreur DB");

$ref = $_GET['ref'] ?? '';
if(!$ref) die("Référence manquante.");

// Récupération lignes facture
$stmt=$conn->prepare("SELECT * FROM ventes_historique WHERE ref_vente=?");
$stmt->bind_param("s",$ref);
$stmt->execute();
$res=$stmt->get_result();
if($res->num_rows===0) die("Facture introuvable");

$data=$res->fetch_all(MYSQLI_ASSOC);
$vente=$data[0];

// Totaux
$sous_total=0;
foreach($data as $d){
  $sous_total += $d['prix_total'];
}

// ⚠️ Ici je suppose que tu as ajouté `remise_pct` et `remise_montant` dans ventes_historique.
// Si ce n’est pas encore fait, il faudra les ajouter en SQL (je peux t’envoyer la requête).
$remise_pct = isset($vente['remise_pct']) ? (int)$vente['remise_pct'] : 0;
$remise_montant = isset($vente['remise_montant']) ? (float)$vente['remise_montant'] : 0;

// Application de la remise au total
if ($remise_montant > 0) {
    $total_final = max($sous_total - $remise_montant, 0);
} elseif ($remise_pct > 0) {
    $total_final = $sous_total - ($sous_total * $remise_pct / 100);
} else {
    $total_final = $sous_total;
}

// PDF
$pdf = new TCPDF();
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

// --- Logo
$logoPath="logo-rem.png";
if(file_exists($logoPath)){
    $pdf->Image($logoPath,15,12,30);
}

// --- Infos entreprise
$pdf->SetXY(50,12);
$pdf->SetFont("dejavusans","",10);
$pdf->MultiCell(0,5,"R.E.Mobiles
SASU au capital de 100 €
RCS Cayenne 932 352 149
104 bis Avenue Général de Gaulle
97300 Cayenne
Tél : +594 694 27 40 51",0,'L');

// --- Bandeau Facture
$pdf->Ln(25);
$pdf->SetFont("dejavusans","B",16);
$pdf->SetFillColor(0,122,94); // Vert
$pdf->SetTextColor(255);
$pdf->Cell(0,14,"FACTURE",0,1,"C",1);

$pdf->SetTextColor(0);
$pdf->SetFont("dejavusans","",11);
$pdf->Cell(0,8,"Référence : ".$ref,0,1,"C");
$pdf->Ln(6);

// --- Infos client
$pdf->SetFont("dejavusans","B",11);
$pdf->Cell(0,8,"Informations Client",0,1);
$pdf->SetFont("dejavusans","",10);
$pdf->MultiCell(0,6,"Nom : ".$vente['client_nom']."\nTéléphone : ".$vente['client_tel'],0,'L');
$pdf->Ln(5);

// --- Tableau Articles
$html = '
<style>
table { border-collapse: collapse; width: 100%; }
th { background-color: #007a5e; color: #fff; font-weight: bold; text-align:center; }
td { border: 1px solid #ccc; padding: 6px; font-size: 10pt; }
.total { font-weight: bold; background:#f2f2f2; }
</style>
<table>
<tr>
  <th>Qté</th>
  <th>Désignation</th>
  <th>PU (€)</th>
  <th>Remise</th>
  <th>Total (€)</th>
</tr>';
foreach($data as $d){
    $q = (int)$d['quantite'];
    $designation = htmlspecialchars($d['designation']);
    $pu = number_format($d['prix_total'] / max($q,1), 2, ',', ' ');

    $remise_cell = "-";
    if(!empty($d['remise_pct']) && $d['remise_pct'] > 0) {
        $remise_cell = $d['remise_pct']." %";
    }
    if(!empty($d['remise_montant']) && $d['remise_montant'] > 0) {
        $remise_cell = number_format($d['remise_montant'],2,',',' ')." €";
    }

    $total_ligne = number_format($d['prix_total'], 2, ',', ' ');
    $html.="<tr>
      <td align='center'>$q</td>
      <td>$designation</td>
      <td align='right'>$pu</td>
      <td align='center'>$remise_cell</td>
      <td align='right'>$total_ligne</td>
    </tr>";
}

$html.="</table>";
$pdf->writeHTML($html,true,false,false,false,'');

// --- Résumé
$pdf->Ln(6);
$pdf->SetFont("dejavusans","",11);
$pdf->Cell(140,8,"Sous-total",1,0,'R');
$pdf->Cell(40,8,number_format($sous_total,2,',',' ')." €",1,1,'R');

if($remise_pct>0){
    $pdf->Cell(140,8,"Remise ($remise_pct %)",1,0,'R');
    $pdf->Cell(40,8,"- ".number_format($sous_total*$remise_pct/100,2,',',' ')." €",1,1,'R');
}
if($remise_montant>0){
    $pdf->Cell(140,8,"Remise (montant)",1,0,'R');
    $pdf->Cell(40,8,"- ".number_format($remise_montant,2,',',' ')." €",1,1,'R');
}

$pdf->SetFont("dejavusans","B",12);
$pdf->Cell(140,10,"TOTAL TTC",1,0,'R',false);
$pdf->Cell(40,10,number_format($total_final,2,',',' ')." €",1,1,'R',false);

// --- Paiement
$pdf->Ln(10);
$pdf->SetFont("dejavusans","",10);
$pdf->Cell(0,6,"Mode de paiement : ".$vente['mode_paiement'],0,1);

// --- Bas de page
$pdf->Ln(15);
$pdf->SetFont("dejavusans","I",9);
$pdf->Cell(0,6,"Merci pour votre confiance. Pour toute question : +594 694 27 40 51",0,1,"C");
$pdf->Ln(4);
$pdf->Cell(0,6,"Fait à Cayenne, le ".date('d/m/Y'),0,1,"R");

$pdf->Output("facture_$ref.pdf","I");
