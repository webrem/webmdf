<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

$PRINTER_IP = "192.168.1.14:443"; // IP de ton imprimante

$url = "https://{$PRINTER_IP}/epson/epos/service.cgi?devid=local_printer&timeout=5000";

$xml = '<?xml version="1.0" encoding="utf-8"?>'
     . '<epos-print xmlns="http://www.epson-pos.com/schemas/2011/03/epos-print">'
     . '<text lang="fr" align="center">*** TEST IMPRESSION ***&#10;</text>'
     . '<feed line="3"/>'
     . '<cut type="feed"/>'
     . '</epos-print>';

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => ['Content-Type: text/xml; charset=utf-8'],
  CURLOPT_POSTFIELDS     => $xml,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$error    = curl_error($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo "Erreur cURL : " . $error;
} else {
    echo "Code HTTP : $code <br> Réponse imprimante : <br><pre>" . htmlspecialchars($response) . "</pre>";
}
