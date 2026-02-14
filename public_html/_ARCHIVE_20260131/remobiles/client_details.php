<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/includes/init.php';

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: login_modern.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$clientModel = ModelFactory::client();
$deviceModel = ModelFactory::device();
$historiqueModel = ModelFactory::historique();

// Get client ID from URL
$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$client_id) {
    header('Location: clients_modern.php');
    exit;
}

// Get client details
$client = $clientModel->find($client_id);
if (!$client) {
    header('Location: clients_modern.php');
    exit;
}

// Get client's devices
$devices = $deviceModel->findByClient($client_id);

// Get client's history
$history = $historiqueModel->getByEntity('client', $client_id);

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update') {
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'phone' => sanitizeInput($_POST['phone']),
                'email' => sanitizeInput($_POST['email']),
                'address' => sanitizeInput($_POST['address'])
            ];
            
            $clientModel->update($client_id, $data);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'client',
                $client_id,
                'Client modifié: ' . $data['name']
            );
            
            // Refresh client data
            $client = $clientModel->find($client_id);
            
            $message = 'Client modifié avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'delete') {
            // Check if client has devices
            if (!empty($devices)) {
                throw new Exception('Impossible de supprimer ce client car il a des appareils associés');
            }
            
            $clientModel->delete($client_id);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'delete',
                'client',
                $client_id,
                'Client supprimé: ' . $client['name']
            );
            
            header('Location: clients_modern.php');
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
    <title><?php echo htmlspecialchars($client['name']); ?> - Détails Client</title>
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
                    <a href="clients_modern.php" class="nav-item active flex items-center p-3 rounded-lg text-white">
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
                    <h1 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($client['name']); ?></h1>
                    <p class="text-white/80">Détails du client</p>
                </div>
                <div class="flex space-x-4">
                    <a href="clients_modern.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <button onclick="toggleEditMode()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-edit mr-2"></i>Modifier
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

            <!-- Client Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Client Details -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-user mr-2"></i>
                            Informations du Client
                        </h3>
                        
                        <form method="POST" id="clientForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nom *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 client-input" 
                                           readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone *</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 client-input" 
                                           readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 client-input" 
                                           readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                                    <textarea name="address" rows="3" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 client-input" 
                                              readonly><?php echo htmlspecialchars($client['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div id="editButtons" class="mt-6 hidden">
                                <div class="flex space-x-4">
                                    <button type="button" onclick="cancelEdit()" 
                                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                                        <i class="fas fa-times mr-2"></i>Annuler
                                    </button>
                                    <button type="submit" 
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                                        <i class="fas fa-save mr-2"></i>Enregistrer
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="space-y-6">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Statistiques
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Appareils:</span>
                                <span class="font-semibold text-indigo-600"><?php echo count($devices); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">En attente:</span>
                                <span class="font-semibold text-yellow-600;">
                                    <?php echo count(array_filter($devices, fn($d) => $d['status'] === 'pending')); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">En cours:</span>
                                <span class="font-semibold text-blue-600;">
                                    <?php echo count(array_filter($devices, fn($d) => $d['status'] === 'in_progress')); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Terminé:</span>
                                <span class="font-semibold text-green-600;">
                                    <?php echo count(array_filter($devices, fn($d) => $d['status'] === 'completed')); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Client depuis:</span>
                                <span class="font-semibold text-gray-800;">
                                    <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Actions
                        </h3>
                        <div class="space-y-3">
                            <a href="add_device.php?client_id=<?php echo $client_id; ?>" 
                               class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-2 px-4 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-plus mr-2"></i>Nouvel Appareil
                            </a>
                            <button onclick="confirmDeleteClient()" 
                                    class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-2 px-4 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-trash mr-2"></i>Supprimer Client
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <?php if (!empty($devices)): ?>
                            ⚠️ Impossible de supprimer: ce client a des appareils associés
                            <?php else: ?>
                            ✓ Ce client peut être supprimé (aucun appareil associé)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Devices Section -->
            <div class="mb-8">
                <div class="glass-card rounded-xl p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-mobile-alt mr-2"></i>
                            Appareils (<?php echo count($devices); ?>)
                        </h3>
                        <a href="add_device.php?client_id=<?php echo $client_id; ?>" 
                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-plus mr-2"></i>Ajouter
                        </a>
                    </div>
                    
                    <?php if (empty($devices)): ?>
                    <div class="text-center py-8"> 
                        <i class="fas fa-mobile-alt text-4xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-semibold text-gray-600 mb-2">Aucun appareil</h4>
                        <p class="text-gray-500">Ajoutez le premier appareil pour ce client</p>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($devices as $device): ?>
                        <div class="device-card bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($device['brand'] . ' ' . $device['model']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($device['device_type']); ?></p>
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
                            
                            <div class="space-y-2 mb-3">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-calendar mr-2"></i>
                                    <?php echo date('d/m/Y', strtotime($device['created_at'])); ?>
                                </div>
                                <?php if ($device['repair_cost'] > 0): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-euro-sign mr-2"></i>
                                    <?php echo formatPrice($device['repair_cost']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between">
                                <a href="device_details.php?id=<?php echo $device['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-eye mr-1"></i>Voir
                                </a>
                                <span class="text-xs text-gray-500">
                                    #<?php echo $device['id']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- History Section -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-history mr-2"></i>
                    Historique du Client
                </h3>
                
                <?php if (empty($history)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-600 mb-2">Aucune activité</h4>
                    <p class="text-gray-500">Aucune action n'a été enregistrée pour ce client</p>
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
                    Êtes-vous sûr de vouloir supprimer le client <strong><?php echo htmlspecialchars($client['name']); ?></strong> ?
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
        let isEditMode = false;

        function toggleEditMode() {
            isEditMode = !isEditMode;
            const inputs = document.querySelectorAll('.client-input');
            const editButtons = document.getElementById('editButtons');
            const editButton = event.target;
            
            if (isEditMode) {
                inputs.forEach(input => {
                    input.removeAttribute('readonly');
                    input.classList.add('bg-white');
                    input.classList.remove('bg-gray-50');
                });
                editButtons.classList.remove('hidden');
                editButton.innerHTML = '<i class="fas fa-times mr-2"></i>Annuler';
                editButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                editButton.classList.add('bg-red-600', 'hover:bg-red-700');
            } else {
                inputs.forEach(input => {
                    input.setAttribute('readonly', 'readonly');
                    input.classList.remove('bg-white');
                    input.classList.add('bg-gray-50');
                });
                editButtons.classList.add('hidden');
                editButton.innerHTML = '<i class="fas fa-edit mr-2"></i>Modifier';
                editButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                editButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
            }
        }

        function cancelEdit() {
            toggleEditMode();
            // Reset form values
            location.reload();
        }

        function confirmDeleteClient() {
            <?php if (empty($devices)): ?>
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
            <?php else: ?>
            alert('Impossible de supprimer ce client car il a des appareils associés.');
            <?php endif; ?>
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

        anime({
            targets: '.device-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(50, {start: 400}),
            easing: 'easeOutQuart'
        });
    </script>
</body>
</html>