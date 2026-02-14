<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/includes/init.php';

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: login_modern.php');
    exit;
}
 
// Get current user
$current_user = $auth->getCurrentUser();

// Get dashboard data
$clientModel = ModelFactory::client();
$deviceModel = ModelFactory::device();
$stockModel = ModelFactory::stockArticle();
$historiqueModel = ModelFactory::historique();

// Statistics
$stats = [
    'total_clients' => $clientModel->count(),
    'total_devices' => $deviceModel->count(),
    'total_stock_items' => $stockModel->count(),
    'low_stock_items' => count($stockModel->findLowStock()),
    'pending_devices' => $deviceModel->count(['status' => 'pending']),
    'in_progress_devices' => $deviceModel->count(['status' => 'in_progress']),
    'completed_devices' => $deviceModel->count(['status' => 'completed'])
];

// Recent activity
$recent_activity = $historiqueModel->getRecent(10);

// Device statistics for chart
$device_stats = $deviceModel->getStatistics();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - R.E.Mobiles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/plotly.js/2.26.0/plotly.min.js"></script>
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
        .gradient-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
    </style>
</head>
<body>
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 glass fixed h-full">
            <div class="p-6">
                <div class="text-center mb-8">
                    <div class="glass-card rounded-xl p-4 mb-4 inline-block">
                        <i class="fas fa-mobile-alt text-3xl gradient-text"></i>
                    </div>
                    <h1 class="text-xl font-bold text-white">R.E.Mobiles</h1>
                </div>
                
                <nav class="space-y-2">
                    <a href="dashboard_modern.php" class="nav-item active flex items-center p-3 rounded-lg text-white">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Tableau de Bord
                    </a>
                    <a href="clients.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-users mr-3"></i>
                        Clients
                    </a>
                    <a href="devices.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-mobile-alt mr-3"></i>
                        Appareils
                    </a>
                    <a href="stock.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-boxes mr-3"></i>
                        Stock
                    </a>
                    <a href="historique.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
                        <i class="fas fa-history mr-3"></i>
                        Historique
                    </a>
                    <a href="settings.php" class="nav-item flex items-center p-3 rounded-lg text-white/80 hover:text-white">
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
                <h1 class="text-3xl font-bold text-white mb-2">Tableau de Bord</h1>
                <p class="text-white/80">Bienvenue, <?php echo htmlspecialchars($current_user['username']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Clients</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_clients']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="clients.php" class="text-sm text-blue-600 hover:text-blue-800">
                            Voir tous les clients <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Appareils en cours</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['in_progress_devices']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tools text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="devices.php" class="text-sm text-yellow-600 hover:text-yellow-800">
                            Gérer les appareils <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Stock Total</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_stock_items']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-boxes text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="stock.php" class="text-sm text-green-600 hover:text-green-800">
                            Voir le stock <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Stock faible</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['low_stock_items']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="stock.php?filter=low" class="text-sm text-red-600 hover:text-red-800">
                            Réapprovisionner <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Charts and Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Device Status Chart -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-pie mr-2 text-indigo-600"></i>
                        État des Appareils
                    </h3>
                    <div id="device-chart" style="height: 300px;"></div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-history mr-2 text-indigo-600"></i>
                        Activité Récente
                    </h3>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <i class="fas fa-user text-indigo-600 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">
                                    <?php echo htmlspecialchars($activity['username'] ?? 'Système'); ?>
                                </p>
                                <p class="text-xs text-gray-600">
                                    <?php echo htmlspecialchars($activity['action']); ?> 
                                    <?php echo htmlspecialchars($activity['entity_type']); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo formatDate($activity['created_at']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-bolt mr-2 text-indigo-600"></i>
                    Actions Rapides
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="add_client.php" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-user-plus text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Nouveau Client</p>
                            <p class="text-sm text-gray-600">Ajouter un client</p>
                        </div>
                    </a>

                    <a href="add_device.php" class="flex items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-plus text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Nouvel Appareil</p>
                            <p class="text-sm text-gray-600">Créer une réparation</p>
                        </div>
                    </a>

                    <a href="add_stock.php" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-box text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Ajouter Stock</p>
                            <p class="text-sm text-gray-600">Gérer l'inventaire</p>
                        </div>
                    </a>

                    <a href="reports.php" class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-chart-bar text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Rapports</p>
                            <p class="text-sm text-gray-600">Voir les statistiques</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Device status chart
        const deviceData = [{
            values: [
                <?php echo $stats['pending_devices']; ?>,
                <?php echo $stats['in_progress_devices']; ?>,
                <?php echo $stats['completed_devices']; ?>
            ],
            labels: ['En attente', 'En cours', 'Terminé'],
            type: 'pie',
            hole: 0.4,
            marker: {
                colors: ['#F59E0B', '#3B82F6', '#10B981']
            },
            textinfo: 'label+percent',
            textposition: 'outside',
            hovertemplate: '<b>%{label}</b><br>%{value} appareils<br>%{percent}<extra></extra>'
        }];

        const deviceLayout = {
            showlegend: false,
            margin: { t: 0, b: 0, l: 0, r: 0 },
            paper_bgcolor: 'rgba(0,0,0,0)',
            plot_bgcolor: 'rgba(0,0,0,0)',
            font: {
                family: 'Inter, sans-serif',
                size: 12,
                color: '#374151'
            }
        };

        const deviceConfig = {
            displayModeBar: false,
            responsive: true
        };

        Plotly.newPlot('device-chart', deviceData, deviceLayout, deviceConfig);

        // Animations
        anime({
            targets: '.stat-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(100),
            easing: 'easeOutQuart'
        });

        anime({
            targets: '.glass-card',
            scale: [0.95, 1],
            opacity: [0, 1],
            duration: 1000,
            delay: anime.stagger(150, {start: 400}),
            easing: 'easeOutQuart'
        });

        // Auto-refresh data every 30 seconds
        setInterval(function() {
            // In a real application, you would fetch fresh data here
            console.log('Refreshing dashboard data...');
        }, 30000);
    </script>
</body>
</html>