<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Login page for remshop1 database version
 * Independent login system for testing
 */

// Load configuration without authentication check
require_once __DIR__ . '/includes/config_remshop1.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize database and auth
$db = new Database();
$auth = new Auth($db);

// Handle login form submission
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate CSRF token
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $login_error = 'Erreur de sécurité. Veuillez réessayer.';
    } elseif (empty($username) || empty($password)) {
        $login_error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            if ($auth->login($username, $password)) {
                // Login successful, redirect to dashboard
                header('Location: index_remshop1.php');
                exit;
            } else {
                $login_error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            $login_error = 'Erreur lors de la connexion: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token
$csrf_token = $auth->getCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - R.E.Mobiles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .gradient-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .login-form {
            transition: all 0.3s ease;
        }
        .login-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .input-group {
            position: relative;
        }
        .input-group input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .input-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
        }
        .login-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center p-4">
        <!-- Background Animation -->
        <div class="absolute inset-0 overflow-hidden">
            <div id="particles" class="absolute inset-0"></div>
        </div>

        <!-- Login Container -->
        <div class="relative z-10 w-full max-w-md">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="glass rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-mobile-alt text-3xl gradient-text"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">R.E.Mobiles</h1>
                <p class="text-white opacity-75">Système de Gestion</p>
                <p class="text-sm text-white opacity-50 mt-2">Base de données: u498346438_remshop1</p>
            </div>

            <!-- Login Form -->
            <div class="glass login-form rounded-xl p-8">
                <h2 class="text-2xl font-bold text-white text-center mb-6">
                    <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                </h2>

                <!-- Error Message -->
                <?php if ($login_error): ?>
                    <div class="bg-red-500 bg-opacity-20 border border-red-500 text-white px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <!-- Info Message -->
                <div class="bg-blue-500 bg-opacity-20 border border-blue-500 text-white px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Test Account:</strong><br>
                    Username: admin<br>
                    Password: admin123
                </div>

                <!-- Login Form -->
                <form method="POST" action="login_remshop1.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- Username -->
                    <div class="input-group">
                        <label for="username" class="block text-white text-sm font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Nom d'utilisateur
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            class="w-full px-4 py-3 rounded-lg"
                            placeholder="Entrez votre nom d'utilisateur"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        >
                    </div>

                    <!-- Password -->
                    <div class="input-group">
                        <label for="password" class="block text-white text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Mot de passe
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            class="w-full px-4 py-3 rounded-lg"
                            placeholder="Entrez votre mot de passe"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="mr-2">
                        <label for="remember" class="text-white text-sm">Se souvenir de moi</label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="login-btn w-full text-white font-bold py-3 px-4 rounded-lg"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
                    </button>
                </form>

                <!-- Links -->
                <div class="mt-6 text-center space-y-2">
                    <a href="register_remshop1.php" class="text-blue-400 hover:text-blue-300 text-sm block">
                        <i class="fas fa-user-plus mr-1"></i>
                        Créer un compte
                    </a>
                    <a href="forgot_password_remshop1.php" class="text-blue-400 hover:text-blue-300 text-sm block">
                        <i class="fas fa-key mr-1"></i>
                        Mot de passe oublié ?
                    </a>
                </div>

                <!-- System Info -->
                <div class="mt-6 pt-6 border-t border-white border-opacity-20 text-center">
                    <p class="text-white text-xs opacity-75">
                        <i class="fas fa-database mr-1"></i>
                        Base de données indépendante: u498346438_remshop1
                    </p>
                    <p class="text-white text-xs opacity-50 mt-1">
                        Version 2.0.0 - Système de test
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-white text-sm opacity-75">
                    © 2024 R.E.Mobiles - Système de Gestion
                </p>
            </div>
        </div>
    </div>

    <script>
        // Particle animation
        document.addEventListener('DOMContentLoaded', function() {
            // Create particles
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'absolute w-1 h-1 bg-white opacity-20 rounded-full';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                document.getElementById('particles').appendChild(particle);
                
                // Animate particle
                anime({
                    targets: particle,
                    translateX: () => anime.random(-200, 200),
                    translateY: () => anime.random(-200, 200),
                    scale: [0, 1, 0],
                    opacity: [0, 0.5, 0],
                    duration: () => anime.random(3000, 6000),
                    easing: 'easeInOutQuad',
                    loop: true,
                    delay: () => anime.random(0, 2000)
                });
            }

            // Form animation
            anime({
                targets: '.login-form',
                translateY: [50, 0],
                opacity: [0, 1],
                duration: 1000,
                easing: 'easeOutExpo',
                delay: 500
            });

            // Focus on username field
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>