<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
require_once __DIR__ . '/tcpdf/tcpdf.php';

// Vérification des paramètres
if (!isset($_GET['vendeur'], $_GET['date_debut'], $_GET['date_fin'])) {
    die("Paramètres manquants.");
}

date_default_timezone_set("America/Cayenne");

$vendeur    = trim($_GET['vendeur']);
$dateDebut  = $_GET['date_debut'] . " 00:00:00";
$dateFin    = $_GET['date_fin'] . " 23:59:59";

// Connexion MySQL
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur de connexion DB");
$conn->set_charset("utf8mb4");

/* =======================================================
   CALCUL DES TOTAUX
======================================================= */

// --- Total ventes classiques (POS)
$stmtV = $conn->prepare("
    SELECT SUM(prix_total) AS total_ventes
    FROM ventes
    WHERE vendeur=? AND date_vente BETWEEN ? AND ?
");
$stmtV->bind_param("sss", $vendeur, $dateDebut, $dateFin);
$stmtV->execute();
$totalVentes = (float)($stmtV->get_result()->fetch_assoc()['total_ventes'] ?? 0);
$stmtV->close();

// --- Acomptes réparations (ref contenant REM ou POS-ACOMPTE)
$stmtA = $conn->prepare("
    SELECT SUM(prix_total) AS total_acomptes_rep
    FROM ventes_historique
    WHERE vendeur=? 
    AND LOWER(type)='acompte'
    AND (
        ref_vente LIKE '%REM%' 
        OR ref_vente LIKE 'POS-ACOMPTE-%'
    )
    AND date_vente BETWEEN ? AND ?
");
$stmtA->bind_param("sss", $vendeur, $dateDebut, $dateFin);
$stmtA->execute();
$totalAcomptesReparations = (float)($stmtA->get_result()->fetch_assoc()['total_acomptes_rep'] ?? 0);
$stmtA->close();

// --- Acomptes commandes (ref sans REM)
$stmtC = $conn->prepare("
    SELECT SUM(prix_total) AS total_acomptes_cmd
    FROM ventes_historique
    WHERE vendeur=? 
    AND LOWER(type)='acompte'
    AND (
        ref_vente LIKE 'ACOMPTE-%'
        OR ref_vente LIKE 'CMD-%'
        OR ref_vente LIKE 'COMMANDE-%'
    )
    AND ref_vente NOT LIKE '%REM%'
    AND date_vente BETWEEN ? AND ?
");
$stmtC->bind_param("sss", $vendeur, $dateDebut, $dateFin);
$stmtC->execute();
$totalAcomptesCommandes = (float)($stmtC->get_result()->fetch_assoc()['total_acomptes_cmd'] ?? 0);
$stmtC->close();

/* =======================================================
   COMMISSIONS
======================================================= */
$commissionVente       = $totalVentes * 0.15;
$commissionReparation  = $totalAcomptesReparations * 0.20;
$commissionCommande    = $totalAcomptesCommandes * 0.20;

$totalGlobal     = $totalVentes + $totalAcomptesReparations + $totalAcomptesCommandes;
$totalCommission = $commissionVente + $commissionReparation + $commissionCommande;

/* =======================================================
   GÉNÉRATION DU PDF THERMIQUE 80mm
======================================================= */
$pdf = new TCPDF('P', 'mm', [80, 200], true, 'UTF-8', false);
$pdf->SetMargins(4, 4, 4);
$pdf->AddPage();


// --- Logo ---
$pageWidth = $pdf->getPageWidth();
$logoWidth = 50;
$x = ($pageWidth - $logoWidth) / 2;
$logoPath = __DIR__ . "/logo-rem.png";
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $x, 5, $logoWidth);
    $pdf->Ln(33);
}


// Informations société
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, ' ', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, '104 bis avenue Général de Gaulle, 97300 Cayenne', 0, 1, 'C');
$pdf->Cell(0, 5, 'Téléphone : +594 694 27 40 51', 0, 1, 'C');
$pdf->Ln(2);

// Titre du rapport
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, "RAPPORT VENDEUR - " . strtoupper($vendeur), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, "Période : " . date('d/m/Y', strtotime($_GET['date_debut'])) . " au " . date('d/m/Y', strtotime($_GET['date_fin'])), 0, 1, 'C');
$pdf->Ln(3);

// Détail des commissions
$pdf->Ln(3);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, '----------------------------------------', 0, 1, 'C');
$pdf->Cell(0, 6, 'DÉTAIL DES COMMISSIONS', 0, 1, 'C');
$pdf->Cell(0, 6, '----------------------------------------', 0, 1, 'C');
$pdf->Ln(1);

$pdf->Cell(48, 6, "Ventes (15%) :", 0, 0, 'L');
$pdf->Cell(25, 6, number_format($commissionVente, 2, ',', ' ') . " €", 0, 1, 'R');

$pdf->Cell(48, 6, "Réparations (20%) :", 0, 0, 'L');
$pdf->Cell(25, 6, number_format($commissionReparation, 2, ',', ' ') . " €", 0, 1, 'R');

$pdf->Cell(48, 6, "Commandes (20%) :", 0, 0, 'L');
$pdf->Cell(25, 6, number_format($commissionCommande, 2, ',', ' ') . " €", 0, 1, 'R');

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(48, 7, "Commission totale :", 0, 0, 'L');
$pdf->Cell(25, 7, number_format($totalCommission, 2, ',', ' ') . " €", 0, 1, 'R');

// Pied du document
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, "Date d'édition : " . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Cell(0, 5, "Signature du vendeur : ____________________", 0, 1, 'L');

$pdf->Output("ticket_vendeur_$vendeur.pdf", 'I');
?>
