<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script de nettoyage automatique du projet R-E-Mobiles
 * Déplace les fichiers obsolètes 🟥 et optionnels 🟨 dans /archive/
 * A exécuter depuis la racine du site (ex: /public_html/)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = __DIR__;
$archiveDir = "$baseDir/archive";
$obsoleteDir = "$archiveDir/obsolete";
$optionalDir = "$archiveDir/optionnels";

@mkdir($archiveDir, 0775, true);
@mkdir($obsoleteDir, 0775, true);
@mkdir($optionalDir, 0775, true);

echo "<pre>=== Nettoyage du projet R-E-Mobiles ===\n\n";

// --- 🟥 FICHIERS OBSOLÈTES À DÉPLACER ---
$obsoleteFiles = [
    'generate_pdfold.php',
    'index_remobiles_colored.php',
    'delete_vente.php',
    'scraper_final.php',
    'test_pdf.php',
    'test_printer.php',
    'backup_devices.sql',
    'migration.sql',
    'style.php',
    'style.css.old',
    'historique.php',
    'scraper.php',
    'old_generate_pdf.php'
];

// --- 🟨 FICHIERS OPTIONNELS À DÉPLACER ---
$optionalFiles = [
    'export_auth.php',
    'fiche_client.php',
    'export_users.php',
    'historique.php',
    'README.md',
    'composer.json',
    'composer.lock',
    'error_log'
];

// --- Fonction de déplacement sécurisée ---
function moveFileSafe($file, $destDir) {
    global $baseDir;
    $source = "$baseDir/$file";
    $dest = "$destDir/" . basename($file);

    if (file_exists($source)) {
        if (@rename($source, $dest)) {
            echo "[OK]  $file déplacé vers $destDir\n";
        } else {
            echo "[ERREUR] Impossible de déplacer $file\n";
        }
    }
}

// --- Déplacement des fichiers 🟥 ---
echo "🟥 Déplacement des fichiers obsolètes...\n";
foreach ($obsoleteFiles as $file) moveFileSafe($file, $GLOBALS['obsoleteDir']);
echo "\n";

// --- Déplacement des fichiers 🟨 ---
echo "🟨 Déplacement des fichiers optionnels...\n";
foreach ($optionalFiles as $file) moveFileSafe($file, $GLOBALS['optionalDir']);
echo "\n";

// --- Résumé final ---
echo "✅ Nettoyage terminé avec succès.\n";
echo "Les fichiers essentiels 🟩 sont restés à leur place.\n";
echo "Les fichiers déplacés sont sauvegardés dans /archive/.\n";
echo "\n=== FIN ===</pre>";
?>
