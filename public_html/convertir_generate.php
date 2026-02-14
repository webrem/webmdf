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

// === Connexion base de données ===
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);

// === Paramètres GET ===
$id   = (int)($_GET['id'] ?? 0);
$type = strtoupper(trim($_GET['type'] ?? 'FACTURE'));
$types_valides = ['FACTURE', 'FACTURE_ACOMPTE', 'DEVIS', 'PROFORMA'];
if (!in_array($type, $types_valides, true)) $type = 'FACTURE';

// === Récupération du ticket (multi-sources) ===
$res = $conn->query("SELECT * FROM ventes_historique WHERE id=$id");
if (!$res || $res->num_rows === 0) {
    // 🔁 Si non trouvé dans ventes_historique, on cherche dans historiques
    $res = $conn->query("SELECT * FROM historiques WHERE id=$id");
    if (!$res || $res->num_rows === 0) {
        die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Ticket introuvable (#$id)</h3>");
    }
}
$data = $res->fetch_assoc();

// === Données société ===
$entreprise_nom    = "R.E.Mobiles";
$forme_juridique   = "SASU";
$rcs               = "834 693 301 R.C.S. Cayenne";
$adresse           = "104 bis avenue Général de Gaulle, 97300 Cayenne";
$activite          = "Travaux de réparation de téléphones et appareils électroniques";
$telephone         = "+594 694 27 40 51";

// === Configuration PDF ===
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator($entreprise_nom);
$pdf->SetAuthor($entreprise_nom);
$pdf->SetTitle("$type - $entreprise_nom");
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(TRUE, 18);
$pdf->AddPage();

// === FILIGRANE central ===
$bgLogo = __DIR__ . '/logo.png';
if (file_exists($bgLogo)) {
    $pdf->SetAlpha(0.1);
    $pdf->Image($bgLogo, 30, 90, 150, '', 'PNG', '', '', false, 300, '', false, false, 0);
    $pdf->SetAlpha(1);
}

// === En-tête société ===
$logoPath = __DIR__ . '/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 40);
}
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $entreprise_nom, 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, "$forme_juridique — $rcs", 0, 1, 'R');
$pdf->Cell(0, 5, $adresse, 0, 1, 'R');
$pdf->Cell(0, 5, "Tél : $telephone", 0, 1, 'R');
$pdf->Ln(10);

// === Titre du document ===
$titreDoc = match($type) {
    'FACTURE_ACOMPTE' => 'FACTURE D’ACOMPTE',
    'FACTURE' => 'FACTURE',
    'DEVIS' => 'DEVIS',
    'PROFORMA' => 'PROFORMA',
    default => 'FACTURE'
};

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, "DOCUMENT : $titreDoc", 0, 1, 'C');
$pdf->Ln(3);

// === Mentions spéciales selon type ===
if ($type === 'PROFORMA') {
    $pdf->SetFont('helvetica', 'I', 11);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 8, "Document sans valeur légale — Copie interne", 0, 1, 'C');
    $pdf->Ln(3);
} elseif ($type === 'FACTURE_ACOMPTE') {
    $pdf->SetFont('helvetica', 'I', 11);
    $pdf->SetTextColor(0, 128, 255);
    $pdf->Cell(0, 8, "Facture d’acompte relative à une réparation en cours", 0, 1, 'C');
    $pdf->Ln(3);
}
$pdf->SetTextColor(0, 0, 0);

// === Informations client ===
$clientNom = $data['client_nom'] ?? 'Client inconnu';
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(100, 8, "Client : " . htmlspecialchars($clientNom), 0, 0, 'L');
$pdf->Cell(0, 8, "Date : " . date('d/m/Y'), 0, 1, 'R');
$pdf->Cell(100, 8, "Téléphone : " . htmlspecialchars($data['client_tel'] ?? '—'), 0, 1, 'L');
$pdf->Ln(8);

// === Tableau principal ===
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(13, 202, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(40, 8, "Réf.", 1, 0, 'C', true);
$pdf->Cell(80, 8, "Désignation", 1, 0, 'C', true);
$pdf->Cell(30, 8, "Qté", 1, 0, 'C', true);
$pdf->Cell(40, 8, "Prix TTC (€)", 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 10);

$ref = $data['ref_vente'] ?? '—';
$designation = $data['designation'] ?? $data['piece'] ?? 'Réparation / Vente POS';
$quantite = $data['quantite'] ?? 1;

// ✅ Gestion des deux champs prix_total ou prix_final
$prix_total = 0;
if (isset($data['prix_total'])) {
    $prix_total = (float)$data['prix_total'];
} elseif (isset($data['prix_final'])) {
    $prix_total = (float)$data['prix_final'];
}
$prix = number_format($prix_total, 2, ',', ' ');

// ✅ Multicell pour texte long dans désignation
$pdf->MultiCell(40, 8, $ref, 1, 'C', false, 0);
$pdf->MultiCell(80, 8, $designation, 1, 'L', false, 0);
$pdf->MultiCell(30, 8, $quantite, 1, 'C', false, 0);
$pdf->MultiCell(40, 8, $prix, 1, 'R', false, 1);

// === Total ===
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(220, 248, 255);
$pdf->Cell(150, 10, "TOTAL TTC", 1, 0, 'R', true);
$pdf->Cell(40, 10, $prix . " €", 1, 1, 'R', true);
$pdf->Ln(8);

// === Pied de page ===
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$mention = "Merci pour votre confiance.\nPour toute question : $telephone\n\n" .
           "$entreprise_nom — $activite\n$adresse";

if ($type === 'FACTURE_ACOMPTE') {
    $mention = "Cette facture d’acompte confirme un paiement partiel.\nLe solde restant sera facturé à la livraison.\n\n" . $mention;
}
$pdf->MultiCell(0, 8, $mention, 0, 'C');

// === ✍️ Signature (même style que l’autre code) ===
$signaturePath = __DIR__ . '/signature.png';
if (file_exists($signaturePath)) {
    $pdf->StartTransform();
    $pdf->Rotate(30, 110, 220);
    $pdf->SetAlpha(0.50);
    $pdf->Image($signaturePath, 80, 200, 55);
    $pdf->SetAlpha(1);
    $pdf->StopTransform();
    $pdf->SetFont('helvetica', 'I', 9);
}

// === Enregistrement / affichage ===
$dateFolder = date('Y-m');
$folder = __DIR__ . "/devis/$dateFolder";
if (!is_dir($folder)) mkdir($folder, 0775, true);

$fileName = "{$titreDoc}_{$id}_" . date('Ymd_His') . ".pdf";
$filePath = "$folder/$fileName";

ob_end_clean(); // 🔹 Empêche toute sortie avant TCPDF
$pdf->Output($filePath, 'F');
$pdf->Output($fileName, 'I');
exit;
?>
