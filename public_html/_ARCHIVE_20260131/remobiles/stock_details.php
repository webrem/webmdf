<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/includes/init.php';

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: login_modern.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$stockModel = ModelFactory::stockArticle();
$historiqueModel = ModelFactory::historique();

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$article_id) {
    header('Location: stock_modern.php');
    exit;
}

// Get article details
$article = $stockModel->find($article_id);
if (!$article) {
    header('Location: stock_modern.php');
    exit;
}

// Get article's history
$history = $historiqueModel->getByEntity('stock_article', $article_id);

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_info') {
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'reference' => sanitizeInput($_POST['reference']),
                'category' => sanitizeInput($_POST['category']),
                'description' => sanitizeInput($_POST['description']),
                'unit_price' => floatval($_POST['unit_price']),
                'min_stock' => intval($_POST['min_stock'])
            ];
            
            $stockModel->update($article_id, $data);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'stock_article',
                $article_id,
                'Article modifié: ' . $data['name']
            );
            
            // Refresh article data
            $article = $stockModel->find($article_id);
            
            $message = 'Article modifié avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'update_quantity') {
            $quantity_change = intval($_POST['quantity_change']);
            $reason = sanitizeInput($_POST['reason'] ?? '');
            
            $stockModel->updateQuantity($article_id, $quantity_change);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'stock_article',
                $article_id,
                'Stock modifié: ' . ($quantity_change > 0 ? '+' : '') . $quantity_change . ($reason ? ' - ' . $reason : '')
            );
            
            // Refresh article data
            $article = $stockModel->find($article_id);
            
            $message = 'Quantité mise à jour avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'delete') {
            $stockModel->delete($article_id);
            
            // Log activity
            $historiqueModel->addRecord(
                $current_user['id'],
                'delete',
                'stock_article',
                $article_id,
                'Article supprimé: ' . $article['name']
            );
            
            header('Location: stock_modern.php');
            exit;
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage(); 
        $message_type = 'error';
    }
}

// Calculate stock value
$stock_value = $article['quantity'] * $article['unit_price'];

