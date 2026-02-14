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
                'reference' => sanitizeInput($_POST['reference']),
                'category' => sanitizeInput($_POST['category']),
                'description' => sanitizeInput($_POST['description']),
                'quantity' => intval($_POST['quantity']),
                'unit_price' => floatval($_POST['unit_price']),
                'min_stock' => intval($_POST['min_stock']),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $article_id = $stockModel->insert($data);
            
            // Log activity
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'create',
                'stock_article',
                $article_id,
                'Article créé: ' . $data['name']
            );
            
            $message = 'Article ajouté avec succès!';
            $message_type = 'success';
        }
        
        if ($action === 'update_quantity') {
            $article_id = intval($_POST['article_id']);
            $quantity_change = intval($_POST['quantity_change']);
            
            $stockModel->updateQuantity($article_id, $quantity_change);
            
            // Log activity
            $article = $stockModel->find($article_id);
            $historiqueModel = ModelFactory::historique();
            $historiqueModel->addRecord(
                $current_user['id'],
                'update',
                'stock_article',
                $article_id,
                'Stock modifié: ' . $article['name'] . ' (' . ($quantity_change > 0 ? '+' : '') . $quantity_change . ')'
            );
            
            $message = 'Quantité mise à jour avec succès!';
            $message_type = 'success';
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get stock with filters
$category_filter = $_GET['category'] ?? '';
$low_stock_filter = isset($_GET['low_stock']);
$search = $_GET['search'] ?? '';

if ($low_stock_filter) {
    $articles = $stockModel->findLowStock();
} elseif ($category_filter) {
    $articles = $stockModel->findByCategory($category_filter);
} elseif ($search) {
    $articles = $stockModel->search($search);
} else {
    $articles = $stockModel->findAll('name ASC');
}

// Get categories
$categories = ['Écran', 'Batterie', 'Chargeur', 'Câble', 'Coque', 'Protection', 'Composant', 'Outil', 'Autre'];

// Get statistics
$total_items = $stockModel->count();
$low_stock_count = count($stockModel->findLowStock());
$total_value = $stockModel->getStockValue();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - R.E.Mobiles</title>
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
        .stock-card {
            transition: all 0.3s ease;
        }
        .stock-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
                    <h1 class="text-3xl font-bold text-white mb-2">Gestion du Stock</h1>
                    <p class="text-white/80">Gérez votre inventaire de pièces</p>
                </div>
                <button onclick="openAddModal()" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvel Article
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Articles</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_items); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-boxes text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Stock Faible</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($low_stock_count); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Valeur Totale</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo formatPrice($total_value); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-euro-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
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

            <!-- Filters -->
            <div class="mb-6">
                <div class="glass-card rounded-lg p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                name="search" 
                                id="searchInput"
                                placeholder="Rechercher un article..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                value="<?php echo htmlspecialchars($search); ?>"
                            >
                        </div>
                        <div class="flex space-x-2">
                            <a href="stock_modern.php" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo !$category_filter && !$low_stock_filter ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                Tous
                            </a>
                            <a href="stock_modern.php?low_stock=1" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $low_stock_filter ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200'; ?>">
                                Stock Faible
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($articles as $article): 
                    $stock_level = 'good';
                    if ($article['quantity'] <= $article['min_stock']) {
                        $stock_level = 'low';
                    } elseif ($article['quantity'] <= ($article['min_stock'] * 2)) {
                        $stock_level = 'medium';
                    }
                ?>
                <div class="stock-card glass-card rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($article['name']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($article['reference']); ?></p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold stock-<?php echo $stock_level; ?>">
                            <?php echo $article['quantity']; ?> pcs
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-tag mr-2"></i>
                            <?php echo htmlspecialchars($article['category']); ?>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-euro-sign mr-2"></i>
                            <?php echo formatPrice($article['unit_price']); ?> / unité
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-chart-line mr-2"></i>
                            <?php echo formatPrice($article['quantity'] * $article['unit_price']); ?>
                        </div>
                    </div>
                    
                    <?php if ($article['description']): ?>
                    <p class="text-sm text-gray-700 mb-4">
                        <?php echo htmlspecialchars(substr($article['description'], 0, 60)) . (strlen($article['description']) > 60 ? '...' : ''); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <button onclick="openQuantityModal(<?php echo $article['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-edit mr-1"></i>Quantité
                        </button>
                        <a href="stock_details.php?id=<?php echo $article['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                            <i class="fas fa-eye mr-1"></i>Détails
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($articles)): ?>
            <div class="glass-card rounded-xl p-12 text-center">
                <i class="fas fa-boxes text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucun article trouvé</h3>
                <p class="text-gray-500 mb-6">Commencez par ajouter votre premier article</p>
                <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un article
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Article Modal -->
    <div id="addArticleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="glass-card rounded-xl p-6 m-4 max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Ajouter un article</h2>
                <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Référence *</label>
                        <input type="text" name="reference" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie *</label>
                        <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Quantité *</label>
                        <input type="number" name="quantity" required min="0" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Prix unitaire *</label>
                        <input type="number" name="unit_price" required min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Stock minimum</label>
                        <input type="number" name="min_stock" min="0" value="5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Description de l'article..."></textarea>
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

    <!-- Quantity Update Modal -->
    <div id="quantityModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="glass-card rounded-xl p-6 m-4 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Modifier la quantité</h2>
                <button onclick="closeQuantityModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_quantity">
                <input type="hidden" name="article_id" id="quantityArticleId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Changement de quantité</label>
                        <input type="number" name="quantity_change" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Ex: +5 ou -3">
                        <p class="text-xs text-gray-500 mt-1">Entrez un nombre positif pour ajouter, négatif pour retirer</p>
                    </div>
                </div>
                
                <div class="flex space-x-4 mt-6">
                    <button type="button" onclick="closeQuantityModal()" 
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
            document.getElementById('addArticleModal').classList.remove('hidden');
            document.getElementById('addArticleModal').classList.add('flex');
        }
        
        function closeAddModal() {
            document.getElementById('addArticleModal').classList.add('hidden');
            document.getElementById('addArticleModal').classList.remove('flex');
        }
        
        function openQuantityModal(articleId) {
            document.getElementById('quantityArticleId').value = articleId;
            document.getElementById('quantityModal').classList.remove('hidden');
            document.getElementById('quantityModal').classList.add('flex');
        }
        
        function closeQuantityModal() {
            document.getElementById('quantityModal').classList.add('hidden');
            document.getElementById('quantityModal').classList.remove('flex');
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchValue = this.value.trim();
                if (searchValue) {
                    window.location.href = 'stock_modern.php?search=' + encodeURIComponent(searchValue);
                } else {
                    window.location.href = 'stock_modern.php';
                }
            }
        });
        
        // Animation on load
        anime({
            targets: '.stock-card',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: anime.stagger(100),
            easing: 'easeOutQuart'
        });
    </script>
</body>
</html>