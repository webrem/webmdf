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
$stockModel = ModelFactory::stockArticle();
$historiqueModel = ModelFactory::historique();

// Get device ID from URL
$device_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$device_id) {
    header('Location: devices_modern.php');
    exit;
}

// Get device details with client info
$sql = "SELECT d.*, c.name as client_name, c.phone as client_phone 
        FROM devices d 
        LEFT JOIN clients c ON d.client_id = c.id 
        WHERE d.id = :id LIMIT 1";
$device = $database->fetch($sql, [':id' => $device_id]);

if (!$device) {
    header('Location: devices_modern.php');
    exit;
}

// Get device's history
$history = $historiqueModel->getByEntity('device', $device_id);

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $new_status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $repair_cost = floatval($_POST['repair_cost'] ?? 0);
            
            $update_data = [
                'status' => $new_status,
                'notes' => $notes
            ];
            
            if ($repair_cost > 0) {
                $update_data['repair_cost'] = $repair_cost;
            }
            
            $deviceModel->update($device_id, $update_data);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'device',
                $device_id,
                'Statut changé: ' . $new_status . ($notes ? ' - ' . $notes : '')
            );
            
            // Refresh device data
            $device = $database->fetch($sql, [':id' => $device_id]);
            
            $message = 'Statut mis à jour avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'update_info') {
            $update_data = [
                'brand' => sanitizeInput($_POST['brand']),
                'model' => sanitizeInput($_POST['model']),
                'serial_number' => sanitizeInput($_POST['serial_number']),
                'problem_description' => sanitizeInput($_POST['problem_description']),
                'estimated_cost' => floatval($_POST['estimated_cost'])
            ];
            
            $deviceModel->update($device_id, $update_data);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'device',
                $device_id,
                'Informations modifiées'
            );
            
            // Refresh device data
            $device = $database->fetch($sql, [':id' => $device_id]);
            
            $message = 'Informations mises à jour avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'delete') {
            $deviceModel->delete($device_id);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'delete',
                'device',
                $device_id,
                'Appareil supprimé: ' . $device['brand'] . ' ' . $device['model']
            );
            
            header('Location: devices_modern.php');
            exit;
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($device['brand'] . ' ' . $device['model']); ?> - Détails Appareil</title>
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
                    <h1 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($device['brand'] . ' ' . $device['model']); ?></h1>
                    <p class="text-white/80">
                        <i class="fas fa-user mr-2"></i>
                        Client: <?php echo htmlspecialchars($device['client_name'] ?? 'Inconnu'); ?>
                    </p>
                </div>
                <div class="flex space-x-4">
                    <a href="devices_modern.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <button onclick="confirmDeleteDevice()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </button>
                </div>
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

            <!-- Device Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Device Details -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-mobile-alt mr-2"></i>
                            Informations de l'Appareil
                        </h3>
                        
                        <form method="POST" id="deviceInfoForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update_info">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Marque *</label>
                                    <input type="text" name="brand" value="<?php echo htmlspecialchars($device['brand']); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Modèle *</label>
                                    <input type="text" name="model" value="<?php echo htmlspecialchars($device['model']); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                                    <input type="text" name="device_type" value="<?php echo htmlspecialchars($device['device_type']); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Numéro de série</label>
                                    <input type="text" name="serial_number" value="<?php echo htmlspecialchars($device['serial_number'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Description du problème</label>
                                <textarea name="problem_description" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($device['problem_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Coût estimé</label>
                                <input type="number" name="estimated_cost" step="0.01" min="0" 
                                       value="<?php echo htmlspecialchars($device['estimated_cost'] ?? ''); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-save mr-2"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Status and Actions -->
                <div class="space-y-6">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-info-circle mr-2"></i>
                            Statut et Coût
                        </h3>
                        
                        <form method="POST" id="statusForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update_status">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Statut *</label>
                                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="pending" <?php echo $device['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                        <option value="in_progress" <?php echo $device['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="completed" <?php echo $device['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                        <option value="cancelled" <?php echo $device['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Coût de réparation</label>
                                    <input type="number" name="repair_cost" step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($device['repair_cost'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                                    <textarea name="notes" rows="3" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                              placeholder="Notes sur l'état ou la réparation..."><?php echo htmlspecialchars($device['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" 
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-save mr-2"></i>Mettre à jour le statut
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Statistiques
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Statut actuel:</span>
                                <span class="status-<?php echo $device['status']; ?> px-2 py-1 rounded-full text-xs font-semibold">
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
                            <div class="flex justify-between">
                                <span class="text-gray-600">Créé le:</span>
                                <span class="font-semibold text-gray-800;">
                                    <?php echo date('d/m/Y', strtotime($device['created_at'])); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Coût estimé:</span>
                                <span class="font-semibold text-gray-800;">
                                    <?php echo $device['estimated_cost'] > 0 ? formatPrice($device['estimated_cost']) : 'Non défini'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Coût réel:</span>
                                <span class="font-semibold text-gray-800;">
                                    <?php echo $device['repair_cost'] > 0 ? formatPrice($device['repair_cost']) : 'Non défini'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-user mr-2"></i>
                            Client
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nom:</span>
                                <span class="font-semibold text-gray-800;">
                                    <?php echo htmlspecialchars($device['client_name'] ?? 'Inconnu'); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Téléphone:</span>
                                <span class="font-semibold text-gray-800;">
                                    <?php echo htmlspecialchars($device['client_phone'] ?? 'Non défini'); ?>
                                </span>
                            </div>
                            <div class="mt-4">
                                <a href="client_details.php?id=<?php echo $device['client_id']; ?>" 
                                   class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-eye mr-2"></i>Voir le client
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-history mr-2"></i>
                    Historique de l'Appareil
                </h3>
                
                <?php if (empty($history)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-600 mb-2">Aucune activité</h4>
                    <p class="text-gray-500">Aucune action n'a été enregistrée pour cet appareil</p>
                </div>
                <?php else: ?>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php foreach ($history as $record): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold mr-3
                                      <?php echo 'action-' . $record['action']; ?>">
                                    <?php
                                    $action_labels = [
                                        'create' => 'Création',
                                        'update' => 'Modification',
                                        'delete' => 'Suppression'
                                    ];
                                    echo $action_labels[$record['action']] ?? $record['action'];
                                    ?>
                                </span>
                                <span class="text-sm text-gray-600">
                                    Par <?php echo htmlspecialchars($record['username'] ?? 'Système'); ?>
                                </span>
                            </div>
                            <span class="text-xs text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                            </span>
                        </div>
                        <?php if ($record['details']): ?>
                        <p class="text-sm text-gray-700 mt-2">
                            <?php echo htmlspecialchars($record['details']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="glass-card rounded-xl p-6 m-4 max-w-md w-full">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirmer la suppression</h3>
                <p class="text-gray-600 mb-6">
                    Êtes-vous sûr de vouloir supprimer l'appareil <strong><?php echo htmlspecialchars($device['brand'] . ' ' . $device['model']); ?></strong> ?
                    <br><br>
                    <span class="text-red-600">Cette action est irréversible.</span>
                </p>
                
                <div class="flex space-x-4">
                    <button onclick="closeDeleteModal()" 
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                        Annuler
                    </button>
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" 
                                class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDeleteDevice() {
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
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