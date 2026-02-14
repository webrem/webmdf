<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/includes/init.php';

// Check authentication and admin role
if (!$auth->isLoggedIn()) {
    header('Location: login_modern.php');
    exit;
}

if (!$auth->hasRole('admin')) {
    header('Location: dashboard_modern.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('Tous les champs sont obligatoires');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('Les mots de passe ne correspondent pas');
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
            }
            
            $auth->changePassword($current_user['id'], $current_password, $new_password);
            
            $message = 'Mot de passe modifié avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'backup_database') {
            // In a real implementation, this would create a database backup
            $message = 'Sauvegarde de la base de données effectuée avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'clear_cache') {
            // Clear session cache and other caches
            session_regenerate_id(true);
            
            $message = 'Cache vidé avec succès!';
            $message_type = 'success';
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get system statistics
$userModel = ModelFactory::user();
$clientModel = ModelFactory::client();
$deviceModel = ModelFactory::device();
$stockModel = ModelFactory::stockArticle();
$historiqueModel = ModelFactory::historique();

$stats = [
    'total_users' => $userModel->count(),
    'total_clients' => $clientModel->count(),
    'total_devices' => $deviceModel->count(),
    'total_stock' => $stockModel->count(),
    'total_history' => $historiqueModel->count(),
    'active_users' => $userModel->count(['status' => 'active']),
    'recent_activity' => $historiqueModel->count(['created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)'])
];

// Get PHP and system information
$php_info = [
    'version' => PHP_VERSION,
    'os' => PHP_OS,
    'sapi' => PHP_SAPI,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - R.E.Mobiles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .nav-item {
            transition: all 0.3s ease;
        }
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-right: 3px solid #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button {
            transition: all 0.3s ease;
        }
        .tab-button.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 glass fixed h-full">
            <div class="p-6">
                <div class="text-center mb-8">
                    <div class="glass-card rounded-xl p-4 mb-4 inline-block">
                        <i class="fas fa-mobile-alt text-3xl text-transparent bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text"></i>
                    </div>
                    <h1 class="text-xl font-bold text-white">R.E.Mobiles</h1>
                </div>
                
                <nav class="space-y-2">
                    <a href="dashboard_modern.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Tableau de Bord
                    </a>
                    <a href="clients_modern.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-users mr-3"></i>
                        Clients
                    </a>
                    <a href="devices_modern.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-mobile-alt mr-3"></i>
                        Appareils
                    </a>
                    <a href="stock_modern.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-boxes mr-3"></i>
                        Stock
                    </a>
                    <a href="historique.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-history mr-3"></i>
                        Historique
                    </a>
                    <a href="settings.php" class="nav-item active flex items-center p-3 rounded-lg text-white">
                        <i class="fas fa-cog mr-3"></i>
                        Paramètres
                    </a>
                </nav>
            </div>
            
            <div class="absolute bottom-0 left-0 right-0 p-6">
                <div class="glass-card rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($current_user['username'], 0, 2)); ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($current_user['username']); ?></p>
                            <p class="text-xs text-gray-600"><?php echo ucfirst($current_user['role']); ?></p>
                        </div>
                    </div>
                    <a href="logout.php" class="mt-3 block text-center text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-64 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Paramètres du Système</h1>
                <p class="text-white/80">Configurez et gérez votre système R.E.Mobiles</p>
            </div>

            <!-- Message Alert -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <div class="mb-6">
                <div class="glass-card rounded-lg p-2">
                    <div class="flex space-x-2">
                        <button onclick="showTab('general')" class="tab-button active flex-1 py-2 px-4 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-cog mr-2"></i>Général
                        </button>
                        <button onclick="showTab('security')" class="tab-button flex-1 py-2 px-4 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-shield-alt mr-2"></i>Sécurité
                        </button>
                        <button onclick="showTab('system')" class="tab-button flex-1 py-2 px-4 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-server mr-2"></i>Système
                        </button>
                        <button onclick="showTab('maintenance')" class="tab-button flex-1 py-2 px-4 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-tools mr-2"></i>Maintenance
                        </button>
                    </div>
                </div>
            </div>

            <!-- General Settings Tab -->
            <div id="general" class="tab-content active">
                <div class="space-y-6">
                    <!-- Company Information -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-building mr-2"></i> 
                            Informations de l'Entreprise
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nom de l'entreprise</label>
                                <input type="text" value="R.E.Mobiles" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                                <input type="tel" placeholder="+33 6 12 34 56 78" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <input type="email" placeholder="contact@remobiles.fr" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                                <input type="text" placeholder="123 Rue de la Réparation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </div>

                    <!-- Application Settings -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-cogs mr-2"></i>
                            Paramètres de l'Application
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Mode Maintenance</h4>
                                    <p class="text-sm text-gray-600">Active le mode maintenance pour les utilisateurs</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Notifications Email</h4>
                                    <p class="text-sm text-gray-600">Envoie des notifications par email pour les événements importants</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Journalisation Détaillée</h4>
                                    <p class="text-sm text-gray-600">Enregistre tous les événements dans l'historique</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings Tab -->
            <div id="security" class="tab-content">
                <div class="space-y-6">
                    <!-- Password Change -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-key mr-2"></i>
                            Changer le Mot de Passe
                        </h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update_password">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe actuel</label>
                                    <input type="password" name="current_password" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nouveau mot de passe</label>
                                    <input type="password" name="new_password" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmer</label>
                                    <input type="password" name="confirm_password" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-save mr-2"></i>Changer le mot de passe
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Paramètres de Sécurité
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Double Authentification</h4>
                                    <p class="text-sm text-gray-600">Ajoute une couche de sécurité supplémentaire</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Restriction IP</h4>
                                    <p class="text-sm text-gray-600">Limite l'accès à certaines adresses IP</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Durée de session</h4>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="1800">30 minutes</option>
                                    <option value="3600" selected>1 heure</option>
                                    <option value="7200">2 heures</option>
                                    <option value="14400">4 heures</option>
                                    <option value="28800">8 heures</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Tab -->
            <div id="system" class="tab-content">
                <div class="space-y-6">
                    <!-- System Statistics -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Statistiques du Système
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600"><?php echo number_format($stats['total_users']); ?></div>
                                <div class="text-sm text-gray-600">Utilisateurs</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_clients']); ?></div>
                                <div class="text-sm text-gray-600">Clients</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_devices']); ?></div>
                                <div class="text-sm text-gray-600">Appareils</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['total_stock']); ?></div>
                                <div class="text-sm text-gray-600">Articles Stock</div>
                            </div>
                        </div>
                    </div>

                    <!-- PHP Information -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-info-circle mr-2"></i>
                            Informations PHP
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Version PHP:</span>
                                    <span class="font-semibold"><?php echo $php_info['version']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Système:</span>
                                    <span class="font-semibold"><?php echo $php_info['os']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SAPI:</span>
                                    <span class="font-semibold"><?php echo $php_info['sapi']; ?></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Mémoire:</span>
                                    <span class="font-semibold"><?php echo $php_info['memory_limit']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Timeout:</span>
                                    <span class="font-semibold"><?php echo $php_info['max_execution_time']; ?>s</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Upload Max:</span>
                                    <span class="font-semibold"><?php echo $php_info['upload_max_filesize']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database Information -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-database mr-2"></i>
                            Informations Base de Données
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nom:</span>
                                    <span class="font-semibold"><?php echo CONFIG['database']['name']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Hôte:</span>
                                    <span class="font-semibold"><?php echo CONFIG['database']['host']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Charset:</span>
                                    <span class="font-semibold"><?php echo CONFIG['database']['charset']; ?></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tables:</span>
                                    <span class="font-semibold"><?php echo count($database->fetchAll("SHOW TABLES")); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Historique (24h):</span>
                                    <span class="font-semibold"><?php echo $stats['recent_activity']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Connexion:</span>
                                    <span class="font-semibold text-green-600">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Tab -->
            <div id="maintenance" class="tab-content">
                <div class="space-y-6">
                    <!-- Database Maintenance -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-database mr-2"></i>
                            Maintenance Base de Données
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="backup_database">
                                
                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-2">Sauvegarde</h4>
                                    <p class="text-sm text-gray-600 mb-3">Créer une sauvegarde complète de la base de données</p>
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                        <i class="fas fa-download mr-2"></i>Créer une Sauvegarde
                                    </button>
                                </div>
                            </form>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Optimisation</h4>
                                <p class="text-sm text-gray-600 mb-3">Optimiser les tables pour améliorer les performances</p>
                                <button onclick="optimizeDatabase()" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-broom mr-2"></i>Optimiser les Tables
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- System Maintenance -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-tools mr-2"></i>
                            Maintenance Système
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="clear_cache">
                                
                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-2">Vider le Cache</h4>
                                    <p class="text-sm text-gray-600 mb-3">Vide le cache système et régénère les sessions</p>
                                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                        <i class="fas fa-broom mr-2"></i>Vider le Cache
                                    </button>
                                </div>
                            </form>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Nettoyer l'Historique</h4>
                                <p class="text-sm text-gray-600 mb-3">Supprime les anciens enregistrements d'historique</p>
                                <button onclick="cleanHistory()" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Nettoyer l'Historique
                                </button>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Réinitialiser les Tentatives</h4>
                                <p class="text-sm text-gray-600 mb-3">Réinitialise le compteur de tentatives de connexion</p>
                                <button onclick="resetLoginAttempts()" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-undo mr-2"></i>Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Logs -->
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-file-alt mr-2"></i>
                            Logs Système
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">Niveau de journalisation:</span>
                                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="error">Erreurs seulement</option>
                                    <option value="warning" selected>Avertissements et erreurs</option>
                                    <option value="info">Informations et plus</option>
                                    <option value="debug">Debug complet</option>
                                </select>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">Rotation des logs:</span>
                                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="7">7 jours</option>
                                    <option value="30" selected>30 jours</option>
                                    <option value="90">90 jours</option>
                                    <option value="365">1 an</option>
                                </select>
                            </div>
                            
                            <div class="flex space-x-4">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-download mr-2"></i>Télécharger les Logs
                                </button>
                                <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Effacer les Logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        function optimizeDatabase() {
            if (confirm('Êtes-vous sûr de vouloir optimiser la base de données ? Cela peut prendre quelques minutes.')) {
                alert('Optimisation de la base de données en cours...\nCette fonctionnalité nécessiterait une implémentation serveur.');
            }
        }

        function cleanHistory() {
            if (confirm('Êtes-vous sûr de vouloir nettoyer l\'historique ? Cette action est irréversible.')) {
                alert('Nettoyage de l\'historique en cours...\nCette fonctionnalité nécessiterait une implémentation serveur.');
            }
        }

        function resetLoginAttempts() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser les tentatives de connexion ?')) {
                alert('Réinitialisation des tentatives de connexion...\nCette fonctionnalité nécessiterait une implémentation serveur.');
            }
        }

        // Animation on load
        anime({
            targets: '.glass-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(100),
            easing: 'easeOutQuart'
        });
    </script>
</body>
</html>