<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur DB: ".$conn->connect_error); }

$res = $conn->query("SHOW COLUMNS FROM devices");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "<br>";
}
?>
