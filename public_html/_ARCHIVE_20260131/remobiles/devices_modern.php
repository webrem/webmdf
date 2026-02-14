<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 
require_once __DIR__ . '/includes/init.php';

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: login_modern.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$deviceModel = ModelFactory::device();
$clientModel = ModelFactory::client();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $data = [
                'client_id' => intval($_POST['client_id']),
                'device_type' => sanitizeInput($_POST['device_type']),
                'brand' => sanitizeInput($_POST['brand']),
                'model' => sanitizeInput($_POST['model']),
                'serial_number' => sanitizeInput($_POST['serial_number']),
                'problem_description' => sanitizeInput($_POST['problem_description']),
                'estimated_cost' => floatval($_POST['estimated_cost']),
                'repair_cost' => floatval($_POST['repair_cost']),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $device_id = $deviceModel->insert($data);
            
            // Log activity
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'create',
                'device',
                $device_id,
                'Appareil créé: ' . $data['brand'] . ' ' . $data['model']
            );
            
            $message = 'Appareil ajouté avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'update_status') {
            $device_id = intval($_POST['device_id']);
            $new_status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            $deviceModel->updateStatus($device_id, $new_status, $notes);
            
            // Log activity
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'device',
                $device_id,
                'Statut changé: ' . $new_status
            );
            
            $message = 'Statut mis à jour avec succès!';
            $message_type = 'success';
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get devices with filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

if ($status_filter) {
    $devices = $deviceModel->findByStatus($status_filter);
} elseif ($search) {
    // Simple search implementation
    $sql = "SELECT d.*, c.name as client_name FROM devices d LEFT JOIN clients c ON d.client_id = c.id 
            WHERE d.brand LIKE :search OR d.model LIKE :search OR c.name LIKE :search 
            ORDER BY d.created_at DESC";
    $devices = $deviceModel->db->fetchAll($sql, [':search' => "%{$search}%"]);
} else {
    $devices = $deviceModel->findAll('created_at DESC');
}

