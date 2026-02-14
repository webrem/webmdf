<?php
require_once __DIR__.'/../config/db.php';

/* =========================
   PARAMÈTRES
   ========================= */

$retention = '2 MONTH'; // changer en '2 MONTH' si besoin

$baseDir    = __DIR__ . '/../archives/etiquettes/';
$currentDir = $baseDir . 'current/';
$archiveDir = $baseDir . 'archive/';

// Sécurité dossiers
if (!is_dir($archiveDir)) {
    mkdir($archiveDir, 0775, true);
}

/* =========================
   SÉLECTION DES ANCIENS PDF
   ========================= */

$stmt = $pdo->prepare("
    SELECT id, filename
    FROM print_labels_history
    WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention)
");
$stmt->execute();

$oldPrints = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($oldPrints as $p) {

    $src = $currentDir . $p['filename'];
    $dst = $archiveDir . $p['filename'];

    // Déplacement du PDF
    if (file_exists($src)) {
        rename($src, $dst);
    }

    // Suppression DB (cascade sur print_labels_items)
    $del = $pdo->prepare("DELETE FROM print_labels_history WHERE id = ?");
    $del->execute([$p['id']]);
}

echo "Purge terminée : " . count($oldPrints) . " impressions archivées\n";
