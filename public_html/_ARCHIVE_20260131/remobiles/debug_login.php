<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Debug Login Script for R.E.Mobiles
 * Helps diagnose authentication issues
 */ 

require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>';
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Debug Login - R.E.Mobiles</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
echo '</head>';
echo '<body class="bg-gray-100 py-8">';
echo '<div class="max-w-4xl mx-auto px-4">';
echo '<div class="bg-white rounded-lg shadow-lg p-6">';
echo '<h1 class="text-3xl font-bold text-center mb-8 text-gray-800">';
echo '<i class="fas fa-bug mr-2"></i>Debug Login - R.E.Mobiles';
echo '</h1>';

// Test database connection
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">1. Test de Connexion Base de Données</h2>';
try {
    $database = Database::getInstance();
    $result = $database->fetch("SELECT 1 as test");
    echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-green-800">✅ Connexion réussie</h3>';
    echo '<p class="text-green-700">Base de données accessible</p>';
    echo '</div>';
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Erreur de connexion</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Check admin_users table structure
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">2. Structure de la Table admin_users</h2>';
try {
    $sql = "DESCRIBE admin_users";
    $columns = $database->fetchAll($sql);
    
    echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-blue-800">Structure de la table:</h3>';
    echo '<div class="overflow-x-auto mt-2">';
    echo '<table class="min-w-full bg-white border border-gray-200 rounded-lg">';
    echo '<thead class="bg-gray-100">';
    echo '<tr>';
    echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Champ</th>';
    echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Type</th>';
    echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Null</th>';
    echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Clé</th>';
    echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Défaut</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($columns as $column) {
        echo '<tr class="border-t border-gray-100">';
        echo '<td class="px-4 py-2 text-sm text-gray-800">' . htmlspecialchars($column['Field']) . '</td>';
        echo '<td class="px-4 py-2 text-sm text-gray-600">' . htmlspecialchars($column['Type']) . '</td>';
        echo '<td class="px-4 py-2 text-sm text-gray-600">' . htmlspecialchars($column['Null']) . '</td>';
        echo '<td class="px-4 py-2 text-sm text-gray-600">' . htmlspecialchars($column['Key']) . '</td>';
        echo '<td class="px-4 py-2 text-sm text-gray-600">' . htmlspecialchars($column['Default'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Impossible d\'analyser la table</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Check for users in admin_users table
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">3. Utilisateurs dans admin_users</h2>';
try {
    $sql = "SELECT id, username, status, created_at FROM admin_users ORDER BY username";
    $users = $database->fetchAll($sql);
    
    if (!empty($users)) {
        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-green-800">✅ Utilisateurs trouvés: ' . count($users) . '</h3>';
        echo '<div class="overflow-x-auto mt-2">';
        echo '<table class="min-w-full bg-white border border-gray-200 rounded-lg">';
        echo '<thead class="bg-gray-100">';
        echo '<tr>';
        echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">ID</th>';
        echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Username</th>';
        echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Statut</th>';
        echo '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Créé le</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($users as $user) {
            echo '<tr class="border-t border-gray-100">';
            echo '<td class="px-4 py-2 text-sm text-gray-800">' . $user['id'] . '</td>';
            echo '<td class="px-4 py-2 text-sm text-gray-800 font-semibold">' . htmlspecialchars($user['username']) . '</td>';
            echo '<td class="px-4 py-2 text-sm">';
            if ($user['status'] === 'active') {
                echo '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Actif</span>';
            } else {
                echo '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Inactif</span>';
            }
            echo '</td>';
            echo '<td class="px-4 py-2 text-sm text-gray-600">' . date('d/m/Y H:i', strtotime($user['created_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-yellow-800">⚠️ Aucun utilisateur trouvé</h3>';
        echo '<p class="text-yellow-700">La table admin_users est vide</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Erreur lors de la récupération des utilisateurs</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Check specific user "carlos"
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">4. Vérification du compte "carlos"</h2>';
try {
    $sql = "SELECT id, username, status, created_at FROM admin_users WHERE username = :username LIMIT 1";
    $carlos = $database->fetch($sql, [':username' => 'carlos']);
    
    if ($carlos) {
        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-green-800">✅ Compte "carlos" trouvé</h3>';
        echo '<div class="mt-2 space-y-1">';
        echo '<p><strong>ID:</strong> ' . $carlos['id'] . '</p>';
        echo '<p><strong>Username:</strong> ' . htmlspecialchars($carlos['username']) . '</p>';
        echo '<p><strong>Statut:</strong> ' . $carlos['status'] . '</p>';
        echo '<p><strong>Créé le:</strong> ' . date('d/m/Y H:i', strtotime($carlos['created_at'])) . '</p>';
        echo '</div>';
        
        if ($carlos['status'] !== 'active') {
            echo '<div class="mt-3 p-3 bg-yellow-100 border border-yellow-200 rounded">';
            echo '<p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>';
            echo 'Le compte n\'est pas actif. Statut actuel: ' . $carlos['status'] . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-red-800">❌ Compte "carlos" non trouvé</h3>';
        echo '<p class="text-red-700">Aucun utilisateur avec le nom "carlos" dans la base de données</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Erreur lors de la recherche de "carlos"</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Check password field
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">5. Vérification du champ mot de passe</h2>';
try {
    $sql = "SHOW COLUMNS FROM admin_users LIKE 'password'";
    $password_field = $database->fetch($sql);
    
    if ($password_field) {
        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-green-800">✅ Champ mot de passe trouvé</h3>';
        echo '<p class="text-green-700">Type: ' . $password_field['Type'] . '</p>';
        echo '</div>';
    } else {
        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-red-800">❌ Champ mot de passe manquant</h3>';
        echo '<p class="text-red-700">La table admin_users ne contient pas de champ password</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Erreur lors de la vérification du champ password</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Check if users table exists as fallback
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">6. Vérification de la table users (fallback)</h2>';
try {
    $sql = "SHOW TABLES LIKE 'users'";
    $users_table = $database->fetch($sql);
    
    if ($users_table) {
        echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-blue-800">ℹ️ Table users trouvée</h3>';
        echo '<p class="text-blue-700">La table users existe et peut être utilisée comme fallback</p>';
        
        // Check users table structure
        $sql = "DESCRIBE users";
        $users_columns = $database->fetchAll($sql);
        echo '<div class="mt-2">';
        echo '<p class="text-sm text-blue-600"><strong>Champs:</strong> ';
        $field_names = array_column($users_columns, 'Field');
        echo implode(', ', $field_names);
        echo '</p>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-gray-800">ℹ️ Table users non trouvée</h3>';
        echo '<p class="text-gray-700">Aucune table de secours disponible</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Erreur lors de la vérification de la table users</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Test password verification
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">7. Test de vérification de mot de passe</h2>';
if ($carlos) {
    try {
        // Try to get the password hash for carlos
        $sql = "SELECT password FROM admin_users WHERE username = :username LIMIT 1";
        $user_data = $database->fetch($sql, [':username' => 'carlos']);
        
        if ($user_data && isset($user_data['password'])) {
            echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
            echo '<h3 class="font-semibold text-green-800">✅ Hash de mot de passe trouvé</h3>';
            echo '<p class="text-green-700 text-xs">Hash: ' . substr($user_data['password'], 0, 20) . '...</p>';
            echo '<p class="text-green-600 text-sm mt-2">Le mot de passe est stocké de manière sécurisée</p>';
            echo '</div>';
        } else {
            echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
            echo '<h3 class="font-semibold text-yellow-800">⚠️ Aucun mot de passe trouvé pour carlos</h3>';
            echo '<p class="text-yellow-700">Le compte existe mais n\'a pas de mot de passe défini</p>';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-red-800">❌ Erreur lors de la récupération du mot de passe</h3>';
        echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
        echo '</div>';
    }
}

// Check login attempts
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">8. Vérification des tentatives de connexion</h2>';
try {
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts WHERE attempt_result = 'failed' AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    $attempts = $database->fetch($sql);
    
    if ($attempts && $attempts['attempts'] > 0) {
        echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-yellow-800">⚠️ Tentatives de connexion récentes</h3>';
        echo '<p class="text-yellow-700">' . $attempts['attempts'] . ' tentatives échouées dans les 15 dernières minutes</p>';
        echo '</div>';
    } else {
        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-green-800">✅ Aucune tentative récente</h3>';
        echo '<p class="text-green-700">Pas de tentatives échouées récentes</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-gray-800">ℹ️ Table login_attempts non trouvée</h3>';
    echo '<p class="text-gray-700">La table de suivi des tentatives n\'existe pas encore</p>';
    echo '</div>';
}

// Summary and recommendations
echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-6">';
echo '<h3 class="text-lg font-semibold text-blue-800 mb-3">';
echo '<i class="fas fa-lightbulb mr-2"></i>Recommandations et Solutions';
echo '</h3>';
echo '<div class="space-y-3 text-blue-700">';

if (!$carlos) {
    echo '<div class="flex items-start">';
    echo '<i class="fas fa-exclamation-triangle mr-2 mt-1 text-yellow-600"></i>';
    echo '<div>';
    echo '<p><strong>Compte "carlos" manquant:</strong></p>';
    echo '<p class="text-sm ml-4">Le compte n\'existe pas dans la base de données. Vous devez:</p>';
    echo '<ul class="text-sm ml-8 list-disc">';
    echo '<li>Vérifier le nom d\'utilisateur (sensible à la casse)</li>';
    echo '<li>Créer le compte si nécessaire</li>';
    echo '<li>Vérifier dans la table users si elle existe</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
} elseif ($carlos['status'] !== 'active') {
    echo '<div class="flex items-start">';
    echo '<i class="fas fa-user-lock mr-2 mt-1 text-red-600"></i>';
    echo '<div>';
    echo '<p><strong>Compte désactivé:</strong></p>';
    echo '<p class="text-sm ml-4">Le compte existe mais n\'est pas actif. Statut actuel: ' . $carlos['status'] . '</p>';
    echo '<p class="text-sm ml-4">Solution: Activez le compte avec la requête SQL:</p>';
    echo '<code class="text-xs ml-8 bg-gray-200 px-2 py-1 rounded">UPDATE admin_users SET status = 'active' WHERE username = 'carlos';</code>';
    echo '</div>';
    echo '</div>';
}

echo '<div class="flex items-start">';
echo '<i class="fas fa-key mr-2 mt-1 text-green-600"></i>';
echo '<div>';
echo '<p><strong>Vérification du mot de passe:</strong></p>';
echo '<p class="text-sm ml-4">Assurez-vous que:</p>';
echo '<ul class="text-sm ml-8 list-disc">';
echo '<li>Le mot de passe est correctement saisi</li>';
echo '<li>La casse est respectée si applicable</li>';
echo '<li>Le mot de passe n\'a pas été modifié récemment</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo '<div class="flex items-start">';
echo '<i class="fas fa-database mr-2 mt-1 text-blue-600"></i>';
echo '<div>';
echo '<p><strong>Debug supplémentaire:</strong></p>';
echo '<p class="text-sm ml-4">Pour plus d\'informations, vous pouvez:</p>';
echo '<ul class="text-sm ml-8 list-disc">';
echo '<li>Vérifier les logs PHP pour les erreurs</li>';
echo '<li>Tester avec un autre utilisateur si disponible</li>';
echo '<li>Vérifier la configuration de la base de données</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

// Quick fix options
echo '<div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-6">';
echo '<h3 class="text-lg font-semibold text-green-800 mb-3">';
echo '<i class="fas fa-tools mr-2"></i>Options de Résolution Rapide';
echo '</h3>';
echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

echo '<div class="space-y-2">';
echo '<h4 class="font-semibold text-green-700">Si le compte n\'existe pas:</h4>';
echo '<div class="bg-white border border-green-200 rounded p-3">';
echo '<code class="text-xs text-green-800">';
echo "INSERT INTO admin_users (username, password, status, created_at) VALUES<br>";
echo "('carlos', '" . password_hash('carlos123', PASSWORD_DEFAULT) . "', 'active', NOW());";
echo '</code>';
echo '</div>';
echo '<p class="text-xs text-green-600">Mot de passe par défaut: carlos123</p>';
echo '</div>';

echo '<div class="space-y-2">';
echo '<h4 class="font-semibold text-green-700">Si le compte est désactivé:</h4>';
echo '<div class="bg-white border border-green-200 rounded p-3">';
echo '<code class="text-xs text-green-800">';
echo "UPDATE admin_users SET status = 'active' WHERE username = 'carlos';";
echo '</code>';
echo '</div>';
echo '<p class="text-xs text-green-600">Active le compte immédiatement</p>';
echo '</div>';

echo '</div>';
echo '</div>';

echo '<div class="mt-6 text-center">';
echo '<a href="login_modern.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors inline-block">';
echo '<i class="fas fa-arrow-left mr-2"></i>Retour à la connexion';
echo '</a>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>