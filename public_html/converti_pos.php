<?php
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/sync_time.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

/* ============================
   CONNEXION DB
   ============================ */
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'");
if ($conn->connect_error) {
    die("Erreur DB : " . $conn->connect_error);
}

/* ============================
   PARAMÈTRE
   ============================ */
$ref = trim($_GET['ref'] ?? '');
if ($ref === '') {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Référence manquante.</h3>");
}

/* ============================
   🧠 DÉTECTION TYPE DE RÉFÉRENCE
   ============================ */
$isPOS = false;
$isDEV = false;
$isAcompte = false;
$isRET = false;
$isCMD = false;

if (strpos($ref, 'POS-') === 0) {
    $isPOS = true;
}
elseif (strpos($ref, 'DEV-AC-') === 0) {
    $isDEV = true;
    $isAcompte = true;
}
// CMD acompte (ancien + nouveau format)
elseif (
    strpos($ref, 'CMD-ACOMPTE-') === 0 ||
    strpos($ref, 'CMD-AC-') === 0
) {
    $isCMD = true;       // ✅ commande
    $isAcompte = true;  // ✅ acompte
}


elseif (strpos($ref, 'DEV-') === 0) {
    $isDEV = true;
}
elseif (strpos($ref, 'RET-') === 0) {
    $isRET = true;
}


/* ============================
   RÉCUPÉRATION RETRAIT ESPÈCES
   ============================ */
if ($isRET) {

    $stmtRET = $conn->prepare("
        SELECT ref_vente, designation, prix_total, date_vente, mode_paiement
        FROM ventes_historique
        WHERE ref_vente = ?
          AND type = 'retrait'
        LIMIT 1
    ");
    $stmtRET->bind_param("s", $ref);
    $stmtRET->execute();
    $ret = $stmtRET->get_result()->fetch_assoc();
    $stmtRET->close();

    if (!$ret) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Retrait introuvable.</h3>");
    }

    $lignes[] = [
        'ref_vente'     => $ret['ref_vente'],
        'designation'   => $ret['designation'],
        'quantite'      => 1,
        'prix_total'    => (float)$ret['prix_total'], // déjà négatif
        'mode_paiement' => $ret['mode_paiement'] ?? 'Espèces',
        'date_vente'    => $ret['date_vente']
    ];

    $totalFacture = (float)$ret['prix_total'];
    $totalAcompte = 0;
    $resteAPayer  = 0;

    $device = [
        'client_name'  => 'Caisse',
        'client_phone' => '',
        'model'        => 'Retrait espèces'
    ];
}


/* ============================
   EXTRACTION deviceRef
   ============================ */
if ($isAcompte) {

    // DEV acompte
   if (preg_match('/^DEV-AC-([A-Z0-9]+)(?:[-_].*)?$/i', $ref, $m)) {
    $deviceRef = $m[1];
}
    // CMD acompte
    // CMD-AC-151-43 ou CMD-ACOMPTE-151
    elseif (preg_match('/CMD-AC-([0-9]+)/', $ref, $m)) {
        $deviceRef = $m[1]; // 151
    }
    elseif (preg_match('/CMD-ACOMPTE-([0-9]+)/', $ref, $m)) {
        $deviceRef = $m[1]; // 151
    }

    else {
        $deviceRef = $ref;
    }

} else {
    $deviceRef = $ref;
}
if (empty($deviceRef)) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Référence appareil invalide.</h3>");
}

/* ============================
   RÉCUPÉRATION DES ACOMPTES DEV
   ============================ */
$lignes = [];

if ($isDEV && !$isCMD) {

    if (strpos($ref, 'CMD-ACOMPTE-') === 0) {
        $sql = "
            SELECT * FROM ventes_historique
            WHERE ref_vente LIKE CONCAT('CMD-ACOMPTE-', ?)
            ORDER BY date_vente ASC
        ";
    } else {
        $sql = "
            SELECT * FROM ventes_historique
            WHERE ref_vente LIKE CONCAT('DEV-AC-', ?, '-%')
            ORDER BY date_vente ASC
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $deviceRef);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $lignes[] = $row;
    }
}
// ============================
// RÉCUPÉRATION DESCRIPTION COMMANDE (ACOMPTE CMD)
// ============================
$commandePiece = null;

