<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

$servername = "localhost";
$username   = "u498346438_calculrem";
$password   = "Calculrem1";
$dbname     = "u498346438_calculrem";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur connexion : " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// ✅ Récupérer un appareil
function get_device_by_ref($ref) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM devices WHERE ref=? LIMIT 1");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $result = $stmt->get_result();
    $device = $result->fetch_assoc();
    $stmt->close();
    return $device;
}

// ✅ Récupérer les photos d’un appareil
function get_photos_by_ref($ref) {
    global $conn;
    $stmt = $conn->prepare("SELECT photo_path FROM device_photos WHERE device_ref=?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $result = $stmt->get_result();
    $photos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $photos;
}

// ✅ Récupérer les notes avec éventuelles photos
function get_device_notes($ref) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM device_notes WHERE device_ref=? ORDER BY created_at DESC");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $result = $stmt->get_result();
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        // Récupérer photos liées à cette note
        $stmt2 = $conn->prepare("SELECT photo_path FROM device_note_photos WHERE note_id=?");
        $stmt2->bind_param("i", $row['id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $row['photos'] = $res2->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
        $notes[] = $row;
    }
    $stmt->close();
    return $notes;
}