// Get clients for dropdown
$clients = $clientModel->findAll('name ASC');

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appareils - R.E.Mobiles</title>
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
        .device-card {
            transition: all 0.3s ease;
        }
        .device-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .status-pending { background-color: #FEF3C7; color: #92400E; }
        .status-in-progress { background-color: #DBEAFE; color: #1E40AF; }
        .status-completed { background-color: #D1FAE5; color: #065F46; }
        .status-cancelled { background-color: #FEE2E2; color: #991B1B; }
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
                    <a href="devices_modern.php" class="nav-item active flex items-center p-3 rounded-lg text-white">
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
                    <h1 class="text-3xl font-bold text-white mb-2">Gestion des Appareils</h1>
                    <p class="text-white/80">Suivez l'état des réparations</p>
                </div>
                <button onclick="openAddModal()" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvel Appareil
                </button>
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

            <!-- Filters -->
            <div class="mb-6">
                <div class="glass-card rounded-lg p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                name="search" 
                                id="searchInput"
                                placeholder="Rechercher par marque, modèle ou client..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                value="<?php echo htmlspecialchars($search); ?>"
                            >
                        </div>
                        <div class="flex space-x-2">
                            <a href="devices_modern.php" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo !$status_filter ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                Tous
                            </a>
                            <a href="devices_modern.php?status=pending" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $status_filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'; ?>">
                                En attente
                            </a>
                            <a href="devices_modern.php?status=in_progress" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $status_filter === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'; ?>">
                                En cours
                            </a>
                            <a href="devices_modern.php?status=completed" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $status_filter === 'completed' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?>">
                                Terminé
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Devices Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($devices as $device): ?>
                <div class="device-card glass-card rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-500 to-gray-600 rounded-lg flex items-center justify-center text-white mr-3">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($device['brand'] . ' ' . $device['model']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($device['device_type']); ?></p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold status-<?php echo $device['status']; ?>">
                            <?php
                            $status_labels = [
                                'pending' => 'En attente',
                                'in_progress' => 'En cours',
                                'completed' => 'Terminé',
                                'cancelled' => 'Annulé'
                            ];
                            echo $status_labels[$device['status']] ?? $device['status'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-user mr-2"></i>
                            <?php echo htmlspecialchars($device['client_name'] ?? 'Client inconnu'); ?>
                        </div>
                        
                        <?php if ($device['serial_number']): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-hashtag mr-2"></i>
                            <?php echo htmlspecialchars($device['serial_number']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-calendar mr-2"></i>
                            <?php echo date('d/m/Y', strtotime($device['created_at'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($device['problem_description']): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-700">
                            <strong>Problème:</strong> <?php echo htmlspecialchars(substr($device['problem_description'], 0, 100)) . (strlen($device['problem_description']) > 100 ? '...' : ''); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            <?php if ($device['repair_cost'] > 0): ?>
                            <strong>Coût:</strong> <?php echo formatPrice($device['repair_cost']); ?>
                            <?php elseif ($device['estimated_cost'] > 0): ?>
                            <strong>Estimation:</strong> <?php echo formatPrice($device['estimated_cost']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="openStatusModal(<?php echo $device['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-edit mr-1"></i>Statut
                            </button>
                            <a href="device_details.php?id=<?php echo $device['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-eye mr-1"></i>Détails
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($devices)): ?>
            <div class="glass-card rounded-xl p-12 text-center">
                <i class="fas fa-mobile-alt text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucun appareil trouvé</h3>
                <p class="text-gray-500 mb-6">Commencez par ajouter votre premier appareil</p>
                <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un appareil
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Device Modal -->
    <div id="addDeviceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="glass-card rounded-xl p-6 m-4 max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Ajouter un appareil</h2>
                <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Client *</label>
                        <select name="client_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Sélectionner un client</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Type d'appareil *</label>
                        <select name="device_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Sélectionner le type</option>
                            <option value="Smartphone">Smartphone</option>
                            <option value="Tablette">Tablette</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Console">Console</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Marque *</label>
                        <input type="text" name="brand" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Apple, Samsung, etc.">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Modèle *</label>
                        <input type="text" name="model" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="iPhone 14, Galaxy S23, etc.">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Numéro de série</label>
                        <input type="text" name="serial_number" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Coût estimé</label>
                        <input type="number" name="estimated_cost" step="0.01" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description du problème</label>
                        <textarea name="problem_description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Décrivez le problème rencontré..."></textarea>
                    </div>
                </div>
                
                <div class="flex space-x-4 mt-6">
                    <button type="button" onclick="closeAddModal()" 
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="glass-card rounded-xl p-6 m-4 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Changer le statut</h2>
                <button onclick="closeStatusModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="device_id" id="statusDeviceId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nouveau statut *</label>
                        <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="pending">En attente</option>
                            <option value="in_progress">En cours</option>
                            <option value="completed">Terminé</option>
                            <option value="cancelled">Annulé</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Notes sur le statut..."></textarea>
                    </div>
                </div>
                
                <div class="flex space-x-4 mt-6">
                    <button type="button" onclick="closeStatusModal()" 
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addDeviceModal').classList.remove('hidden');
            document.getElementById('addDeviceModal').classList.add('flex');
        }
        
        function closeAddModal() {
            document.getElementById('addDeviceModal').classList.add('hidden');
            document.getElementById('addDeviceModal').classList.remove('flex');
        }
        
        function openStatusModal(deviceId) {
            document.getElementById('statusDeviceId').value = deviceId;
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusModal').classList.add('flex');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusModal').classList.remove('flex');
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchValue = this.value.trim();
                if (searchValue) {
                    window.location.href = 'devices_modern.php?search=' + encodeURIComponent(searchValue);
                } else {
                    window.location.href = 'devices_modern.php';
                }
            }
        });
        
        // Animation on load
        anime({
            targets: '.device-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(100),
            easing: 'easeOutQuart'
        });
    </script>
</body>
</html>