if ($isCMD && !empty($deviceRef)) {

    // $deviceRef = ID de la commande (ex: 151)
    $stmtCmd = $conn->prepare("
        SELECT piece
        FROM historiques
        WHERE id = ? AND statut = 'commande'
        LIMIT 1
    ");
    $stmtCmd->bind_param("i", $deviceRef);
    $stmtCmd->execute();
    $stmtCmd->bind_result($commandePiece);
    $stmtCmd->fetch();
    $stmtCmd->close();
}

if ($isCMD && !empty($deviceRef)) {

    $stmt = $conn->prepare("
        SELECT piece, client_nom, client_tel
        FROM historiques
        WHERE id = ? AND statut = 'commande'
        LIMIT 1
    ");
    $stmt->bind_param("i", $deviceRef);
    $stmt->execute();
    $stmt->bind_result($commandePiece, $clientNom, $clientTel);
    $stmt->fetch();
    $stmt->close();

    $device = [
        'client_name'  => $clientNom ?: 'Client',
        'client_phone' => $clientTel ?: '',
        'model'        => $commandePiece ?: 'Commande'
    ];
}

/* ============================
   RÉCUPÉRATION DEVICE (DEV)
   ============================ */
if ($isDEV && !$isCMD) {
    $stmtDev = $conn->prepare("
        SELECT price_repair, price_diagnostic, client_name, client_phone, model
        FROM devices WHERE ref=? LIMIT 1
    ");
    $stmtDev->bind_param("s", $deviceRef);
    $stmtDev->execute();
    $device = $stmtDev->get_result()->fetch_assoc();
    $stmtDev->close();

    if (!$device && !$isCMD) {
        die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Appareil introuvable.</h3>");
    }

    $totalFacture = (float)$device['price_repair'] + (float)$device['price_diagnostic'];
    $totalAcompte = array_sum(array_column($lignes, 'prix_total'));
    $resteAPayer  = max(0, $totalFacture - $totalAcompte);
}

/* ============================
   RÉCUPÉRATION POS
   ============================ */
if ($isPOS) {

    $stmtPOS = $conn->prepare("
        SELECT ref_vente, designation, quantite, prix_total, mode_paiement,
               client_nom, client_tel, date_vente
        FROM ventes
        WHERE ref_vente = ?
    ");
    $stmtPOS->bind_param("s", $ref);
    $stmtPOS->execute();
    $resPOS = $stmtPOS->get_result();

    while ($row = $resPOS->fetch_assoc()) {
        $lignes[] = $row;
    }

    if (empty($lignes)) {
        die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Vente POS introuvable.</h3>");
    }

    $totalFacture = array_sum(array_column($lignes, 'prix_total'));
    $totalAcompte = 0;
    $resteAPayer  = 0;

    $device = [
        'client_name'  => $lignes[0]['client_nom'] ?? 'Client comptoir',
        'client_phone' => $lignes[0]['client_tel'] ?? '',
        'model'        => 'Vente comptoir'
    ];
}

/* ============================
   DEV FINAL SANS ACOMPTE
   ============================ */
if ($isDEV && !$isAcompte && empty($lignes)) {
    $lignes[] = [
        'ref_vente'     => $ref,
        'prix_total'    => $totalFacture,
        'mode_paiement' => 'cb',
        'date_vente'    => date('Y-m-d H:i:s')
    ];
    $totalAcompte = 0;
    $resteAPayer  = 0;
}

/* ============================
   ENTÊTE
   ============================ */
$entete = [
    'client_nom'    => $device['client_name'] ?? 'Client inconnu',
    'mode_paiement' => $lignes[0]['mode_paiement'] ?? ''
];

/* ============================
   PDF SETUP
   ============================ */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

/* ============================
   FILIGRANE
   ============================ */
$bgLogo = __DIR__ . '/logo.png';
if (file_exists($bgLogo)) {
    $pdf->SetAlpha(0.08);
    $pdf->Image($bgLogo, 30, 90, 150);
    $pdf->SetAlpha(1);
}

/* ============================
   SOCIÉTÉ
   ============================ */
$entreprise_nom = "R.E.Mobiles";
$adresse = "104B Avenue Général de Gaulle, 97300 Cayenne";
$telephone = "+594 694 27 40 51";
$siret = "932 352 149 00011";

/* ============================
   ENTÊTE PDF
   ============================ */
$logoPath = __DIR__ . '/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 40);
}

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, "R.E.Mobiles", 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $adresse, 0, 1, 'R');
$pdf->Cell(0, 5, "Tél : $telephone", 0, 1, 'R');
$pdf->Cell(0, 5, "SIRET : $siret", 0, 1, 'R');
$pdf->Ln(8);


/* ============================
   TITRE
   ============================ */
$pdf->SetFont('helvetica', 'B', 14);

$titrePDF = $isRET ? "RETRAIT DE CAISSE" : "FACTURE";

$pdf->Cell(0, 10, $titrePDF, 0, 1, 'C');

/* ============================
   CLIENT
   ============================ */
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(100, 8, "Client : {$device['client_name']}", 0, 0);
$pdf->Cell(0, 8, "Date : " . date('d/m/Y'), 0, 1, 'R');
$pdf->Cell(100, 8, "Téléphone : {$device['client_phone']}", 0, 1);
$pdf->Ln(6);

/* ============================
   TABLE
   ============================ */
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(13, 202, 240);
$pdf->Cell(35, 8, "Réf.", 1, 0, 'C', true);
$pdf->Cell(60, 8, "Désignation", 1, 0, 'C', true);
$pdf->Cell(35, 8, "Mode de paiement", 1, 0, 'C', true);
$pdf->Cell(20, 8, "Qté", 1, 0, 'C', true);
$pdf->Cell(40, 8, "Prix TTC (€)", 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 10);
$w = [35, 60, 35, 20, 40];

foreach ($lignes as $row) {

    // ✅ Contenu
    $refVente     = $row['ref_vente'];
 // ============================
// DÉSIGNATION AFFICHÉE SUR LE PDF
// ============================

$designation = '';

// 🛒 Vente POS
if ($isPOS) {
    $designation = $row['designation'] ?? 'Article';
}

// 💰 Acompte commande
elseif ($isAcompte && !empty($commandePiece)) {
    $designation = $commandePiece;
}

// ➖ Retrait espèces
elseif ($isRET) {
    $designation = $row['designation'] ?? 'Retrait espèces';
}

// 🔧 Réparation
elseif (!empty($device['model'])) {
    $designation = $device['model'];
}

// Sécurité
else {
    $designation = 'Acompte';
}

  
        
        
    $modePaiement = ucfirst(strtolower($row['mode_paiement'] ?? '—'));
    $quantite     = (string)($row['quantite'] ?? 1);
    $prixLigne    = number_format((float)$row['prix_total'], 2, ',', ' ');

    // ✅ CALCUL DE LA HAUTEUR MAX DE LA LIGNE
    $h = max(
        $pdf->getStringHeight($w[0], $refVente),
        $pdf->getStringHeight($w[1], $designation),
        $pdf->getStringHeight($w[2], $modePaiement),
        $pdf->getStringHeight($w[3], $quantite),
        $pdf->getStringHeight($w[4], $prixLigne)
    );

    // ✅ DESSIN DES CELLULES (MÊME HAUTEUR POUR TOUTES)
    $pdf->MultiCell($w[0], $h, $refVente, 1, 'C', false, 0);
    $pdf->MultiCell($w[1], $h, $designation, 1, 'L', false, 0);
    $pdf->MultiCell($w[2], $h, $modePaiement, 1, 'C', false, 0);
    $pdf->MultiCell($w[3], $h, $quantite, 1, 'C', false, 0);
    $pdf->MultiCell($w[4], $h, $prixLigne, 1, 'R', false, 1);
}


/* ============================
   TOTAUX
   ============================ */
$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(150, 8, "TOTAL FACTURE TTC", 1, 0, 'R');
$pdf->Cell(40, 8, number_format($totalFacture, 2, ',', ' ') . " €", 1, 1, 'R');

if ($totalAcompte > 0) {
    $pdf->Cell(150, 8, "ACOMPTE(S) DÉJÀ PAYÉ(S)", 1, 0, 'R');
    $pdf->Cell(40, 8, "- " . number_format($totalAcompte, 2, ',', ' ') . " €", 1, 1, 'R');
}

$pdf->Cell(150, 10, "RESTE À PAYER", 1, 0, 'R');
$pdf->Cell(40, 10, number_format($resteAPayer, 2, ',', ' ') . " €", 1, 1, 'R');




/* ============================
   TAMPONS (FACTURE / RETRAIT)
   ============================ */

$modePaiement = strtolower(trim($entete['mode_paiement'] ?? ''));

/**
 * FACTURE PAYÉE
 * → DEV / POS
 * → reste à payer = 0
 */
if (
    !$isRET &&
    $resteAPayer <= 0 &&
    (
        $modePaiement === '' ||
        in_array($modePaiement, ['espèces','especes','cb','carte','carte bleue', 'Carte bancaire', 'Carte Bancaire', 'CB','Carte bleue','Carte Bleue'])
    )
) {

    $pdf->SetAlpha(0.35);
    $pdf->StartTransform();
    $pdf->Rotate(30, 105, 160);

    $pdf->SetFont('helvetica', 'B', 36);
    $pdf->SetTextColor(0, 150, 0);

    $pdf->writeHTMLCell(
        0,        // largeur auto
        0,        // hauteur auto
        0,        // x auto
        130,      // y stable (zone visible)
        '<div style="text-align:center;">FACTURE PAYÉE</div>',
        0,
        1,
        false,
        true,
        'C'
    );

    $pdf->StopTransform();
    $pdf->SetAlpha(1);
    $pdf->SetTextColor(0, 0, 0);
}

/**
 * SORTIE DE CAISSE
 * → uniquement RET
 */
if ($isRET) {

    $pdf->SetAlpha(0.30);
    $pdf->StartTransform();
    $pdf->Rotate(30, 105, 160);

    $pdf->SetFont('helvetica', 'B', 44);
    $pdf->SetTextColor(200, 0, 0);

    $pdf->writeHTMLCell(
        0,
        0,
        60,
        85,
        '<div style="text-align:center;">SORTIE DE CAISSE</div>',
        0,
        1,
        false,
        true,
        'C'
    );

    $pdf->StopTransform();
    $pdf->SetAlpha(1);
    $pdf->SetTextColor(0, 0, 0);
}










/* ============================
   PIED DE PAGE
   ============================ */
$pdf->Ln(8);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 8,
    "Merci pour votre confiance.\n$entreprise_nom\n$adresse\n$telephone\n$siret",
    0, 'C'
);

/* ============================
   SIGNATURE (INCHANGÉE)
   ============================ */
$signaturePath = __DIR__ . '/signature.png';
if (file_exists($signaturePath)) {
    $pdf->StartTransform();
    $pdf->Rotate(30, 110, 220);
    $pdf->SetAlpha(0.5);
    $pdf->Image($signaturePath, 80, 200, 55);
    $pdf->SetAlpha(1);
    $pdf->StopTransform();
}

/* ============================
   SORTIE
   ============================ */
   if (ob_get_length()) {
    ob_end_clean();
}
$nomFichier = $isRET
    ? "RETRAIT_CAISSE_$ref.pdf"
    : "FACTURE_$deviceRef.pdf";




$pdf->Output($nomFichier, 'I');


exit;
