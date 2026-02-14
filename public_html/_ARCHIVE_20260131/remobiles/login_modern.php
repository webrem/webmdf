<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf_token = $_POST['csrf_token'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception('Veuillez remplir tous les champs');
        }
        
        // Attempt login
        if ($auth->login($username, $password, $csrf_token)) {
            // Log successful login
            $userModel = ModelFactory::user();
            $user = $userModel->findByUsername($username);
            if ($user) {
                $userModel->updateLastLogin($user['id']);
                
                // Add to history
                $historiqueModel = ModelFactory::historique();
                $historiqueModel->addRecord(
                    $user['id'], 
                    'login', 
                    'user', 
                    $user['id'], 
                    'Connexion réussie'
                );
            }
            
            header('Location: dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate CSRF token
$csrf_token = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - R.E.Mobiles</title>
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
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .input-glass {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-glass {
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: all 0.3s ease;
        }
        .btn-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .error-shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="floating absolute top-20 left-10 w-20 h-20 bg-white opacity-10 rounded-full"></div>
        <div class="floating absolute top-40 right-20 w-16 h-16 bg-white opacity-10 rounded-full" style="animation-delay: -2s;"></div>
        <div class="floating absolute bottom-40 left-1/4 w-12 h-12 bg-white opacity-10 rounded-full" style="animation-delay: -4s;"></div>
        <div class="floating absolute bottom-20 right-1/3 w-24 h-24 bg-white opacity-10 rounded-full" style="animation-delay: -1s;"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-md">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <div class="glass-card rounded-2xl p-6 mb-6 inline-block">
                    <i class="fas fa-mobile-alt text-6xl gradient-text"></i>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">R.E.Mobiles</h1>
                <p class="text-white/80 text-lg">Système de Gestion</p>
            </div>

            <!-- Login Form Card -->
            <div class="glass-card rounded-2xl p-8 shadow-2xl">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Connexion</h2>
                    <p class="text-gray-600">Accédez à votre espace de travail</p>
                </div>

                <?php if ($error): ?>
                <div id="error-alert" class="error-shake bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-red-800">Erreur</h3>
                            <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- Username Field -->
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-user mr-2 text-gray-500"></i>Nom d'utilisateur
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?php echo htmlspecialchars($username); ?>"
                                class="input-glass w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-0"
                                placeholder="Entrez votre nom d'utilisateur"
                                required
                                autocomplete="username"
                            >
                            <div class="absolute right-3 top-3 text-gray-400">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-lock mr-2 text-gray-500"></i>Mot de passe
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password"
                                class="input-glass w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-0"
                                placeholder="Entrez votre mot de passe"
                                required
                                autocomplete="current-password"
                            >
                            <button 
                                type="button" 
                                id="toggle-password"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-500">
                            Mot de passe oublié?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn-glass w-full py-3 px-4 text-white font-semibold rounded-lg focus:outline-none focus:ring-0"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
                    </button>
                </form>

                <!-- Security Info -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-center text-xs text-gray-500 space-x-4">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt mr-1"></i>
                            <span>Sécurisé par CSRF</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-lock mr-1"></i>
                            <span>HTTPS</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-user-shield mr-1"></i>
                            <span>Protection brute force</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-white/60 text-sm">
                    R.E.Mobiles Système de Gestion v2.0
                </p>
            </div>
        </div>
    </div>
 
    <script>
        // Password visibility toggle
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form animation on load
        anime({
            targets: '.glass-card',
            translateY: [50, 0],
            opacity: [0, 1],
            duration: 800,
            easing: 'easeOutQuart',
            delay: 200
        });

        // Error alert animation
        <?php if ($error): ?>
        anime({
            targets: '#error-alert',
            scale: [0.9, 1],
            duration: 300,
            easing: 'easeOutBack'
        });
        <?php endif; ?>

        // Input focus animations
        document.querySelectorAll('.input-glass').forEach(input => {
            input.addEventListener('focus', function() {
                anime({
                    targets: this,
                    scale: [1, 1.02],
                    duration: 200,
                    easing: 'easeOutQuart'
                });
            });
            
            input.addEventListener('blur', function() {
                anime({
                    targets: this,
                    scale: [1.02, 1],
                    duration: 200,
                    easing: 'easeOutQuart'
                });
            });
        });

        // Button hover animation
        document.querySelector('.btn-glass').addEventListener('mouseenter', function() {
            anime({
                targets: this,
                scale: 1.05,
                duration: 200,
                easing: 'easeOutQuart'
            });
        });

        document.querySelector('.btn-glass').addEventListener('mouseleave', function() {
            anime({
                targets: this,
                scale: 1,
                duration: 200,
                easing: 'easeOutQuart'
            });
        });

        // Prevent form resubmission
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Connexion...';
        });
    </script>
</body>
</html>