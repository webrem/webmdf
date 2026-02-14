<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Header commun pour toutes les pages
 */

// Vérifier si les variables nécessaires sont définies
if (!isset($pageTitle)) {
    $pageTitle = 'R.E.Mobiles';
}

if (!isset($pageDescription)) {
    $pageDescription = 'Système de gestion pour réparations mobiles';
}

if (!isset($currentPage)) {
    $currentPage = '';
}

// Générer le token CSRF
try {
    $csrfToken = $auth->generateCSRFToken();
} catch (Exception $e) {
    $csrfToken = '';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="author" content="R.E.Mobiles">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Anime.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    
    <!-- Chart.js -->
    <?php if (isset($needsCharts) && $needsCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <style>
        :root {
            --primary: #0dcaf0;
            --primary-dark: #0b5ed7;
            --secondary: #6c757d;
            --success: #198754;
            --danger: #dc3545;
            --dark: #0a0a0a;
            --darker: #000000;
            --light: #ffffff;
            --glass: rgba(255, 255, 255, 0.05);
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a1a 50%, var(--darker) 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Glass morphism */
        .glass {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(13, 202, 240, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .glass:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(13, 202, 240, 0.2);
            border-color: rgba(13, 202, 240, 0.4);
        }
        
        /* Navigation */
        .navbar {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(13, 202, 240, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin: 1rem;
            padding: 1rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link:hover {
            color: var(--primary);
            background: rgba(13, 202, 240, 0.1);
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            color: var(--primary);
            background: rgba(13, 202, 240, 0.2);
            font-weight: 600;
        }
        
        /* Boutons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(13, 202, 240, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 202, 240, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }
        
        /* Inputs modernes */
        .input-modern {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            padding: 12px 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .input-modern:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 202, 240, 0.1);
            outline: none;
        }
        
        .input-modern::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Scrollbar personnalisée */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        /* Badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .badge-user {
            background: linear-gradient(135deg, #0dcaf0, #0b5ed7);
            color: white;
            box-shadow: 0 4px 15px rgba(13, 202, 240, 0.3);
        }
        
        /* Footer */
        .footer {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(13, 202, 240, 0.2);
            border-radius: 16px;
            margin: 1rem;
            padding: 1rem;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                margin: 0.5rem;
                padding: 0.75rem;
            }
            
            .nav-link {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="bi bi-cpu-fill text-cyan-400 text-2xl"></i>
                <h1 class="text-white font-bold text-xl">R.E.Mobiles</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <?php if ($isLoggedIn): ?>
                    <span class="badge <?= $isAdmin ? 'badge-admin' : 'badge-user' ?>">
                        👤 <?= htmlspecialchars($currentUser['username']) ?> - <?= ucfirst($currentUser['role']) ?>
                    </span>
                <?php endif; ?>
                
                <div class="flex space-x-2">
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-house-fill"></i>
                            <span class="hidden sm:inline">Dashboard</span>
                        </a>
                        <a href="logout.php" class="nav-link text-red-400 hover:text-red-300">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="hidden sm:inline">Déconnexion</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">
                            <i class="bi bi-unlock-fill"></i>
                            <span class="hidden sm:inline">Connexion</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Navigation secondaire -->
        <?php if ($isLoggedIn && isset($showSecondaryNav) && $showSecondaryNav): ?>
        <div class="mt-4 pt-4 border-t border-gray-700">
            <div class="flex flex-wrap gap-2">
                <a href="index.php" class="nav-link <?= $currentPage === 'calculator' ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i>
                    Calculateur
                </a>
                <a href="devices_list.php" class="nav-link <?= $currentPage === 'repairs' ? 'active' : '' ?>">
                    <i class="bi bi-phone"></i>
                    Réparations
                </a>
                <a href="clients.php" class="nav-link <?= $currentPage === 'clients' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    Clients
                </a>
                <a href="stock.php" class="nav-link <?= $currentPage === 'stock' ? 'active' : '' ?>">
                    <i class="bi bi-box2"></i>
                    Stock
                </a>
                <a href="pos_vente.php" class="nav-link <?= $currentPage === 'pos' ? 'active' : '' ?>">
                    <i class="bi bi-cart-check"></i>
                    Vente
                </a>
                <?php if ($isAdmin): ?>
                    <a href="user_manage.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                        <i class="bi bi-person-gear"></i>
                        Utilisateurs
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </nav>
    
    <!-- Messages flash -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mx-4 mt-4">
            <?php displayFlashMessage(); ?>
        </div>
    <?php endif; ?>
    
    <!-- Contenu principal -->
    <main class="container mx-auto px-4 py-6">