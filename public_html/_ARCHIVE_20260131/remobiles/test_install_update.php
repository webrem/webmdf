<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Test pour vérifier que install.php a été mis à jour correctement
 */

// Vérifiez que le fichier install.php contient la bonne configuration
$file_path = __DIR__ . "/install.php";

echo "=== Test de install.php ===\n\n";

if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    
    echo "Contenu de install.php:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Rechercher la configuration
    if (strpos($content, "u498346438_remshop1") !== false) {
        echo "✅ Configuration u498346438_remshop1 trouvée\n";
    } else {
        echo "❌ Configuration u498346438_remshop1 non trouvée\n";
    }
    
    if (strpos($content, "Remshop104") !== false) {
        echo "✅ Mot de passe Remshop104 trouvé\n";
    } else {
        echo "❌ Mot de passe Remshop104 non trouvé\n";
    }
    
    if (strpos($content, "u498346438_calculrem") !== false) {
        echo "❌ Ancienne configuration u498346438_calculrem toujours présente\n";
    } else {
        echo "✅ Ancienne configuration u498346438_calculrem supprimée\n";
    }
    
} else {
    echo "❌ Fichier install.php non trouvé\n";
}
?>