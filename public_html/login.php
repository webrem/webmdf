<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();

require_once __DIR__ . '/sync_time.php';

$servername = "localhost";
$username = "u498346438_calculrem";
$password = "Calculrem1";
$dbname = "u498346438_calculrem";
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            header("Location: check_caisse.php");
            exit;
        } else {
            $error = "❌ Mot de passe incorrect.";
        }
    } else {
        $error = "❌ Utilisateur introuvable.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion - R.E.Mobiles</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #dc3545;
            --primary-dark: #a71d2a;
            --secondary: #2d2d2d;
            --dark: #0a0a0a;
            --darker: #000;
            --light: #fff;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--dark), #1a1a1a, var(--darker));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--light);
        }

        .grid-pattern {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image:
                linear-gradient(rgba(220, 53, 69, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(220, 53, 69, 0.08) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            z-index: -1;
        }

        @keyframes gridMove {
            0% { transform: translate(0,0); }
            100% { transform: translate(50px,50px); }
        }

        .login-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(220,53,69,0.25);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 380px;
            transition: 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(220,53,69,0.3);
        }

        .input-modern {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: rgba(255,255,255,0.08);
            border: 2px solid rgba(255,255,255,0.1);
            color: var(--light);
            transition: all 0.3s;
        }

        .input-modern:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220,53,69,0.15);
            background: rgba(255,255,255,0.12);
            outline: none;
        }

        .input-modern::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .btn-futuristic {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--light);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(220,53,69,0.4);
            transition: all 0.3s;
        }

        .btn-futuristic:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220,53,69,0.5);
        }

        .alert {
            background-color: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.4);
            color: #ff8a95;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-icon {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
    </style>
</head>
<body>
    <div class="grid-pattern"></div>

    <div class="login-card fade-in">
        <div class="text-center mb-6">
            <i class="bi bi-cpu-fill logo-icon text-5xl text-red-500"></i>
            <h1 class="text-2xl font-bold mt-3 text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-red-700">
                R.E.Mobiles
            </h1>
            <p class="text-gray-400 text-sm">Connectez-vous à votre espace</p>
        </div>

        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <div>
                <label class="block text-sm text-gray-300 mb-2">Nom d'utilisateur</label>
                <input type="text" name="username" class="input-modern" placeholder="Votre identifiant" required autofocus>
            </div>
            <div>
                <label class="block text-sm text-gray-300 mb-2">Mot de passe</label>
                <div class="relative">
                    <input type="password" id="password" name="password" class="input-modern pr-10" placeholder="Votre mot de passe" required>
                    <button type="button" id="togglePassword" class="absolute right-3 top-3 text-gray-400 hover:text-red-400 focus:outline-none">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-futuristic">
                <i class="bi bi-unlock-fill mr-2"></i> Se connecter
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-gray-400">
            <i class="bi bi-shield-lock mr-1"></i> Connexion sécurisée
        </div>

        <p class="text-center mt-3 text-xs text-gray-500">R.E.Mobiles © 2025 — Version 2.0</p>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            icon.className = type === 'password' ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
        });
    </script>
</body>
</html>