// Determine stock level
$stock_level = 'good';
if ($article['quantity'] <= $article['min_stock']) {
    $stock_level = 'low';
} elseif ($article['quantity'] <= ($article['min_stock'] * 2)) {
    $stock_level = 'medium';
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['name']); ?> - Détails Stock</title>
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
        .stock-low { background-color: #FEE2E2; color: #991B1B; }
        .stock-medium { background-color: #FEF3C7; color: #92400E; }
        .stock-good { background-color: #D1FAE5; color: #065F46; }
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
                    <a href="stock_modern.php" class="nav-item active flex items-center p-3 rounded-lg text-white">
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
                    <h1 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($article['name']); ?></h1>
                    <p class="text-white/80">Référence: <?php echo htmlspecialchars($article['reference']); ?></p>
                </div>
                <div class="flex space-x-4">
                    <a href="stock_modern.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <button onclick="confirmDeleteArticle()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
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

            <!-- Article Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Article Details -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-box mr-2"></i>
                            Informations de l'Article
                        </h3>
                        
                        <form method="POST" id="articleInfoForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update_info">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nom *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($article['name']); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Référence *</label>
                                    <input type="text" name="reference" value="<?php echo htmlspecialchars($article['reference']); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie *</label>
                                    <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="Écran" <?php echo $article['category'] === 'Écran' ? 'selected' : ''; ?>>Écran</option>
                                        <option value="Batterie" <?php echo $article['category'] === 'Batterie' ? 'selected' : ''; ?>>Batterie</option>
                                        <option value="Chargeur" <?php echo $article['category'] === 'Chargeur' ? 'selected' : ''; ?>>Chargeur</option>
                                        <option value="Câble" <?php echo $article['category'] === 'Câble' ? 'selected' : ''; ?>>Câble</option>
                                        <option value="Coque" <?php echo $article['category'] === 'Coque' ? 'selected' : ''; ?>>Coque</option>
                                        <option value="Protection" <?php echo $article['category'] === 'Protection' ? 'selected' : ''; ?>>Protection</option>
                                        <option value="Composant" <?php echo $article['category'] === 'Composant' ? 'selected' : ''; ?>>Composant</option>
                                        <option value="Outil" <?php echo $article['category'] === 'Outil' ? 'selected' : ''; ?>>Outil</option>
                                        <option value="Autre" <?php echo $article['category'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Prix unitaire *</label>
                                    <input type="number" name="unit_price" step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($article['unit_price']); ?>" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                <textarea name="description" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($article['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Stock minimum</label>
                                <input type="number" name="min_stock" min="0" 
                                       value="<?php echo htmlspecialchars($article['min_stock']); ?>" 
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

                <!-- Stock Management -->
                <div class="space-y-6">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-warehouse mr-2"></i>
                            Gestion du Stock
                        </h3>
                        
                        <div class="text-center mb-6">
                            <div class="text-4xl font-bold text-gray-800 mb-2"><?php echo $article['quantity']; ?></div>
                            <div class="text-lg text-gray-600">Quantité disponible</div>
                            <div class="mt-2">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold stock-<?php echo $stock_level; ?>">
                                    <?php
                                    $level_labels = [
                                        'low' => 'Stock faible',
                                        'medium' => 'Stock moyen',
                                        'good' => 'Stock suffisant'
                                    ];
                                    echo $level_labels[$stock_level];
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <form method="POST" id="quantityForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update_quantity">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Changement de quantité</label>
                                    <input type="number" name="quantity_change" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                           placeholder="Ex: +5 ou -3">
                                    <p class="text-xs text-gray-500 mt-1">Entrez un nombre positif pour ajouter, négatif pour retirer</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Raison (optionnel)</label>
                                    <input type="text" name="reason" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                           placeholder="Raison du changement...">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" 
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-save mr-2"></i>Mettre à jour la quantité
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
                                <span class="text-gray-600">Stock minimum:</span>
                                <span class="font-semibold text-gray-800;"><?php echo $article['min_stock']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Valeur totale:</span>
                                <span class="font-semibold text-green-600;"><?php echo formatPrice($stock_value); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Prix unitaire:</span>
                                <span class="font-semibold text-gray-800;"><?php echo formatPrice($article['unit_price']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Créé le:</span>
                                <span class="font-semibold text-gray-800;"><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Actions Rapides
                        </h3>
                        <div class="space-y-3">
                            <button onclick="addStock(10)" 
                                    class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-2 px-4 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-plus mr-2"></i>Ajouter 10 unités
                            </button>
                            <button onclick="removeStock(5)" 
                                    class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-2 px-4 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-minus mr-2"></i>Retirer 5 unités
                            </button>
                            <button onclick="setStock()" 
                                    class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg font-semibold transition-colors">
                                <i class="fas fa-edit mr-2"></i>Définir quantité
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-history mr-2"></i>
                    Historique de l'Article
                </h3>
                
                <?php if (empty($history)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-600 mb-2">Aucune activité</h4>
                    <p class="text-gray-500">Aucune action n'a été enregistrée pour cet article</p>
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
                    Êtes-vous sûr de vouloir supprimer l'article <strong><?php echo htmlspecialchars($article['name']); ?></strong> ?
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
        function confirmDeleteArticle() {
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        function addStock(quantity) {
            if (confirm(`Ajouter ${quantity} unités au stock ?`)) {
                document.querySelector('input[name="quantity_change"]').value = quantity;
                document.querySelector('input[name="reason"]').value = 'Ajout rapide';
                document.getElementById('quantityForm').submit();
            }
        }

        function removeStock(quantity) {
            if (confirm(`Retirer ${quantity} unités du stock ?`)) {
                document.querySelector('input[name="quantity_change"]').value = -quantity;
                document.querySelector('input[name="reason"]').value = 'Retrait rapide';
                document.getElementById('quantityForm').submit();
            }
        }

        function setStock() {
            const newQuantity = prompt('Entrez la nouvelle quantité:');
            if (newQuantity !== null && !isNaN(newQuantity)) {
                const currentQuantity = <?php echo $article['quantity']; ?>;
                const change = parseInt(newQuantity) - currentQuantity;
                document.querySelector('input[name="quantity_change"]').value = change;
                document.querySelector('input[name="reason"]').value = 'Réinitialisation';
                document.getElementById('quantityForm').submit();
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