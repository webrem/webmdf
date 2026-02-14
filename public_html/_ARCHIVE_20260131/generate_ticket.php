<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once('tcpdf/tcpdf.php');

// Largeur pour imprimante thermique 80mm = 80 / 25.4 * 72 = ~226 points
$width = 226;
$height = 600; // tu peux mettre 0 pour "auto" si tu veux une hauteur dynamique

$pdf = new TCPDF('P', 'pt', array($width, $height), true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5); // petites marges
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->AddPage();

// --- En-tête ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 0, "R.E.Mobiles", 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 0, "104B Avenue Général de Gaulle", 0, 1, 'C');
$pdf->Cell(0, 0, "97300 Cayenne", 0, 1, 'C');
$pdf->Cell(0, 0, "Tél : +594 694 27 40 51", 0, 1, 'C');
$pdf->Ln(5);
$pdf->Cell(0, 0, str_repeat("-", 32), 0, 1, 'C');

// --- Corps (exemple infos appareil) ---
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 0, "Réf : REM-20250924-F4F8BA", 0, 'L');
$pdf->MultiCell(0, 0, "Client : Carlos Ramon", 0, 'L');
$pdf->MultiCell(0, 0, "Téléphone : 0694274051", 0, 'L');
$pdf->MultiCell(0, 0, "Appareil : iPhone 12", 0, 'L');
$pdf->MultiCell(0, 0, "Problème : Ecran cassé", 0, 'L');
$pdf->Ln(5);
$pdf->Cell(0, 0, str_repeat("-", 32), 0, 1, 'C');

// --- Pied ---
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(0, 0, "Merci pour votre confiance", 0, 'C');
$pdf->MultiCell(0, 0, "Signature client : __________", 0, 'L');

$pdf->Output('ticket.pdf', 'I'); // I = affichage dans navigateur
