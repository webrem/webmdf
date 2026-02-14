<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u498346438_calculrem;charset=utf8mb4",
        "u498346438_calculrem",
        "Calculrem1",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Fuseau horaire (équivalent à ton mysqli)
    $pdo->exec("SET time_zone = '-03:00'");

} catch (PDOException $e) {
    die("❌ Erreur connexion base de données : " . $e->getMessage());
}

// Session sécurisée (évite double session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}