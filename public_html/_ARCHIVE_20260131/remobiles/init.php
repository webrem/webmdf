<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Application Initialization - R.E.Mobiles
 * Chargement sécurisé de l'environnement, de la configuration et des classes
 */

declare(strict_types=1);

// ---------------------------
// 🧩 CONFIGURATION GLOBALE
// ---------------------------

// Activer le rapport d’erreurs propre (journalisé mais sans affichage public)
error_reporting(E_ALL);
ini_set('display_errors', 0); // aucune erreur à l'écran
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Lancer le buffering pour éviter les problèmes de headers
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
}

// ---------------------------
// 📁 DÉFINITIONS DE CHEMINS
// ---------------------------
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Vérifie les dossiers critiques
$dirs = [LOGS_PATH, UPLOADS_PATH, UPLOADS_PATH . '/clients', UPLOADS_PATH . '/invoices'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ---------------------------
// 🔒 EN-TÊTES DE SÉCURITÉ
// ---------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN'); // DENY bloque certains iframes internes
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
try {
    $nonce = base64_encode(random_bytes(16));
    define('CSP_NONCE', $nonce);
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
} catch (Exception $e) {
    error_log('CSP generation failed: ' . $e->getMessage());
}

// ---------------------------
// ⚙️ CHARGEMENT DE LA CONFIGURATION
// ---------------------------
$configFile = INCLUDES_PATH . '/config_exact.php';
if (!file_exists($configFile)) {
    die('❌ Fichier de configuration manquant : config_exact.php');
}
require_once $configFile;

// ---------------------------
// 🧠 CHARGEMENT DES CLASSES CŒUR
// ---------------------------
$requiredFiles = [
    INCLUDES_PATH . '/database.php',
    INCLUDES_PATH . '/security.php',
    INCLUDES_PATH . '/auth_exact.php',
    INCLUDES_PATH . '/models.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        error_log("Fichier manquant : {$file}");
        die("Erreur interne : fichier manquant ({$file})");
    }
    require_once $file;
}

// ---------------------------
// 💾 CONNEXION BASE DE DONNÉES
// ---------------------------
try {
    $dbConfig = CONFIG['database'] ?? null;
    if (!$dbConfig) {
        throw new Exception('Configuration base de données introuvable dans CONFIG.');
    }

    $database = Database::getInstance(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['charset'] ?? 'utf8mb4'
    );

    if (class_exists('ModelFactory')) {
        ModelFactory::setDatabase($database);
    }

} catch (Throwable $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    die('Erreur de connexion à la base de données.');
}

// ---------------------------
// 👤 AUTHENTIFICATION
// ---------------------------
try {
    $auth = new Auth($database);
    $security = new Security();

    // Vérifie la session si utilisateur connecté
    if ($auth->isLoggedIn() && !$auth->validateSessionData()) {
        $auth->logout();
        header('Location: login.php');
        exit;
    }

} catch (Throwable $e) {
    error_log('[AUTH INIT ERROR] ' . $e->getMessage());
    die('Erreur d\'initialisation de l\'authentification.');
}

// ---------------------------
// ⏰ TIMEZONE ET SESSION TIMEOUT
// ---------------------------
date_default_timezone_set(CONFIG['app']['timezone'] ?? 'America/Cayenne');

if ($auth->isLoggedIn()) {
    $timeout = CONFIG['session']['timeout'] ?? 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        $auth->logout();
        header('Location: login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ---------------------------
// 🧹 NETTOYAGE PÉRIODIQUE
// ---------------------------
if (rand(1, 100) === 1 && class_exists('ModelFactory')) {
    try {
        $loginAttemptModel = ModelFactory::loginAttempt();
        $loginAttemptModel->cleanOldAttempts(24);
    } catch (Throwable $e) {
        error_log('Erreur nettoyage login_attempts : ' . $e->getMessage());
    }
}

// ---------------------------
// 🧰 FONCTIONS UTILES
// ---------------------------
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format((float)$price, 2, ',', ' ') . ' €';
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function generateCSRFToken() {
    global $auth;
    return $auth->generateCSRFToken();
}

function validateCSRFToken($token) {
    global $auth;
    return $auth->validateCSRFToken($token);
}

// ---------------------------
// 🧾 MÉTADONNÉES APP
// ---------------------------
define('APP_VERSION', '2.0.1');
echo "<!-- R.E.Mobiles v" . APP_VERSION . " - Modern PHP Architecture -->";
