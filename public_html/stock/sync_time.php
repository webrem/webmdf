<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Synchronisation universelle R.E.Mobiles
 * Fuseau horaire : America/Cayenne (-03:00)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🕒 Fuseau PHP
date_default_timezone_set('America/Cayenne');

// 🧩 Synchronisation MySQL
if (isset($conn) && $conn instanceof mysqli) {
    @$conn->query("SET time_zone = '-03:00'");
}
?> 