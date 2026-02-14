<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../sync_time.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Accès refusé.");
}

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset("utf8mb4");

$mois = $_GET['mois'] ?? date('Y-m');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="caisse_'.$mois.'.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Date',
    'Total espèces',
    'Total CB',
    'Total global',
    'Validé le',
    'Validé par'
], ';');

$stmt = $conn->prepare("
    SELECT
        c.date_caisse,
        c.total_especes,
        c.total_cb,
        c.validated_at,
        u.username
    FROM caisse_jour c
    LEFT JOIN users u ON u.id = c.validated_by
    WHERE c.validated_at IS NOT NULL
    AND DATE_FORMAT(c.date_caisse, '%Y-%m') = ?
    ORDER BY c.date_caisse ASC
");
$stmt->bind_param("s", $mois);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    fputcsv($output, [
        $r['date_caisse'],
        number_format($r['total_especes'],2,'.',''),
        number_format($r['total_cb'],2,'.',''),
        number_format($r['total_especes']+$r['total_cb'],2,'.',''),
        $r['validated_at'],
        $r['username']
    ], ';');
}

fclose($output);
exit;
