<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Authentication System - R.E.Mobiles (version unifiée)
 * Compatible avec les modules modernes (stock_modern.php, clients_modern.php, etc.)
 */

class Auth {
    private $db;
    private $session_timeout = 3600;
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 min

    public function __construct($database) {
        $this->db = $database;
        $this->initSession();
    }

    /** Initialisation session sécurisée **/
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('rem_session');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
        }
    }

    /** Vérifie si connecté **/
    public function isLoggedIn(): bool {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    /** Retourne infos utilisateur courant **/
    public function getCurrentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    /** Génère un token CSRF **/
    public function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Valide le token CSRF **/
    public function validateCSRFToken($token): bool {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception('Invalid CSRF token');
        }
        return true;
    }

    /** Connexion utilisateur **/
    public function login(string $username, string $password, ?string $csrf_token = null): bool {
        if ($csrf_token) $this->validateCSRFToken($csrf_token);

        $username = $this->sanitizeInput($username);

        // Protection brute force
        if ($this->isAccountLocked($username)) {
            throw new Exception('Compte temporairement verrouillé (trop de tentatives)');
        }

        $user = $this->authenticateUser($username, $password);

        if ($user) {
            $this->resetFailedAttempts($username);
            $this->createUserSession($user);
            $this->logLoginAttempt($username, 'success');
            return true;
        } else {
            $this->recordFailedAttempt($username);
            $this->logLoginAttempt($username, 'failed');
            throw new Exception('Identifiant ou mot de passe incorrect');
        }
    }

    /** Authentification en base **/
    private function authenticateUser($username, $password): ?array {
        // Priorité admin_users
        $sql = "SELECT id, username, password, role, status 
                FROM admin_users 
                WHERE username = :username AND status = 'active' LIMIT 1";
        $user = $this->db->fetch($sql, [':username' => $username]);

        if (!$user) {
            // Fallback users table
            $sql = "SELECT id, username, password, 'user' as role, 'active' as status 
                    FROM users WHERE username = :username LIMIT 1";
            $user = $this->db->fetch($sql, [':username' => $username]);
        }

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    /** Création de la session utilisateur **/
    private function createUserSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'] ?? 'user',
        ];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['last_activity'] = time();
    }

    /** Validation session **/
    public function validateSessionData(): bool {
        if (!$this->isLoggedIn()) return false;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return $_SESSION['ip'] === $ip && $_SESSION['agent'] === $agent;
    }

    /** Déconnexion **/
    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /** Vérifie le rôle **/
    public function hasRole(string $role): bool {
        return $this->isLoggedIn() && ($_SESSION['user']['role'] ?? '') === $role;
    }

    // ---------------------------------------------------
    // 🔐 GESTION BRUTEFORCE + JOURNAUX
    // ---------------------------------------------------

    private function isAccountLocked(string $username): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE (username = :username OR ip_address = :ip)
                AND attempt_result = 'failed'
                AND attempt_time > DATE_SUB(NOW(), INTERVAL :lockout_duration SECOND)";
        $res = $this->db->fetch($sql, [
            ':username' => $username,
            ':ip' => $ip,
            ':lockout_duration' => $this->lockout_duration
        ]);
        return $res && $res['attempts'] >= $this->max_login_attempts;
    }

    private function recordFailedAttempt(string $username): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sql = "INSERT INTO login_attempts (username, ip_address, attempt_result) VALUES (:username, :ip, 'failed')";
        $this->db->execute($sql, [':username' => $username, ':ip' => $ip]);
    }

    private function resetFailedAttempts(string $username): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sql = "DELETE FROM login_attempts WHERE username = :username OR ip_address = :ip";
        $this->db->execute($sql, [':username' => $username, ':ip' => $ip]);
    }

    private function logLoginAttempt(string $username, string $result): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sql = "INSERT INTO login_logs (username, ip_address, user_agent, result) 
                VALUES (:username, :ip, :agent, :result)";
        $this->db->execute($sql, [
            ':username' => $username,
            ':ip' => $ip,
            ':agent' => $agent,
            ':result' => $result
        ]);
    }

    // ---------------------------------------------------
    // 🧰 UTILITAIRES
    // ---------------------------------------------------

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
