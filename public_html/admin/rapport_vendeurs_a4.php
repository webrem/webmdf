<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();

/* ============================
   DEBUG (à retirer après test)
============================ */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ============================
   SÉCURITÉ ADMIN
============================ */
$isAdmin = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') $isAdmin = true;
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') $isAdmin = true;
if (!$isAdmin) die("Accès refusé");

/* ============================
   INCLUDES
============================ */
require_once __DIR__ . '/../sync_time.php';
require_once __DIR__ . '/../tcpdf/tcpdf.php';

date_default_timezone_set("America/Cayenne");

/* ============================
   PARAMÈTRES
============================ */
if (!isset($_GET['vendeur'], $_GET['date_debut'], $_GET['date_fin'])) {
    die("Paramètres manquants");
}

$vendeur   = trim($_GET['vendeur']);
$dateDebut = $_GET['date_debut'] . " 00:00:00";
$dateFin   = $_GET['date_fin']   . " 23:59:59";

/* ============================
   DB
============================ */
$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
if ($conn->connect_error) die("Erreur DB");
$conn->set_charset("utf8mb4");
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

/* ============================
   DONNÉES UNIFIÉES (COLLATION FIXÉE)
============================ */
$stmt = $conn->prepare("
    (
        SELECT 
            date_vente,
            CONCAT('POS-', id) COLLATE utf8mb4_unicode_ci AS ref_vente,
            'Vente POS' COLLATE utf8mb4_unicode_ci AS designation,
            prix_total,
            'vente' COLLATE utf8mb4_unicode_ci AS type
        FROM ventes
        WHERE vendeur = ?
          AND date_vente BETWEEN ? AND ?
    )
    UNION ALL
    (
        SELECT
            date_vente,
            ref_vente COLLATE utf8mb4_unicode_ci,
            designation COLLATE utf8mb4_unicode_ci,
            prix_total,
            type COLLATE utf8mb4_unicode_ci
        FROM ventes_historique
        WHERE vendeur = ?
          AND date_vente BETWEEN ? AND ?
    )
    ORDER BY date_vente ASC
");

$stmt->bind_param(
    "ssssss",
    $vendeur,
    $dateDebut,
    $dateFin,
    $vendeur,
    $dateDebut,
    $dateFin
);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Aucune activité pour ce vendeur sur la période");
}

/* ============================
   TOTAUX
============================ */
$totalV  = 0;
$totalAR = 0;
$totalAC = 0;

/* ============================
   PDF A4
============================ */
$pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
$pdf->SetMargins(10,15,10);
$pdf->SetAutoPageBreak(true,15);
$pdf->AddPage();

/* ---- LOGO ---- */
$logo = __DIR__ . '/../logo-rem.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 80, 10, 50);
    $pdf->Ln(30);
}

/* ---- EN-TÊTE ---- */
$pdf->SetFont('helvetica','B',14);
$pdf->Cell(0,8,"RAPPORT D’ACTIVITÉ VENDEUR",0,1,'C');

$pdf->SetFont('helvetica','',11);
$pdf->Cell(0,7,"Vendeur : ".strtoupper($vendeur),0,1,'C');
$pdf->Cell(
    0,7,
    "Période du ".date('d/m/Y',strtotime($_GET['date_debut'])).
    " au ".date('d/m/Y',strtotime($_GET['date_fin'])),
    0,1,'C'
);
$pdf->Ln(6);

/* ============================
   TABLEAU
============================ */
$pdf->SetFont('helvetica','B',9);
$pdf->SetFillColor(230,230,230);

$pdf->Cell(35,8,'Date & Heure',1,0,'C',true);
$pdf->Cell(40,8,'Référence',1,0,'C',true);
$pdf->Cell(75,8,'Désignation',1,0,'C',true);
$pdf->Cell(25,8,'Type',1,0,'C',true);
$pdf->Cell(20,8,'Montant €',1,1,'C',true);

$pdf->SetFont('helvetica','',9);

/* ============================
   PARCOURS + CALCULS
============================ */
while ($r = $result->fetch_assoc()) {

    $type = strtolower($r['type']);

    if ($type === 'vente') {
        $totalV += $r['prix_total'];
    } elseif ($type === 'acompte') {
        if (strpos($r['ref_vente'], 'REM') !== false) {
            $totalAR += $r['prix_total'];
        } else {
            $totalAC += $r['prix_total'];
        }
    }

    $designation = trim($r['designation']);
    if ($designation === '') $designation = ucfirst($type);

    $pdf->Cell(35,8,date('d/m/Y H:i',strtotime($r['date_vente'])),1);
    $pdf->Cell(40,8,$r['ref_vente'],1);
    $pdf->Cell(75,8,$designation,1);
    $pdf->Cell(25,8,ucfirst($type),1);
    $pdf->Cell(20,8,number_format($r['prix_total'],2,',',' '),1,1,'R');
}

/* ============================
   COMMISSIONS
============================ */
$commissionV  = $totalV  * 0.15;
$commissionAR = $totalAR * 0.20;
$commissionAC = $totalAC * 0.20;
$totalCommission = $commissionV + $commissionAR + $commissionAC;

/* ============================
   RÉCAPITULATIF
============================ */
$pdf->Ln(6);
$pdf->SetFont('helvetica','B',11);
$pdf->Cell(0,8,"RÉCAPITULATIF & COMMISSIONS",0,1);

$pdf->SetFont('helvetica','',10);
$pdf->Cell(140,8,"Ventes POS",1);                  $pdf->Cell(40,8,number_format($totalV,2,',',' ')." €",1,1,'R');
$pdf->Cell(140,8,"Commission ventes (15%)",1);     $pdf->Cell(40,8,number_format($commissionV,2,',',' ')." €",1,1,'R');

$pdf->Cell(140,8,"Acomptes réparations",1);         $pdf->Cell(40,8,number_format($totalAR,2,',',' ')." €",1,1,'R');
$pdf->Cell(140,8,"Commission réparations (20%)",1); $pdf->Cell(40,8,number_format($commissionAR,2,',',' ')." €",1,1,'R');

$pdf->Cell(140,8,"Acomptes commandes",1);           $pdf->Cell(40,8,number_format($totalAC,2,',',' ')." €",1,1,'R');
$pdf->Cell(140,8,"Commission commandes (20%)",1);   $pdf->Cell(40,8,number_format($commissionAC,2,',',' ')." €",1,1,'R');

$pdf->SetFont('helvetica','B',11);
$pdf->Cell(140,9,"TOTAL COMMISSION",1);             $pdf->Cell(40,9,number_format($totalCommission,2,',',' ')." €",1,1,'R');

/* ============================
   SAUVEGARDE + AFFICHAGE
============================ */
$dossier = __DIR__ . "/rapports_vendeurs/";
if (!is_dir($dossier)) mkdir($dossier,0777,true);

$nom = "rapport_".preg_replace('/[^a-zA-Z0-9_-]/','_',$vendeur)."_".date('Ymd_His').".pdf";
$pdf->Output($dossier.$nom,'F');
$pdf->Output($nom,'I');

exit;
