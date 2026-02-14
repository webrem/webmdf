<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ob_clean();
ob_start();
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/sync_time.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

// === Connexion base ===
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);

// === Infos entreprise ===
$entreprise_nom    = "R.E.Mobiles";
$forme_juridique   = "SASU";
$rcs               = "834 693 301 R.C.S. Cayenne";
$adresse           = "104 bis avenue Général de Gaulle, 97300 Cayenne";
$activite          = "Travaux de réparation de téléphones et tablettes";
$telephone         = "+594 694 27 40 51";

// === Données client et formulaire ===
$clientNom   = trim($_POST['clientNom'] ?? 'Client');
$clientTel   = trim($_POST['clientTel'] ?? '—');
$pieces      = array_filter(array_map('trim', $_POST['piece'] ?? []));
$docType     = strtoupper(trim($_POST['docType'] ?? 'DEVIS'));

// ✅ Récupération du prix final depuis la session (valeur enregistrée par store_price.php)
$prixFinal = isset($_SESSION['dernier_prix_final']) ? floatval($_SESSION['dernier_prix_final']) : 0.00;

// 🔒 Sécurité : si aucune valeur, on garde 0.00
if ($prixFinal <= 0) $prixFinal = 0.00;


/* ============================================================
   ✅ AJOUT DEMANDÉ : Référence unique DEVIS / FACTURE / PROFORMA
   ============================================================ */
function generateReference($type){
    $type = strtoupper($type);
    $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)); // 8 caractères
    return $type . "-REF" . $random;
}
$referenceDoc = generateReference($docType);
// (optionnel) garder en session si besoin ailleurs
$_SESSION['dernier_reference_doc'] = $referenceDoc;
/* ============================== FIN AJOUT ==================== */


// === Création du PDF A4 ===
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator($entreprise_nom);
$pdf->SetAuthor($entreprise_nom);
$pdf->SetTitle("$docType - $entreprise_nom");
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(TRUE, 18);
$pdf->AddPage();

// === Filigrane central transparent ===
$bgLogo = __DIR__ . '/logo-rem.png';
if (file_exists($bgLogo)) {
    $pdf->SetAlpha(0.08);
    $pdf->Image($bgLogo, 30, 100, 150, '', 'PNG', '', '', false, 300, '', false, false, 0);
    $pdf->SetAlpha(1);
}

// === En-tête entreprise ===
$logoPath = __DIR__ . '/logo-rem.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 35);
}
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $entreprise_nom, 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, "$forme_juridique — $rcs", 0, 1, 'R');
$pdf->Cell(0, 5, $adresse, 0, 1, 'R');
$pdf->Cell(0, 5, "Tél : $telephone", 0, 1, 'R');
$pdf->Ln(10);

// === Titre principal ===
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, "DOCUMENT : $docType", 0, 1, 'C');
$pdf->Ln(5);

// === Infos client ===
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(100, 8, "Client : " . htmlspecialchars($clientNom), 0, 0, 'L');
$pdf->Cell(0, 8, "Date : " . date('d/m/Y'), 0, 1, 'R');

// ✅ AJOUT DEMANDÉ : affichage référence
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(100, 8, "Référence : " . $referenceDoc, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
// FIN AJOUT

$pdf->Cell(100, 8, "Téléphone : " . htmlspecialchars($clientTel), 0, 1, 'L');
$pdf->Ln(8);

// === Tableau Quantité + Désignation ===
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(220, 53, 69);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(40, 8, "Quantité", 1, 0, 'C', true);
$pdf->Cell(150, 8, "Désignation", 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// ✅ Chaque article une seule fois, quantité = 1
if (!empty($pieces)) {
    foreach (array_unique($pieces) as $nomPiece) {
        $pdf->Cell(40, 8, "1", 1, 0, 'C');
        $pdf->MultiCell(150, 8, htmlspecialchars($nomPiece), 1, 'L', false, 1);
    }
} else {
    $pdf->Cell(190, 8, "Aucun article spécifié.", 1, 1, 'C');
}

// === Total final TTC ===
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(130, 10, "TOTAL TTC", 1, 0, 'R', true);
$pdf->Cell(60, 10, number_format($prixFinal, 2, ',', ' ') . " €", 1, 1, 'R', true);
$pdf->Ln(8);

// === Mention finale ===
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell(
    0,
    8,
    "Merci pour votre confiance.\nPour toute question : $telephone\n\n$entreprise_nom — $activite\n$adresse",
    0,
    'C'
);

// === Signature / tampon semi-transparent ===
$signaturePath = __DIR__ . '/signature.png';
if (file_exists($signaturePath)) {
    $pdf->StartTransform();
    $pdf->Rotate(5, 50, 100);
    $pdf->SetAlpha(0.3);
    $pdf->Image($signaturePath, 80, 215, 60);
    $pdf->StopTransform();
    $pdf->SetAlpha(1);
}

// === Nettoyage et sortie ===
unset($_SESSION['dernier_prix_final']); // Supprime la variable après usage
ob_end_clean();
$pdf->Output("Devis_A4.pdf", "I");
exit;
?>
