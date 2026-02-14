<?php
/**
 * ==========================================
 * RUNTIME PAGE TRACKER — R.E.Mobiles
 * ==========================================
 * Log chaque page PHP réellement exécutée
 * Sans impacter le site
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = $_SERVER['DOCUMENT_ROOT'] . '/runtime_pages.log';

// Sécurité minimale
if (!is_writable(dirname($logFile))) {
    return;
}

$data = [
    'time' => date('Y-m-d H:i:s'),
    'script' => basename($_SERVER['SCRIPT_FILENAME'] ?? 'unknown'),
    'uri'    => $_SERVER['REQUEST_URI'] ?? 'CLI',
    'role'   => $_SESSION['role'] ?? 'guest',
    'user'   => $_SESSION['username'] ?? 'guest'
];

file_put_contents(
    $logFile,
    json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND
);
