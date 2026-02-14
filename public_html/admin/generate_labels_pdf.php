<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../vendor/tcpdf/tcpdf.php';

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

$products = $_POST['products'] ?? [];
$labels = [];

/* =========================
   PRÉPARATION DES ÉTIQUETTES
   ========================= */
foreach ($products as $id => $data) {

    if (!isset($data['selected'])) {
        continue;
    }

    $qty = (int)($data['qty'] ?? 0);
    if ($qty <= 0) continue;

    $stmt = $pdo->prepare("
        SELECT designation, prix_vente, ean
        FROM stock_articles
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) continue;

    $rawCode = trim((string)$p['ean']);

    /* =========================
       DÉTERMINATION DU CODE
       ========================= */

    // CAS 1 : EAN-13 NUMÉRIQUE
    if (preg_match('/^\d{13}$/', $rawCode)) {
        $barcodeType  = 'EAN13';
        $barcodeValue = substr($rawCode, 0, 12); // règle TCPDF
        $displayCode  = $rawCode;
    }
    // CAS 2 : CODE INTERNE / RÉFÉRENCE
    else {
        $barcodeType  = 'C128';
        $barcodeValue = $rawCode;
        $displayCode  = $rawCode;
    }

    /* =========================
       AJOUT DES ÉTIQUETTES
       ========================= */
    for ($i = 0; $i < $qty; $i++) {
        $labels[] = [
            'designation'  => $p['designation'],
            'price'        => $p['prix_vente'],
            'barcode_type' => $barcodeType,
            'barcode_value'=> $barcodeValue,
            'display_code' => $displayCode
        ];
    }
}

$totalLabels = count($labels);

/* =========================
   GÉNÉRATION PDF (CALIBRÉ)
   ========================= */
$pdf = new TCPDF('P', 'mm', 'A4');

// 🔥 SUPPRIME la ligne / en-tête en haut
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

/* === PARAMÈTRES PLANche ÉTIQUETTES 118990F === */
$labelW = 38;
$labelH = 21.2;
$cols   = 5;

/* ⚠️ MARGES RÉELLES (À AJUSTER UNE FOIS)  taille et marge de etiquettes */
$marginLeft = 5.5;   // ← clé
$marginTop  = 3;  // ← clé

/* Espaces entre étiquettes (souvent 0) */
$gapX = 0.2;
$gapY = 0;

$i = 0;

$style = [
    'position' => 'S',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'border' => false,
    'padding' => 0,
    'fgcolor' => [0,0,0],
    'bgcolor' => false,
    'text' => false
];

foreach ($labels as $label) {

    $col = $i % $cols;
    $row = floor($i / $cols);

    $x = $marginLeft + ($col * ($labelW + $gapX));
    $y = $marginTop  + ($row * ($labelH + $gapY));

    if ($y + $labelH > 297 - 5) {
        $pdf->AddPage();
        $i = 0;
        continue;
    }

    // 🔲 CADRE ÉTIQUETTE
    ///$pdf->SetLineWidth(0.15);
    //$pdf->Rect($x, $y, $labelW, $labelH);

    // Désignation
    $designation = mb_strtoupper($label['designation']);
    $designation = mb_strimwidth($designation, 0, 28, '…');

    $pdf->SetFont('helvetica', 'B', 5.5);
    $pdf->SetXY($x + 1, $y + 2);
    $pdf->Cell($labelW - 2, 3, $designation, 0, 0, 'C');
    
    // Prix ✅ (AJOUTÉ)
    $price = number_format($label['price'], 2, ',', ' ') . ' €';
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY($x + 1, $y + 16);
    $pdf->Cell($labelW - 2, 3, $price, 0, 0, 'C');
    
    // Code-barres
    $pdf->write1DBarcode(
        $label['barcode_value'],
        $label['barcode_type'],
        $x + 2,
        $y + 5,
        $labelW - 4,
        9,
        0.35,
        $style
    );

// Référence / EAN (largeur identique au code-barres)
// Référence / EAN étalé comme le code-barres
$pdf->SetFont('helvetica', '', 5);
$pdf->SetFontSpacing(1.6); // 👈 ESPACEMENT DES CARACTÈRES

$pdf->SetXY($x + 2, $y + 14);
$pdf->Cell($labelW - 4, 2.2, $label['display_code'], 0, 0, 'C');

// IMPORTANT : remettre l'espacement à zéro après
$pdf->SetFontSpacing(0);



    $i++;
}


$filename = 'etiquettes_' . date('Ymd_His') . '_admin' . $_SESSION['user_id'] . '.pdf';
$filepath = __DIR__ . '/../archives/etiquettes/' . $filename;

// Sauvegarde sur le serveur
$pdf->Output($filepath, 'F');

$stmt = $pdo->prepare("
    INSERT INTO print_labels_history (user_id, filename, total_labels)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $_SESSION['user_id'],
    $filename,
    $totalLabels
]);

// Affichage navigateur
$pdf->Output($filename, 'I');

