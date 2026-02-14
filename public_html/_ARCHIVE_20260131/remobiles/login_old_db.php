<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Page de connexion adaptée pour l'ancienne base de données
 */

// Configuration pour utiliser l'ancienne base de données
$servername = "localhost";
$username = "u498346438_remshop1";
$password = "Remshop104";
$dbname = "u498346438_remshop1";

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Démarrer la session
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique

$error = '';

// Traiter la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginUsername = trim($_POST['username'] ?? '');
    $loginPassword = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($loginUsername) || empty($loginPassword)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        // Vérifier dans l'ancienne table admin_users
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $loginUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($loginPassword, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin';
                $_SESSION['login_time'] = time();
                
                // Rediriger vers le dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect";
            }
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect";
        }
        
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - R.E.Mobiles</title>
    <meta name="description" content="Connectez-vous à votre espace R.E.Mobiles">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Anime.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #000000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        /* Effet de grille animée */
        .grid-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(13, 202, 240, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(13, 202, 240, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            z-index: -1;
        }
        
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        /* Glass morphism pour la carte de connexion */
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(13, 202, 240, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(13, 202, 240, 0.2);
            border-color: rgba(13, 202, 240, 0.4);
        }
        
        /* Inputs modernes */
        .input-modern {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            padding: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .input-modern:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #0dcaf0;
            box-shadow: 0 0 0 3px rgba(13, 202, 240, 0.1);
            outline: none;
        }
        
        .input-modern::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Bouton futuriste */
        .btn-futuristic {
            background: linear-gradient(135deg, #0dcaf0 0%, #0b5ed7 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 16px 32px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(13, 202, 240, 0.3);
            width: 100%;
        }
        
        .btn-futuristic:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 202, 240, 0.4);
        }
        
        .btn-futuristic::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-futuristic:hover::before {
            left: 100%;
        }
        
        /* Animation d'apparition */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Logo animé */
        .logo-icon {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body>
    <!-- Grille animée en fond -->
    <div class="grid-pattern"></div>
    
    <!-- Carte de connexion -->
    <div class="login-card p-8 w-full max-w-md mx-4 text-center fade-in">
        <!-- Logo -->
        <div class="mb-6">
            <i class="bi bi-cpu-fill logo-icon text-6xl text-cyan-400"></i>
        </div>
        
        <!-- Titre -->
        <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 mb-2">
            R.E.Mobiles
        </h1>
        <p class="text-gray-300 mb-6">Connexion avec l'ancienne base de données</p>
        
        <!-- Message d'erreur -->
        <?php if ($error): ?>
            <div class="bg-red-500 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 mb-6 text-red-200">
                <div class="flex items-center">
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire de connexion -->
        <form method="POST" class="space-y-6">
            <!-- Nom d'utilisateur -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">
                    Nom d'utilisateur
                </label>
                <div class="relative">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="input-modern" 
                           placeholder="Entrez votre nom d'utilisateur"
                           required
                           autofocus>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="bi bi-person-fill text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <!-- Mot de passe -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                    Mot de passe
                </label>
                <div class="relative">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="input-modern" 
                           placeholder="Entrez votre mot de passe"
                           required>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" 
                                id="togglePassword" 
                                class="text-gray-400 hover:text-cyan-400 focus:outline-none">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Bouton de connexion -->
            <button type="submit" class="btn-futuristic">
                <i class="bi bi-unlock-fill mr-2"></i>
                Se connecter
            </button>
        </form>
        
        <!-- Informations -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-400">
                <i class="bi bi-shield-lock mr-1"></i>
                Connexion sécurisée avec l'ancienne base de données
            </p>
        </div>
        
        <!-- Version -->
        <div class="mt-4 text-center">
            <p class="text-xs text-gray-500">
                Version 2.0.0 - Compatible ancienne DB - R.E.Mobiles © 2024
            </p>
        </div>
        
        <!-- Tests -->
        <div class="mt-6 pt-6 border-t border-gray-700">
            <a href="test_old_db.php" class="text-cyan-400 hover:text-cyan-300 text-sm">
                <i class="bi bi-check-circle mr-1"></i>
                Tester la compatibilité DB
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle du mot de passe
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Changer l'icône
                const icon = this.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
            });
            
            // Animation de la carte
            anime({
                targets: '.login-card',
                opacity: [0, 1],
                translateY: [50, 0],
                duration: 800,
                easing: 'easeOutExpo'
            });
            
            // Focus automatique sur le premier champ
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>

<?php
// Fermer la connexion
$conn->close();
?>