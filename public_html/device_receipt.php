<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
require_once __DIR__ . '/device_utils.php';

$ref = $_GET['ref'] ?? '';
if ($ref === '') { die('Référence manquante.'); }

$device = get_device_by_ref($ref);
if (!$device) { die('Appareil introuvable.'); }

require_once __DIR__ . '/tcpdf/tcpdf.php';

// === Ticket thermique 80 mm (≈226pt largeur) ===
$pdf = new TCPDF('P', 'pt', [226, 1000], true, 'UTF-8', false);
$pdf->SetMargins(10, 7, 13);
$pdf->SetAutoPageBreak(true, 13);
$pdf->AddPage();

// --- LOGO ---
$logoCandidates = [
    __DIR__ . '/logoticket.png',
    __DIR__ . '/assets/logoticket.png',
    __DIR__ . '/logo.jpg',
    __DIR__ . '/assets/logo.jpg'
];
foreach ($logoCandidates as $logoPath) {
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 63, 10, 100, 0, '', '', '', false, 300);
        $pdf->Ln(80);
        break;
    }
}

// --- EN-TÊTE ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 0, "R.E.Mobiles", 0, 2, 'C');
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 0, "104B Avenue Général de Gaulle", 0, 2, 'C');
$pdf->Cell(0, 0, "97300 Cayenne", 0, 2, 'C');
$pdf->Cell(0, 0, "Tél/WhatsApp : +594 694 27 40 51", 0, 2, 'C');
$pdf->Ln(5);
$pdf->Cell(0, 0, str_repeat("-", 32), 0, 1, 'C');

// --- Date & heure ---
date_default_timezone_set('America/Cayenne');
$pdf->Ln(3);
$pdf->MultiCell(0, 0, "Date : " . date("d/m/Y") . "  Heure : " . date("H:i"), 0, 'L');

// --- INFOS CLIENT ---
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 10, "Réf : " . $device['ref'], 0, 1, 'L');
$pdf->Cell(0, 10, "Client : " . $device['client_name'], 0, 1, 'L');
$pdf->Cell(0, 10, "Téléphone : " . $device['client_phone'], 0, 1, 'L');
if (!empty($device['client_email']))   
    $pdf->Cell(0, 10, "Email : " . $device['client_email'], 0, 1, 'L');
if (!empty($device['client_address'])) 
    $pdf->Cell(0, 10, "Adresse : " . $device['client_address'], 0, 1, 'L');

// --- INFOS APPAREIL ---
$pdf->Ln(3);
$pdf->Cell(0, 10, "Modèle : " . $device['model'], 0, 1, 'L');
if (!empty($device['problem']))        
    $pdf->Cell(0, 10, "Problème : " . $device['problem'], 0, 1, 'L');
if (!empty($device['technician_name']))
    $pdf->Cell(0, 10, "Technicien : " . $device['technician_name'], 0, 1, 'L');
if (!empty($device['imei_serial']))    
    $pdf->Cell(0, 10, "IMEI / N° série : " . $device['imei_serial'], 0, 1, 'L');
if (!empty($device['device_lock']))    
    $pdf->Cell(0, 10, "Code verrou : " . $device['device_lock'], 0, 1, 'L');
if (!empty($device['status']))         
    $pdf->Cell(0, 10, "Statut : " . $device['status'], 0, 1, 'L');



// --- PIÈCES FONCTIONNELLES ---
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->MultiCell(0, 0, " Pièces Signalée :", 0, 'L');
$pdf->SetFont('helvetica', 'I', 8);

$parts = [
    'speaker' => 'Haut-parleur',
    'lcd' => 'Écran LCD',
    'front_cam' => 'Caméra avant',
    'back_cam' => 'Caméra arrière',
    'microphone' => 'Microphone',
    'fingerprint' => 'Lecteur empreintes',
    'home_button' => 'Bouton accueil',
    'volume_button' => 'Bouton volume',
    'power_button' => 'Bouton marche/arrêt',
    'signal_carrier' => 'Réseau',
    'battery' => 'Batterie',
    'wifi_bt' => 'Wi-Fi / Bluetooth',
    'ear_speaker' => 'Écouteur interne',
    'charging_port' => 'Port de charge'
];

$col = 0;
foreach ($parts as $field => $label) {
    $checked = (!empty($device[$field]) && $device[$field] == 1);

    // case
    $pdf->Cell(5, 8, '', 1, 0, 'C', $checked);
    // texte
    $pdf->Cell(80, 15, " " . $label, 0, 0, 'L');

    $col++;
    if ($col % 2 == 0) {
        $pdf->Ln(); // retour à la ligne après 2 colonnes
    } else {
        $pdf->Cell(10, 6, '', 0, 0); // petit espace entre les colonnes
    }
}
$pdf->Ln(5);

