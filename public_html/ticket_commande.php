<?php
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';
require_once __DIR__ . "/tcpdf/tcpdf.php";

/* =========================
   SÉCURITÉ
   ========================= */
if (!isset($_SESSION['user_id'])) {
    die("Accès refusé.");
}

/* =========================
   DB
   ========================= */
$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) {
    die("Erreur DB : " . $conn->connect_error);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Commande invalide.");
}

/* =========================
   COMMANDE
   ========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM historiques
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$commande = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$commande) {
    die("Commande introuvable.");
}

/* =========================
   ACOMPTES
   ========================= */
$stmt = $conn->prepare("
    SELECT SUM(montant) AS total
    FROM acomptes_commandes
    WHERE commande_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$acc = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalAcc = (float)($acc['total'] ?? 0);
$prixFinal = (float)($commande['prix_final'] ?? 0);
$reste = max(0, $prixFinal - $totalAcc);

/* =========================
   PDF (80mm)
   ========================= */
$pdf = new TCPDF('P', 'mm', [80, 150], true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 9);

/* --- LOGO --- */
$pageWidth = $pdf->getPageWidth();
$logoWidth = 30;
$x = ($pageWidth - $logoWidth) / 2;
$logoPath = __DIR__ . "/logo-rem.png";

if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $x, 5, $logoWidth);
    $pdf->Ln(25);
}

/* --- TITRE --- */
$pdf->SetFont("helvetica", "B", 11);
$pdf->MultiCell(0, 6, "BON DE COMMANDE", 0, 'C');
$pdf->Ln(1);

/* --- DATE --- */
$dateCommande = !empty($commande['date_enregistrement'])
    ? date("d/m/Y H:i", strtotime($commande['date_enregistrement']))
    : date("d/m/Y H:i");

$pdf->SetFont("helvetica", "", 9);
$pdf->MultiCell(0, 5, "Date : $dateCommande", 0, 'C');
$pdf->Ln(2);

/* --- ENTREPRISE --- */
$pdf->MultiCell(
    0,
    5,
    "R.E.Mobiles\n104Bis Avenue Général De Gaulle\nSIRET : 932 352 149 00011\nWhatsApp : 0694274051",
    0,
    'C'
);
$pdf->Ln(2);

/* --- CLIENT --- */
$clientNom = htmlspecialchars($commande['client_nom'] ?? '-', ENT_QUOTES, 'UTF-8');
$clientTel = htmlspecialchars($commande['client_tel'] ?? '-', ENT_QUOTES, 'UTF-8');

$pdf->MultiCell(0, 5, "Client : $clientNom", 0, 'L');
$pdf->MultiCell(0, 5, "Tel : $clientTel", 0, 'L');
$pdf->Ln(1);

/* --- SÉPARATEUR --- */
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

/* --- DÉTAILS --- */
$piece = htmlspecialchars($commande['piece'] ?? '-', ENT_QUOTES, 'UTF-8');
$quantite = (int)($commande['quantite'] ?? 1);

$html = "
<b>Pièce :</b> $piece<br>
<b>Quantité :</b> $quantite<br>
";
$pdf->writeHTML($html);

/* --- SÉPARATEUR --- */
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

/* --- TOTAUX --- */
$html = "
<b>Total commande :</b> " . number_format($prixFinal, 2, ',', ' ') . " €<br>
<b>Acomptes versés :</b> -" . number_format($totalAcc, 2, ',', ' ') . " €<br>
<b><u>Reste à payer :</u></b> " . number_format($reste, 2, ',', ' ') . " €<br>
";
$pdf->writeHTML($html);

/* --- SÉPARATEUR --- */
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

/* --- MESSAGE --- */
$pdf->SetFont("helvetica", "I", 9);
$pdf->MultiCell(0, 5, "Merci pour votre confiance !", 0, 'C');

/* =========================
   SORTIE
   ========================= */
   ob_end_clean();
$pdf->Output("bon_commande_$id.pdf", "I");
