<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Main index page for remshop1 database version
 * This is the independent version for testing
 */

require_once __DIR__ . '/includes/init_remshop1.php';

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: login_remshop1.php');
    exit;
}

// Get current user
$current_user = $auth->getCurrentUser();

// Get dashboard statistics
try {
    // Total clients
    $client_count = $db->fetch("SELECT COUNT(*) as total FROM clients")['total'];
    
    // Total devices
    $device_count = $db->fetch("SELECT COUNT(*) as total FROM devices")['total'];
    
    // Total repairs
    $repair_count = $db->fetch("SELECT COUNT(*) as total FROM repairs")['total'];
    
    // Total stock articles
    $stock_count = $db->fetch("SELECT COUNT(*) as total FROM stock_articles")['total'];
    
    // Recent repairs
    $recent_repairs = $db->fetchAll("SELECT r.*, c.company_name, c.first_name, c.last_name 
                                    FROM repairs r 
                                    LEFT JOIN clients c ON r.client_id = c.id 
                                    ORDER BY r.created_at DESC 
                                    LIMIT 5");
    
    // Recent clients
    $recent_clients = $db->fetchAll("SELECT * FROM clients ORDER BY created_at DESC LIMIT 5");
    
    // Low stock alerts
    $low_stock_items = $db->fetchAll("SELECT * FROM stock_articles 
                                     WHERE quantity_in_stock <= min_stock_level 
                                     AND min_stock_level > 0");
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement des statistiques: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - R.E.Mobiles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .glass-dark {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="glass-dark text-white p-4 mb-8">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <i class="fas fa-mobile-alt text-2xl gradient-text"></i>
                <h1 class="text-xl font-bold">R.E.Mobiles</h1>
                <span class="text-sm opacity-75">Base de données: u498346438_remshop1</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Bienvenue, <?php echo htmlspecialchars($current_user['username']); ?></span>
                <a href="logout_remshop1.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-sm transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass text-white p-6 rounded-xl stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-75">Clients</p>
                        <p class="text-3xl font-bold"><?php echo $client_count; ?></p>
                    </div>
                    <i class="fas fa-users text-4xl opacity-50"></i>
                </div>
                <a href="clients_remshop1.php" class="inline-block mt-4 text-sm hover:underline">
                    Voir tous les clients <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="glass text-white p-6 rounded-xl stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-75">Appareils</p>
                        <p class="text-3xl font-bold"><?php echo $device_count; ?></p>
                    </div>
                    <i class="fas fa-mobile-alt text-4xl opacity-50"></i>
                </div>
                <a href="devices_remshop1.php" class="inline-block mt-4 text-sm hover:underline">
                    Voir tous les appareils <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="glass text-white p-6 rounded-xl stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-75">Réparations</p>
                        <p class="text-3xl font-bold"><?php echo $repair_count; ?></p>
                    </div>
                    <i class="fas fa-tools text-4xl opacity-50"></i>
                </div>
                <a href="repairs_remshop1.php" class="inline-block mt-4 text-sm hover:underline">
                    Voir toutes les réparations <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="glass text-white p-6 rounded-xl stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-75">Stock</p>
                        <p class="text-3xl font-bold"><?php echo $stock_count; ?></p>
                    </div>
                    <i class="fas fa-boxes text-4xl opacity-50"></i>
                </div>
                <a href="stock_remshop1.php" class="inline-block mt-4 text-sm hover:underline">
                    Voir le stock <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass text-white p-6 rounded-xl mb-8">
            <h2 class="text-xl font-bold mb-4">
                <i class="fas fa-bolt mr-2"></i>Actions Rapides
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="add_client_remshop1.php" class="bg-green-500 hover:bg-green-600 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-plus text-2xl mb-2"></i>
                    <p class="text-sm">Nouveau Client</p>
                </a>
                <a href="add_device_remshop1.php" class="bg-blue-500 hover:bg-blue-600 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-mobile-alt text-2xl mb-2"></i>
                    <p class="text-sm">Nouvel Appareil</p>
                </a>
                <a href="add_repair_remshop1.php" class="bg-purple-500 hover:bg-purple-600 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-tools text-2xl mb-2"></i>
                    <p class="text-sm">Nouvelle Réparation</p>
                </a>
                <a href="add_stock_remshop1.php" class="bg-orange-500 hover:bg-orange-600 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-boxes text-2xl mb-2"></i>
                    <p class="text-sm">Ajouter Stock</p>
                </a>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Repairs -->
            <div class="lg:col-span-2">
                <div class="glass text-white p-6 rounded-xl">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-tools mr-2"></i>Réparations Récentes
                    </h2>
                    
                    <?php if (!empty($recent_repairs)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_repairs as $repair): ?>
                                <div class="bg-white bg-opacity-10 p-4 rounded-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold">
                                            #<?php echo htmlspecialchars($repair['repair_number'] ?? 'N/A'); ?>
                                        </h3>
                                        <span class="text-sm bg-blue-500 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($repair['status'] ?? 'En attente'); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm opacity-75 mb-2">
                                        <?php echo htmlspecialchars($repair['problem_description'] ?? 'Pas de description'); ?>
                                    </p>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="opacity-75">
                                            <?php echo htmlspecialchars($repair['company_name'] ?? 
                                                ($repair['first_name'] . ' ' . $repair['last_name']) ?? 'Client inconnu'); ?>
                                        </span>
                                        <span class="opacity-75">
                                            <?php echo format_date($repair['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center opacity-75 py-8">
                            Aucune réparation récente
                        </p>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <a href="repairs_remshop1.php" class="text-blue-400 hover:text-blue-300 text-sm">
                            Voir toutes les réparations <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Recent Clients -->
                <div class="glass text-white p-6 rounded-xl">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-users mr-2"></i>Clients Récents
                    </h3>
                    
                    <?php if (!empty($recent_clients)): ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_clients as $client): ?>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm font-bold">
                                        <?php echo substr($client['first_name'] ?? 'C', 0, 1); ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold">
                                            <?php echo htmlspecialchars(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')); ?>
                                        </p>
                                        <p class="text-xs opacity-75">
                                            <?php echo htmlspecialchars($client['company_name'] ?? 'Particulier'); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center opacity-75 py-4">
                            Aucun client récent
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Stock Alerts -->
                <div class="glass text-white p-6 rounded-xl">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Alertes Stock
                    </h3>
                    
                    <?php if (!empty($low_stock_items)): ?>
                        <div class="space-y-2">
                            <?php foreach ($low_stock_items as $item): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="truncate">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </span>
                                    <span class="text-red-400 font-bold">
                                        <?php echo $item['quantity_in_stock']; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center opacity-75 py-4">
                            <i class="fas fa-check-circle text-green-400 mr-2"></i>
                            Stock niveau normal
                        </p>
                    <?php endif; ?>
                </div>

                <!-- System Info -->
                <div class="glass text-white p-6 rounded-xl">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Info Système
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="opacity-75">Base de données:</span>
                            <span class="text-blue-400">u498346438_remshop1</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-75">Version:</span>
                            <span>2.0.0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-75">Utilisateur:</span>
                            <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-75">IP:</span>
                            <span><?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-dark text-white text-center p-4 mt-12">
        <p class="text-sm opacity-75">
            R.E.Mobiles Système de Gestion v2.0.0 - Base de données indépendante u498346438_remshop1
        </p>
    </footer>

    <script>
        // Animate statistics cards on load
        document.addEventListener('DOMContentLoaded', function() {
            anime({
                targets: '.stat-card',
                translateY: [50, 0],
                opacity: [0, 1],
                delay: anime.stagger(100),
                duration: 800,
                easing: 'easeOutExpo'
            });
        });
    </script>
</body>
</html>