if (!$ok) {
    $pdf->MultiCell(0, 0, "pièce signalée comme Disfonctionnelle.", 0, 'L');
}

// --- PRIX ---
$pdf->Ln(3);
if (isset($device['price_repair']))     
    $pdf->MultiCell(0, 0, "Prix réparation : " . number_format((float)$device['price_repair'], 2, ',', ' ') . "€");
if (isset($device['price_diagnostic'])) 
    $pdf->MultiCell(0, 0, "Prix diagnostic : " . number_format((float)$device['price_diagnostic'], 2, ',', ' ') . "€");

// --- REMARQUES & NOTES ---
if (!empty($device['other_checks'])) {
    $pdf->Ln(3);
    $pdf->MultiCell(0, 0, "Remarques : " . $device['other_checks']);
}
if (!empty($device['notes'])) {
    $pdf->Ln(2);
    $pdf->MultiCell(0, 0, "Notes : " . $device['notes']);
}

$pdf->Ln(5);
$pdf->Cell(0, 0, str_repeat("-", 32), 0, 1, 'C');

// --- Conditions générales ---
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(0, 0, "Conditions Générales – R.E.Mobiles", 0, 'C');
$pdf->Ln(3);

$pdf->SetFont('helvetica', 'B', 6);
$conditions = "
1. Dépôt & Responsabilité
Si l’appareil arrive éteint, toute responsabilité sur son état initial incombe au propriétaire. (Art. 1353 Code civil)

2. Délai de garde
Après 3 mois suivant la première prise de contact après réparation, les appareils non retirés pourront être recyclés sans responsabilité pour R.E.Mobiles.

3. Carte SIM & Mémoire
Retirez votre SIM et carte mémoire avant dépôt. R.E.Mobiles décline toute responsabilité en cas de perte.

4. Exclusions de garantie
Appareils mouillés, mauvaise utilisation, défaut logiciel ou ouverture par un autre technicien → pas de garantie. (Art. L217-12 Code conso)

5. Réparations carte mère
Toute intervention comporte un risque : l’appareil peut devenir inutilisable.

6. Paiement & Délais
Contactez-nous pour convenir d’un délai. (Art. 1343-1 Code civil)

7. Garantie pièces
30 jours de garantie sur les pièces remplacées.
Exclusions : chute, casse, oxydation, mauvaise utilisation.
Pour les écrans : variations possibles de couleur / luminosité.

8. Délais pièces
Retrait retardé si commande spéciale / acheminement hors Europe.
";
$pdf->MultiCell(0, 0, $conditions, 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');

$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');
// --- Signatures ---
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->MultiCell(0, 0, "Signature client : ____________________", 0, 'L');



$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');
// --- Pied ---
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(0, 0, "Merci pour votre confiance", 0, 'C');


$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');
// --- Signatures ---
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->MultiCell(0, 0, "Signature réparateur : ____________________", 0, 'L');

$pdf->MultiCell(0, 0, "             ", 0, 'L');


$pdf->MultiCell(0, 0, "             ", 0, 'L');
// Sauvegarde du PDF temporairement
$filePath = __DIR__ . '/tickets/Ticket_' . $device['ref'] . '.pdf';
if (!is_dir(__DIR__ . '/tickets')) {
    mkdir(__DIR__ . '/tickets', 0777, true);
}
if (ob_get_length()) ob_end_clean();
$pdf->Output($filePath, 'F'); // Sauvegarde sur le serveur

// --- Fin du PDF ---
$filePath = __DIR__ . '/tickets/Ticket_' . $device['ref'] . '.pdf';
if (!is_dir(__DIR__ . '/tickets')) {
    mkdir(__DIR__ . '/tickets', 0777, true);
}
if (ob_get_length()) ob_end_clean();
$pdf->Output($filePath, 'F'); // Sauvegarde sur le serveur

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ticket <?php echo htmlspecialchars($device['ref']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
  <h3>Ticket généré : <?php echo htmlspecialchars($device['ref']); ?></h3>

  <iframe src="tickets/<?php echo 'Ticket_' . $device['ref'] . '.pdf'; ?>" 
          width="100%" height="500" style="border:1px solid #ccc;border-radius:6px"></iframe>

  <div class="mt-3">
    <button id="printLan" class="btn btn-success">🖨️ Imprimer sur Epson LAN</button>
  </div>

  <script>
  document.getElementById('printLan').addEventListener('click', async () => {
    try {
      const res = await fetch('print_epos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ref: '<?php echo addslashes($device['ref']); ?>' })
      });
      const json = await res.json();
      if (json.ok) {
        alert("✅ Impression envoyée à l'imprimante");
      } else {
        alert("❌ Erreur impression : " + (json.msg || json.code));
      }
    } catch (e) {
      alert("Erreur réseau : " + e.message);
    }
  });
  </script>
</body>
</html>
<?php exit; ?>


