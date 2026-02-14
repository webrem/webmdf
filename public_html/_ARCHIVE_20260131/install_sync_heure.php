<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * INSTALL SYNC TIME v2 — R.E.Mobiles
 * ✅ Ajoute require_once 'sync_time.php' si absent
 * ✅ Corrige la connexion MySQL pour inclure le fuseau -03:00
 * ✅ Crée ou met à jour sync_time.php à la racine
 */

echo "<pre style='font-family: monospace; color:#00ffaa;'>";

$root = __DIR__;
$syncFile = "$root/sync_time.php";

/* === 1️⃣ Création / Mise à jour de sync_time.php === */
$syncContent = <<<PHP
<?php
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
if (isset(\$conn) && \$conn instanceof mysqli) {
    @\$conn->query("SET time_zone = '-03:00'");
}
?>
PHP;

file_put_contents($syncFile, $syncContent);
echo "✅ Fichier sync_time.php mis à jour.\n";

/* === 2️⃣ Parcours de tous les fichiers PHP === */
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$count = 0;

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $filename = basename($path);

    if (substr($path, -4) !== '.php') continue;
    if (in_array($filename, ['install_sync_time.php', 'install_sync_time_v2.php', 'sync_time.php'])) continue;

    $content = file_get_contents($path);
    $modified = false;

    // === 🔹 Étape 1 : Ajouter la synchro après session_start()
    if (preg_match('/session_start\s*\(\s*\)\s*;/', $content) && strpos($content, "sync_time.php") === false) {
        $content = preg_replace(
            '/(session_start\s*\(\s*\)\s*;)/',
            "$1\nrequire_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique",
            $content,
            1
        );
        echo "➕ Sync ajouté dans : $filename\n";
        $modified = true;
    }

    // === 🔹 Étape 2 : Injecter le time_zone juste après la connexion MySQL
    if (preg_match('/new\s+mysqli\s*\([^)]*\)\s*;/', $content) && strpos($content, "SET time_zone") === false) {
        $content = preg_replace(
            '/(\$conn\s*=\s*new\s+mysqli\s*\([^)]*\)\s*;)/',
            "$1\n\$conn->set_charset('utf8mb4');\n\$conn->query(\"SET time_zone = '-03:00'\"); // ⏰ Correction fuseau horaire",
            $content,
            1
        );
        echo "🕒 Fuseau MySQL ajouté dans : $filename\n";
        $modified = true;
    }

    // Sauvegarde du fichier modifié
    if ($modified) {
        file_put_contents($path, $content);
        $count++;
    }
}

echo "\n🎯 Installation terminée avec succès !\n";
echo "→ Total fichiers modifiés : $count\n";
echo "→ Fuseau horaire forcé : America/Cayenne (-03:00)\n";
echo "</pre>";
?>
