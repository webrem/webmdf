<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 
require_once __DIR__ . '/includes/init.php';

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: login_modern.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$historiqueModel = ModelFactory::historique();

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_user = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query conditions
$conditions = [];
$params = [];

if ($filter_type) {
    $conditions[] = "h.entity_type = :type";
    $params[':type'] = $filter_type;
}

if ($filter_user) {
    $conditions[] = "u.username = :username";
    $params[':username'] = $filter_user;
}

if ($date_from) {
    $conditions[] = "h.created_at >= :date_from";
    $params[':date_from'] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $conditions[] = "h.created_at <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}

// Build the query
$where_clause = '';
if (!empty($conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $conditions);
}

// Get history records
$sql = "SELECT h.*, u.username 
        FROM historiques h 
        LEFT JOIN admin_users u ON h.user_id = u.id 
        $where_clause 
        ORDER BY h.created_at DESC 
        LIMIT 100";

$history_records = $database->fetchAll($sql, $params);

// Get filter options
$types = $database->fetchAll("SELECT DISTINCT entity_type FROM historiques ORDER BY entity_type");
$users = $database->fetchAll("SELECT DISTINCT username FROM admin_users WHERE status = 'active' ORDER BY username");

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - R.E.Mobiles</title>
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
        .history-card {
            transition: all 0.3s ease;
        }
        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .action-create { background-color: #D1FAE5; color: #065F46; }
        .action-update { background-color: #DBEAFE; color: #1E40AF; }
        .action-delete { background-color: #FEE2E2; color: #991B1B; }
        .action-login { background-color: #FEF3C7; color: #92400E; }
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
                    <a href="historique.php" class="nav-item active flex items-center p-3 rounded-lg text-white">
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
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Historique des Activités</h1>
                    <p class="text-white/80">Suivez toutes les actions du système</p>
                </div>
                <div class="flex space-x-4">
                    <button onclick="exportHistory()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-download mr-2"></i>Exporter
                    </button>
                    <button onclick="clearFilters()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Effacer filtres
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6">
                <div class="glass-card rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-filter mr-2"></i>Filtres
                    </h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Type d'entité</label>
                            <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Tous les types</option>
                                <?php foreach ($types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['entity_type']); ?>" 
                                        <?php echo $filter_type === $type['entity_type'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($type['entity_type'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Utilisateur</label>
                            <select name="user" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Tous les utilisateurs</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                        <?php echo $filter_user === $user['username'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date de début</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date de fin</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div class="md:col-span-4 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Actions Total</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format(count($history_records)); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Créations</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count(array_filter($history_records, fn($r) => $r['action'] === 'create')); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Modifications</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count(array_filter($history_records, fn($r) => $r['action'] === 'update')); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-edit text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Suppressions</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count(array_filter($history_records, fn($r) => $r['action'] === 'delete')); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-trash text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Records -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-history mr-2"></i>
                    Journal d'Activité
                </h3>

                <?php if (empty($history_records)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-history text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucune activité trouvée</h3>
                    <p class="text-gray-500">Essayez d'ajuster vos filtres</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($history_records as $record): ?>
                    <div class="history-card bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold action-<?php echo $record['action']; ?> mr-3">
                                        <?php
                                        $action_labels = [
                                            'create' => 'Création',
                                            'update' => 'Modification',
                                            'delete' => 'Suppression',
                                            'login' => 'Connexion'
                                        ];
                                        echo $action_labels[$record['action']] ?? $record['action'];
                                        ?>
                                    </span>
                                    <span class="text-sm font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($record['username'] ?? 'Système'); ?>
                                    </span>
                                    <span class="text-gray-400 mx-2">•</span>
                                    <span class="text-sm text-gray-600">
                                        <?php echo ucfirst($record['entity_type']); ?> #<?php echo $record['entity_id']; ?>
                                    </span>
                                </div>
                                
                                <?php if ($record['details']): ?>
                                <p class="text-sm text-gray-700 mb-2">
                                    <strong>Détails:</strong> <?php echo htmlspecialchars($record['details']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-xs text-gray-500">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <?php echo htmlspecialchars($record['ip_address'] ?? 'Inconnu'); ?>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2 ml-4">
                                <button onclick="showDetails(<?php echo $record['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-eye mr-1"></i>Détails
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Export Modal -->
            <div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="glass-card rounded-xl p-6 m-4 max-w-md w-full">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Exporter l'historique</h2>
                        <button onclick="closeExportModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Format</label>
                            <select id="exportFormat" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="csv">CSV</option>
                                <option value="json">JSON</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Période</label>
                            <select id="exportPeriod" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="7">7 derniers jours</option>
                                <option value="30">30 derniers jours</option>
                                <option value="90">90 derniers jours</option>
                                <option value="all">Tout</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <button onclick="closeExportModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                            Annuler
                        </button>
                        <button onclick="confirmExport()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-download mr-2"></i>Exporter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function clearFilters() {
            window.location.href = 'historique.php';
        }

        function exportHistory() {
            document.getElementById('exportModal').classList.remove('hidden');
            document.getElementById('exportModal').classList.add('flex');
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.add('hidden');
            document.getElementById('exportModal').classList.remove('flex');
        }

        function confirmExport() {
            const format = document.getElementById('exportFormat').value;
            const period = document.getElementById('exportPeriod').value;
            
            // In a real implementation, this would trigger a download
            alert(`Export en cours...\nFormat: ${format}\nPériode: ${period} jours`);
            closeExportModal();
        }

        function showDetails(recordId) {
            alert(`Détails de l'enregistrement #${recordId}\n\nCette fonctionnalité afficherait plus de détails sur l'action.`);
        }

        // Animation on load
        anime({
            targets: '.history-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(100),
            easing: 'easeOutQuart'
        });
    </script>
</body>
</html>