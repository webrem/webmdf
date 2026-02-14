<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB : " . $conn->connect_error); }

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=totaux_clients.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ["Client","Total Réparations (€)","Total Diagnostics (€)","Total Acomptes (€)","Restant à payer (€)"], ";");

$sql = "SELECT client_name, 
               SUM(price_repair) AS total_repair,
               SUM(price_diagnostic) AS total_diag,
               SUM(acompte) AS total_acompte,
               SUM(price_repair + price_diagnostic - acompte) AS total_restant
        FROM devices
        GROUP BY client_name
        ORDER BY client_name ASC";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['client_name'],
        number_format((float)$row['total_repair'], 2, ".", ""),
        number_format((float)$row['total_diag'], 2, ".", ""),
        number_format((float)$row['total_acompte'], 2, ".", ""),
        number_format((float)$row['total_restant'], 2, ".", "")
    ], ";");
}

fclose($output);
exit;
