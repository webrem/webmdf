<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ob_clean();
ob_start();
session_start(); // ✅ indispensable pour transférer le prix vers preview_pdf.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once(__DIR__ . '/tcpdf/tcpdf.php');
require_once __DIR__ . '/sync_time.php';

// === Connexion MySQL ===
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
if ($conn->connect_error) {
    error_log("Erreur MySQL: " . $conn->connect_error);
}
if ($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone='-03:00'");
}

// === Fonction d’arrondi ===
function arrondirPrix($prix) {
    return ($prix < 45) ? 39.99 : ceil($prix / 5) * 5 - 0.01;
}

// === Action (save ou preview) ===
$action = $_GET['action'] ?? $_POST['action'] ?? 'save';

// === Données formulaire ===
$pieces      = $_POST['piece'] ?? [];
$prixAchats  = $_POST['prixAchat'] ?? [];
$quantite    = intval($_POST['quantite'] ?? 1);
$mainOeuvre  = floatval($_POST['mainOeuvre'] ?? 0);
$clientNom   = trim($_POST['clientNom'] ?? '');
$clientTel   = trim($_POST['clientTel'] ?? '');
$docType     = $_POST['docType'] ?? 'FACTURE';
$fournisseur = $_POST['fournisseur'] ?? null;
$refPiece    = $_POST['refPiece'] ?? null;

// === Calculs adaptés pour plusieurs articles ===
$fraisEnvoi   = 15.0;
$tauxDouane   = 0.25;
$margeMagasin = 0.25;

// Addition de tous les prix d'achat
$totalArticles = 0;
if (is_array($prixAchats)) {
    foreach ($prixAchats as $p) {
        $totalArticles += floatval($p);
    }
} else {
    $totalArticles = floatval($prixAchats);
}

// Même logique de calcul que ta version originale
$totalAchat     = $totalArticles * max(1, $quantite);
$totalFacture   = $totalAchat + $fraisEnvoi;
$fraisDouane    = $totalFacture * $tauxDouane;
$coutParPiece   = ($totalAchat + $fraisEnvoi + $fraisDouane) / max(1, $quantite);
$prixMagasin    = $coutParPiece * (1 + $margeMagasin);
$prixFinalBase  = $prixMagasin + $mainOeuvre;
$prixFinal      = arrondirPrix($prixFinalBase);

// ✅ Sauvegarde du prix dans la session (pour preview_pdf.php)
$_SESSION['dernier_prix_final'] = $prixFinal;

// === Enregistrement base (si SAVE) ===
if ($action === 'save' && $conn && !$conn->connect_error) {
    $piecesConcat = is_array($pieces) ? implode(', ', $pieces) : $pieces;
    $stmt = $conn->prepare("INSERT INTO historiques (piece, prix_achat, quantite, main_oeuvre, client_nom, client_tel, prix_final, ref_piece, fournisseur) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sdidssdss", $piecesConcat, $totalArticles, $quantite, $mainOeuvre, $clientNom, $clientTel, $prixFinal, $refPiece, $fournisseur);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
    }
    $conn->close();
}

// === Création PDF (ticket 80mm) ===
$pdf = new TCPDF('P', 'mm', [80, 150], true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 9);

// --- Logo centré en haut ---
$pageWidth = $pdf->getPageWidth();
$logoWidth = 40;
$x = ($pageWidth - $logoWidth) / 2;
$pdf->Image(__DIR__ . '/logo-rem.png', $x, 10, $logoWidth, 40);
$pdf->Ln(10);

// --- En-tête ---
$pdf->writeHTMLCell(
    70, 0, 5, 33, "
    <div style='text-align:left;'>
        <h3 style='margin:0;'>*** {$docType} ***</h3>
        <b>R.E.Mobiles</b><br>
        104Bis Avenue Général De Gaulle<br>
        SIRET : 932 352 149 00011<br>
        WhatsApp : 0694274051
    </div>", 0, 1, false, true, 'L', true
);

// Ligne fine
$pdf->SetLineWidth(0.1);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// --- Logo transparent au centre ---
$pdf->SetAlpha(0.08);
$pdf->Image(__DIR__ . '/logo-rem.png', 20, 80, 40, 40, '', '', '', false, 300, '', false, false, 0);
$pdf->SetAlpha(1);

// --- Partie client ---
$pdf->SetFont('helvetica', '', 8);
$pdf->writeHTML("
<div style='text-align:left;'>
  <b>Client :</b> <i>{$clientNom}</i><br>
  <b>Téléphone :</b> <i>{$clientTel}</i>
</div>", true, false, true, false, 'L');

// === Liste des articles (noms uniquement) ===
$pdf->Ln(2);
$pdf->writeHTML("<b>Articles :</b>", true, false, true, false, 'L');

if (isset($pieces) && is_array($pieces) && count($pieces) > 0) {
    $htmlArticles = "<ul style='margin-left:10px; font-size:8px;'>";
    foreach ($pieces as $nomPiece) {
        $htmlArticles .= "<li>{$nomPiece}</li>";
    }
    $htmlArticles .= "</ul>";
    $pdf->writeHTML($htmlArticles, true, false, true, false, '');
} else {
    $pdf->writeHTML("<i>Aucun article spécifié.</i>", true, false, true, false, 'L');
}

// Ligne séparatrice
$pdf->Ln(1);
$pdf->SetLineWidth(0.1);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// === Section total final simplifiée ===
$pdf->SetFont('helvetica', 'B', 9);
$pdf->writeHTML("
<div style='text-align:center; border:1px solid #000; border-radius:5px; padding:5px; background-color:#f0f0f0;'>
<u> TOTAL TTC</u> : <span style='font-size:11px; font-weight:bold;'>" . number_format($prixFinal, 2) . " €</span>
</div>", true, false, true, false, 'C');

// --- Message final ---
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->writeHTML("
<div style='text-align:center;'>
Merci pour votre confiance ! 💡<br>
R.E.Mobiles
</div>", true, false, true, false, 'C');

// Nettoyage et sortie PDF
while (ob_get_level()) { ob_end_clean(); }

$pdf->Output("ticket_rem.pdf", "I");
exit;
?>
