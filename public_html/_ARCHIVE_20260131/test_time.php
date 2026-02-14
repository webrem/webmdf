<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

date_default_timezone_set('America/Cayenne');
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->query("SET time_zone = '-03:00'");
echo "<h3>Heure PHP : " . date('Y-m-d H:i:s') . "</h3>";
$res = $conn->query("SELECT NOW() AS db_time");
echo "<h3>Heure MySQL : " . $res->fetch_assoc()['db_time'] . "</h3>";
?>
