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

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'phone' => sanitizeInput($_POST['phone']),
                'email' => sanitizeInput($_POST['email']),
                'address' => sanitizeInput($_POST['address']),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $client_id = $clientModel->insert($data);
            
            // Log activity
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'create',
                'client',
                $client_id,
                'Client créé: ' . $data['name']
            );
            
            $message = 'Client ajouté avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'update') {
            $client_id = intval($_POST['client_id']);
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'phone' => sanitizeInput($_POST['phone']),
                'email' => sanitizeInput($_POST['email']),
                'address' => sanitizeInput($_POST['address'])
            ];
            
            $clientModel->update($client_id, $data);
            
            // Log activity
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'client',
                $client_id,
                'Client modifié: ' . $data['name']
            );
            
            $message = 'Client modifié avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'delete') {
            $client_id = intval($_POST['client_id']);
            $client = $clientModel->find($client_id);
            
            $clientModel->delete($client_id);
            
            // Log activity
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'delete',
                'client',
                $client_id,
                'Client supprimé: ' . ($client['name'] ?? 'Inconnu')
            );
            
            $message = 'Client supprimé avec succès!';
            $message_type = 'success';
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get clients with search
$search = $_GET['search'] ?? '';
if ($search) {
    $clients = $clientModel->search($search);
} else {
    $clients = $clientModel->findAll('name ASC');
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - R.E.Mobiles</title>
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
        .client-card {
            transition: all 0.3s ease;
        }
        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
                    <h1 class="text-3xl font-bold text-white mb-2">Gestion des Clients</h1>
                    <p class="text-white/80">Gérez votre base de clients</p>
                </div>
                <button onclick="openAddModal()" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nouveau Client
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

            <!-- Search Bar -->
            <div class="mb-6">
                <div class="glass-card rounded-lg p-4">
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Rechercher un client par nom, téléphone ou email..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            Rechercher
                        </button>
                        <?php if ($search): ?>
                        <a href="clients_modern.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Effacer
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Clients Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($clients as $client): ?>
                <div class="client-card glass-card rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                <?php echo strtoupper(substr($client['name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($client['name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($client['phone']); ?></p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="openEditModal(<?php echo $client['id']; ?>)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars(addslashes($client['name'])); ?>')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($client['email']): ?>
                    <div class="flex items-center text-sm text-gray-600 mb-2">
                        <i class="fas fa-envelope mr-2"></i>
                        <?php echo htmlspecialchars($client['email']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($client['address']): ?>
                    <div class="flex items-start text-sm text-gray-600 mb-4">
                        <i class="fas fa-map-marker-alt mr-2 mt-1"></i>
                        <span><?php echo htmlspecialchars($client['address']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <span class="text-xs text-gray-500">
                            Créé le <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                        </span>
                        <a href="client_details.php?id=<?php echo $client['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold">
                            Voir détails <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div> 
                <?php endforeach; ?>
            </div>

            <?php if (empty($clients)): ?>
            <div class="glass-card rounded-xl p-12 text-center">
                <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucun client trouvé</h3>
                <p class="text-gray-500 mb-6">Commencez par ajouter votre premier client</p>
                <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un client
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="clientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="glass-card rounded-xl p-6 m-4 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-800">Ajouter un client</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="clientForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="client_id" id="clientId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom *</label>
                        <input type="text" name="name" id="clientName" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone *</label>
                        <input type="tel" name="phone" id="clientPhone" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="clientEmail"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                        <textarea name="address" id="clientAddress" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
                
                <div class="flex space-x-4 mt-6">
                    <button type="button" onclick="closeModal()" 
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

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un client';
            document.getElementById('formAction').value = 'add';
            document.getElementById('clientForm').reset();
            document.getElementById('clientModal').classList.remove('hidden');
            document.getElementById('clientModal').classList.add('flex');
        }
        
        function openEditModal(clientId) {
            // In a real implementation, you would fetch client data via AJAX
            document.getElementById('modalTitle').textContent = 'Modifier le client';
            document.getElementById('formAction').value = 'update';
            document.getElementById('clientId').value = clientId;
            document.getElementById('clientModal').classList.remove('hidden');
            document.getElementById('clientModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('clientModal').classList.add('hidden');
            document.getElementById('clientModal').classList.remove('flex');
        }
        
        function confirmDelete(clientId, clientName) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${clientName}" ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" value="${clientId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Animation on load
        anime({
            targets: '.client-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(100),
            easing: 'easeOutQuart'
        });
    </script>
</body>
</html>