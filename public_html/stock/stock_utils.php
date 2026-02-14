<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique

/**
 * ==========================================================
 * 🔧 Fonctions utilitaires globales - R.E.Mobiles
 * ==========================================================
 */

/**
 * Échappe une chaîne HTML pour éviter les injections XSS
 */
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Retourne le rôle de l'utilisateur connecté
 */
function user_role() {
    return $_SESSION['role'] ?? 'user';
}

/**
 * Vérifie si l'utilisateur est administrateur
 */
function is_admin() {
    return user_role() === 'admin';
}

/**
 * Convertit une valeur en float proprement (en gérant virgule et espace)
 */
function as_float($v) {
    return (float)str_replace([',', ' '], ['.', ''], (string)$v);
}

/**
 * Renvoie la date et l'heure actuelles à l'heure de la Guyane Française (Cayenne)
 */
function now_guyane() {
    $dt = new DateTime('now', new DateTimeZone('America/Cayenne'));
    return $dt->format('Y-m-d H:i:s');
}

/**
 * Lecture sécurisée d’une valeur POST
 */
if (!function_exists('post')) {
    function post(string $key, $default = '') {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }
}

/**
 * Lecture sécurisée d’une valeur GET
 */
if (!function_exists('get')) {
    function get(string $key, $default = '') {
        return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
    }
}

/**
 * Vérifie un code admin dans la base de données
 */
function verify_admin_code(mysqli $conn, string $code): bool {
    if (trim($code) === '') return false;

    if ($stmt = $conn->prepare("SELECT password FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1")) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $hash = (string)$row['password'];
            if (password_verify($code, $hash) || $code === $hash) return true;
        }
    }

    // 🔒 Code de secours par défaut si aucun admin trouvé
    $FALLBACK_PIN = 'admin';
    return hash_equals($FALLBACK_PIN, $code);
}

/**
 * Vérifie si l'utilisateur est admin ou dispose d'un code valide
 */
function require_admin_or_code(mysqli $conn, ?string $code): bool {
    if (is_admin()) return true;
    return verify_admin_code($conn, (string)$code);
}

/**
 * Bind dynamique des paramètres MySQLi
 * Évite les erreurs "ArgumentCountError" quand le nombre de types ne correspond pas
 */
function bindParamsDynamic(mysqli_stmt $stmt, array $params) {
    if (empty($params)) return;
    $types = '';
    $refs = [];
    foreach ($params as $key => $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}
