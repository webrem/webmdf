<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard_modern.php');
    exit;
}

$error = ''; 
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf_token = $_POST['csrf_token'] ?? '';
        validateCSRFToken($csrf_token);
        
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Validation
        if (empty($username) || empty($password) || empty($confirm_password)) {
            throw new Exception('Tous les champs obligatoires doivent être remplis');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Les mots de passe ne correspondent pas');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores');
        }
        
        // Check if user already exists
        $userModel = ModelFactory::user();
        $existing_user = $userModel->findByUsername($username);
        
        if ($existing_user) {
            throw new Exception('Ce nom d\'utilisateur existe déjà');
        }
        
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $data = [
            'username' => $username,
            'password' => $hashed_password,
            'email' => $email,
            'status' => 'active',
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
            'permissions' => json_encode(['read' => true, 'write' => true])
        ];
        
        $user_id = $userModel->insert($data);
        
        // Log registration
        $historiqueModel = ModelFactory::historique();
        $historiqueModel->addRecord(
            $user_id,
            'create',
            'user',
            $user_id,
            'Nouvel utilisateur créé: ' . $username
        );
        
        $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        
        // Auto-login after registration
        try {
            $auth->login($username, $password);
            header('Location: dashboard_modern.php');
            exit;
        } catch (Exception $e) {
            // If auto-login fails, show success message
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - R.E.Mobiles</title>
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
                    <i class="fas fa-user-plus text-6xl gradient-text"></i>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">R.E.Mobiles</h1>
                <p class="text-white/80 text-lg">Créer un compte</p>
            </div>

            <!-- Registration Form Card -->
            <div class="glass-card rounded-2xl p-8 shadow-2xl">
                <!-- Success Message -->
                <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-green-800">Succès!</h3>
                            <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
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
                            <i class="fas fa-user mr-2 text-gray-500"></i>Nom d'utilisateur *
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                class="input-glass w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-0"
                                placeholder="Choisissez un nom d'utilisateur"
                                required
                                autocomplete="username"
                            >
                            <div class="absolute right-3 top-3 text-gray-400">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Lettres, chiffres et underscores seulement</p>
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-envelope mr-2 text-gray-500"></i>Email
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                class="input-glass w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-0"
                                placeholder="votre@email.com"
                                autocomplete="email"
                            >
                            <div class="absolute right-3 top-3 text-gray-400">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Optionnel mais recommandé pour la récupération</p>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-lock mr-2 text-gray-500"></i>Mot de passe *
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password"
                                class="input-glass w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-0"
                                placeholder="Créez un mot de passe sécurisé"
                                required
                                autocomplete="new-password"
                            >
                            <button 
                                type="button" 
                                id="toggle-password"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500">Au moins 8 caractères</p>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="space-y-2">
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-lock mr-2 text-gray-500"></i>Confirmer le mot de passe *
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password"
                                class="input-glass w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-0"
                                placeholder="Confirmez votre mot de passe"
                                required
                                autocomplete="new-password"
                            >
                            <button 
                                type="button" 
                                id="toggle-confirm-password"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn-glass w-full py-3 px-4 text-white font-semibold rounded-lg focus:outline-none focus:ring-0"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        Créer un compte
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                    <p class="text-gray-600 text-sm">
                        Déjà un compte ? 
                        <a href="login_modern.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                            Se connecter
                        </a>
                    </p>
                </div>

                <!-- Quick Solutions -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-tools mr-2"></i>Solutions Rapides
                    </h4>
                    <div class="space-y-2 text-sm">
                        <a href="setup_user_carlos.php" class="block text-blue-600 hover:text-blue-800">
                            <i class="fas fa-user-cog mr-1"></i>Gérer le compte carlos
                        </a>
                        <a href="debug_login.php" class="block text-blue-600 hover:text-blue-800">
                            <i class="fas fa-bug mr-1"></i>Diagnostiquer un problème de connexion
                        </a>
                        <a href="test_architecture.php" class="block text-blue-600 hover:text-blue-800">
                            <i class="fas fa-check-circle mr-1"></i>Tester l'architecture
                        </a>
                    </div>
                </div>
            </div>
 
            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-white/60 text-sm">
                    R.E.Mobiles Système de Gestion v2.0 - Inscription
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

        document.getElementById('toggle-confirm-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création...';
        });
    </script>
</body>
</html>