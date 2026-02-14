<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// print_epos.php : relai serveur -> imprimante TM-m30 en ePOS-Print
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Non autorisé']);
    exit;
}

require_once __DIR__ . '/device_utils.php';

// ⚠️ Mets l’IP correcte de ton imprimante
$PRINTER_IP = '192.168.1.14:443';  
$TIMEOUT_MS = 5000; // 5 secondes

// Récupère la réf envoyée depuis le bouton
$input = json_decode(file_get_contents('php://input'), true);
$ref = $input['ref'] ?? '';
if ($ref === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Référence manquante']);
    exit;
}

$device = get_device_by_ref($ref);
if (!$device) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Appareil introuvable']);
    exit;
}

// Helpers
$esc = function($s){ return htmlspecialchars((string)$s, ENT_XML1 | ENT_COMPAT, 'UTF-8'); };
$priceRepair = isset($device['price_repair']) ? number_format((float)$device['price_repair'], 2, ',', ' ') : '0,00';
$priceDiag   = isset($device['price_diagnostic']) ? number_format((float)$device['price_diagnostic'], 2, ',', ' ') : null;

// Construction du document ePOS-Print
$xml = '<?xml version="1.0" encoding="utf-8"?>'
     . '<epos-print xmlns="http://www.epson-pos.com/schemas/2011/03/epos-print">'
     . '<text align="center" smooth="true">'
     . "R.E.Mobiles\n104B Avenue Général de Gaulle\n97300 Cayenne\nTél : +594 694 27 40 51\n"
     . "</text>"
     . '<text>--------------------------------\n</text>'
     . '<text>Réf : '.$esc($device['ref'])."\n</text>"
     . '<text>Client : '.$esc($device['client_name'])."\n</text>"
     . '<text>Téléphone : '.$esc($device['client_phone'])."\n</text>"
     . (!empty($device['client_email'])   ? '<text>Email : '.$esc($device['client_email'])."\n</text>" : '')
     . (!empty($device['model'])          ? '<text>Modèle : '.$esc($device['model'])."\n</text>" : '')
     . (!empty($device['problem'])        ? '<text>Problème : '.$esc($device['problem'])."\n</text>" : '')
     . (!empty($device['status'])         ? '<text>Statut : '.$esc($device['status'])."\n</text>" : '')
     . '<text>--------------------------------\n</text>'
     . '<text>Prix réparation : '.$priceRepair." €\n</text>"
     . ($priceDiag !== null ? '<text>Prix diagnostic : '.$priceDiag." €\n</text>" : '')
     . '<feed line="2"/>'
     . '<text align="center">Merci pour votre confiance\n</text>'
     . '<feed/>'
     . '<cut type="feed"/>'
     . '</epos-print>';

// ✅ URL HTTPS + bon chemin
$url = "https://{$PRINTER_IP}/epson/epos/service.cgi?devid=local_printer&timeout={$TIMEOUT_MS}";

// Envoi de la requête
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => ['Content-Type: text/xml; charset=utf-8'],
  CURLOPT_POSTFIELDS     => $xml,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_SSL_VERIFYPEER => false, // certificat auto-signé → on désactive la vérification
  CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$error    = curl_error($ch);
$code     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode(['ok' => false, 'msg' => "Erreur cURL: $error", 'code' => $code]);
    exit;
}

// Vérifie la réponse
$ok = (strpos($response, 'success="true"') !== false);
echo json_encode(['ok' => $ok, 'code' => $code, 'raw' => $response]);
