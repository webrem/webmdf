<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Setup User Carlos Script for R.E.Mobiles
 * Creates or resets the carlos user account
 */

require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>'; 
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Setup User Carlos - R.E.Mobiles</title>';
echo '<link href="https://cdn.tailwindcss.com" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
echo '</head>';
echo '<body class="bg-gray-100 py-8">';
echo '<div class="max-w-4xl mx-auto px-4">';
echo '<div class="bg-white rounded-lg shadow-lg p-6">';
echo '<h1 class="text-3xl font-bold text-center mb-8 text-gray-800">';
echo '<i class="fas fa-user-plus mr-2"></i>Setup Compte Carlos';
echo '</h1>';

$database = Database::getInstance();
$action = $_POST['action'] ?? '';
$message = '';
$message_type = '';

if ($action) {
    try {
        if ($action === 'create') {
            // Create carlos user
            $password = $_POST['password'] ?? 'carlos123';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $data = [
                'username' => 'carlos',
                'password' => $hashed_password,
                'status' => 'active',
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
                'permissions' => json_encode(['all' => true])
            ];
            
            $sql = "INSERT INTO admin_users (username, password, status, role, created_at, permissions) 
                    VALUES (:username, :password, :status, :role, :created_at, :permissions)";
            
            $database->execute($sql, $data);
            
            $message = 'Compte carlos créé avec succès! Mot de passe: ' . $password;
            $message_type = 'success';
            
        } elseif ($action === 'reset') {
            // Reset carlos password
            $password = $_POST['password'] ?? 'carlos123';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE admin_users SET password = :password, status = 'active' WHERE username = :username";
            $database->execute($sql, [
                ':password' => $hashed_password,
                ':username' => 'carlos'
            ]);
            
            $message = 'Mot de passe de carlos réinitialisé! Nouveau mot de passe: ' . $password;
            $message_type = 'success';
            
        } elseif ($action === 'activate') {
            // Activate carlos account
            $sql = "UPDATE admin_users SET status = 'active' WHERE username = :username";
            $database->execute($sql, [':username' => 'carlos']);
            
            $message = 'Compte carlos activé avec succès!';
            $message_type = 'success';
            
        } elseif ($action === 'delete') {
            // Delete carlos user
            $sql = "DELETE FROM admin_users WHERE username = :username";
            $database->execute($sql, [':username' => 'carlos']);
            
            $message = 'Compte carlos supprimé avec succès!';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Check current status of carlos
echo '<div class="mb-6">';
echo '<h2 class="text-xl font-semibold mb-3 text-gray-700">Statut actuel du compte carlos</h2>';

try {
    $sql = "SELECT id, username, status, role, created_at FROM admin_users WHERE username = :username LIMIT 1";
    $carlos = $database->fetch($sql, [':username' => 'carlos']);
    
    if ($carlos) {
        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-green-800">✅ Compte carlos trouvé</h3>';
        echo '<div class="mt-2 space-y-1">';
        echo '<p><strong>ID:</strong> ' . $carlos['id'] . '</p>';
        echo '<p><strong>Username:</strong> ' . htmlspecialchars($carlos['username']) . '</p>';
        echo '<p><strong>Rôle:</strong> ' . htmlspecialchars($carlos['role']) . '</p>';
        echo '<p><strong>Statut:</strong> ';
        if ($carlos['status'] === 'active') {
            echo '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Actif</span>';
        } else {
            echo '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Inactif</span>';
        }
        echo '</p>';
        echo '<p><strong>Créé le:</strong> ' . date('d/m/Y H:i', strtotime($carlos['created_at'])) . '</p>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
        echo '<h3 class="font-semibold text-red-800">❌ Compte carlos non trouvé</h3>';
        echo '<p class="text-red-700">Le compte n\'existe pas encore dans la base de données</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
    echo '<h3 class="font-semibold text-red-800">❌ Erreur lors de la vérification</h3>';
    echo '<p class="text-red-700">' . $e->getMessage() . '</p>';
    echo '</div>';
}

// Show message if any
if ($message) {
    $bg_color = $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
    $icon = $message_type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    
    echo '<div class="mb-6 p-4 rounded-lg border ' . $bg_color . '">';
    echo '<div class="flex items-center">';
    echo '<i class="fas ' . $icon . ' mr-2"></i>';
    echo htmlspecialchars($message);
    echo '</div>';
    echo '</div>';
}

// Action forms
echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">';

// Create/Reset form
echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-6">';
echo '<h3 class="text-lg font-semibold text-blue-800 mb-4">';
echo '<i class="fas fa-user-plus mr-2"></i>Créer/Réinitialiser Carlos';
echo '</h3>';
echo '<form method="POST">';
echo '<input type="hidden" name="action" value="' . ($carlos ? 'reset' : 'create') . '">';
echo '<div class="mb-4">';
echo '<label class="block text-sm font-semibold text-blue-700 mb-2">Mot de passe</label>';
echo '<input type="text" name="password" value="carlos123" ';
echo 'class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">';
echo '<p class="text-xs text-blue-600 mt-1">Mot de passe par défaut: carlos123</p>';
echo '</div>';
echo '<button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">';
echo '<i class="fas fa-save mr-2"></i>' . ($carlos ? 'Réinitialiser' : 'Créer') . ' le compte';
echo '</button>';
echo '</form>';
echo '</div>';

// Activate form
echo '<div class="bg-green-50 border border-green-200 rounded-lg p-6">';
echo '<h3 class="text-lg font-semibold text-green-800 mb-4">';
echo '<i class="fas fa-user-check mr-2"></i>Activer Carlos';
echo '</h3>';
if ($carlos && $carlos['status'] !== 'active') {
    echo '<form method="POST">';
    echo '<input type="hidden" name="action" value="activate">';
    echo '<p class="text-green-700 mb-4">Le compte existe mais est désactivé. Activez-le pour permettre la connexion.</p>';
    echo '<button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">';
    echo '<i class="fas fa-unlock mr-2"></i>Activer le compte';
    echo '</button>';
    echo '</form>';
} else {
    echo '<p class="text-green-700">Le compte est déjà actif ou n\'existe pas.</p>';
}
echo '</div>';

echo '</div>';

// Delete form (if carlos exists)
if ($carlos) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">';
    echo '<h3 class="text-lg font-semibold text-red-800 mb-4">';
    echo '<i class="fas fa-user-times mr-2"></i>Supprimer Carlos';
    echo '</h3>';
    echo '<form method="POST" onsubmit="return confirm(\'Êtes-vous sûr de vouloir supprimer le compte carlos ?\')">';
    echo '<input type="hidden" name="action" value="delete">';
    echo '<p class="text-red-700 mb-4">Cette action est irréversible. Le compte sera définitivement supprimé.</p>';
    echo '<button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">';
    echo '<i class="fas fa-trash mr-2"></i>Supprimer le compte';
    echo '</button>';
    echo '</form>';
    echo '</div>';
}

// Test login form
echo '<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">';
echo '<h3 class="text-lg font-semibold text-gray-800 mb-4">';
echo '<i class="fas fa-sign-in-alt mr-2"></i>Test de Connexion';
echo '</h3>';
echo '<div class="flex space-x-4">';
echo '<a href="login_modern.php" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">';
echo '<i class="fas fa-external-link-alt mr-2"></i>Aller à la page de connexion';
echo '</a>';
echo '<a href="debug_login.php" class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">';
echo '<i class="fas fa-bug mr-2"></i>Debug détaillé';
echo '</a>';
echo '</div>';
echo '</div>';

// System information
echo '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4">';
echo '<h3 class="text-sm font-semibold text-gray-800 mb-2">Informations Système</h3>';
echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600">';
echo '<div><strong>Base de données:</strong> ' . CONFIG['database']['name'] . '</div>';
echo '<div><strong>Table utilisateurs:</strong> admin_users</div>';
echo '<div><strong>Authentification:</strong> CSRF + Password Hash</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>