<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/sync_time.php';

require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$id = (int)($_GET['id'] ?? 0);
$type = strtoupper(trim($_GET['type'] ?? 'FACTURE')); // Par défaut FACTURE

$res = $conn->query("SELECT * FROM historiques WHERE id=$id");
if (!$res || $res->num_rows === 0) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Ticket introuvable.</h3>");
}
$data = $res->fetch_assoc();

// === Données société ===
$entreprise_nom = "R.E.Mobiles";
$forme_juridique = "SASU";
$rcs = "834 693 301 R.C.S. Cayenne";
$adresse = "104 bis avenue Général de Gaulle, 97300 Cayenne";
$activite = "Travaux de réparation de téléphones et appareils électroniques";
$telephone = "+594 694 27 40 51";

// === Création du PDF ===
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('R.E.Mobiles');
$pdf->SetAuthor('R.E.Mobiles');
$pdf->SetTitle("$type - R.E.Mobiles");
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// === En-tête ===
$logoPath = __DIR__ . '/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 10, 35);
}
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $entreprise_nom, 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, "$forme_juridique — $rcs", 0, 1, 'R');
$pdf->Cell(0, 5, $adresse, 0, 1, 'R');
$pdf->Cell(0, 5, "Tél : $telephone", 0, 1, 'R');
$pdf->Ln(10);

// === Titre ===
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, "DOCUMENT : $type", 0, 1, 'C');
$pdf->Ln(4);

// === Informations client ===
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(100, 8, "Client : " . $data['client_nom'], 0, 0, 'L');
$pdf->Cell(0, 8, "Date : " . date('d/m/Y'), 0, 1, 'R');
$pdf->Cell(100, 8, "Téléphone : " . $data['client_tel'], 0, 1, 'L');
$pdf->Ln(8);

// === En-têtes du tableau ===
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(13, 202, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(60, 8, "Pièce", 1, 0, 'C', true);
$pdf->Cell(30, 8, "Réf. Fourn.", 1, 0, 'C', true);
$pdf->Cell(30, 8, "Fournisseur", 1, 0, 'C', true);
$pdf->Cell(20, 8, "Qté", 1, 0, 'C', true);
$pdf->Cell(40, 8, "Prix TTC (€)", 1, 1, 'C', true);

// === Contenu du tableau (multi-lignes auto-ajustées) ===
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0,0,0);

$w_piece = 60;
$w_ref = 30;
$w_fourn = 30;
$w_qte = 20;
$w_prix = 40;

// Calcul de la hauteur nécessaire pour chaque cellule
$h_piece = $pdf->getStringHeight($w_piece, $data['piece']);
$h_ref = $pdf->getStringHeight($w_ref, $data['ref_piece']);
$h_fourn = $pdf->getStringHeight($w_fourn, $data['fournisseur']);
$lineHeight = max($h_piece, $h_ref, $h_fourn, 8); // minimum 8mm

// Position de départ
$x = $pdf->GetX();
$y = $pdf->GetY();

// MultiCell() pour texte long (saut de ligne automatique)
$pdf->MultiCell($w_piece, $lineHeight, $data['piece'], 1, 'L', false, 0, '', '', true, 0, false, true, $lineHeight, 'M');
$pdf->MultiCell($w_ref, $lineHeight, $data['ref_piece'], 1, 'C', false, 0, '', '', true, 0, false, true, $lineHeight, 'M');
$pdf->MultiCell($w_fourn, $lineHeight, $data['fournisseur'], 1, 'C', false, 0, '', '', true, 0, false, true, $lineHeight, 'M');
$pdf->MultiCell($w_qte, $lineHeight, $data['quantite'], 1, 'C', false, 0, '', '', true, 0, false, true, $lineHeight, 'M');
$pdf->MultiCell($w_prix, $lineHeight, number_format($data['prix_final'],2,',',' '), 1, 'R', false, 1, '', '', true, 0, false, true, $lineHeight, 'M');

$pdf->Ln(6);

// === Total général ===
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(220, 248, 255);
$pdf->Cell(140, 10, "TOTAL TTC", 1, 0, 'R', true);
$pdf->Cell(40, 10, number_format($data['prix_final'],2,',',' ') . " €", 1, 1, 'R', true);
$pdf->Ln(10);

// === Pied de page ===
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 8, 
"Merci pour votre confiance.\nPour toute question : $telephone\n\n" .
"$entreprise_nom — $activite\n$adresse", 
0, 'C');

// === Enregistrement et affichage ===
$fileName = "{$type}_Ticket_{$id}_" . date('Ymd_His') . ".pdf";
$folder = __DIR__ . "/devis";
if (!is_dir($folder)) {
    mkdir($folder, 0775, true); // ✅ Crée le dossier s'il n'existe pas
}
$filePath = "$folder/$fileName";

// Enregistre + affiche
$pdf->Output($filePath, 'F'); 
$pdf->Output($fileName, 'I');
?>
