<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/** 
 * Système d'authentification sécurisé
 */

class Auth {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require 'config.php';
        $this->initSession();
    }
    
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $security = $this->config['security'];
            
            ini_set('session.cookie_secure', $security['cookie_secure'] ? '1' : '0');
            ini_set('session.cookie_httponly', $security['cookie_httponly'] ? '1' : '0');
            ini_set('session.cookie_samesite', $security['cookie_samesite']);
            ini_set('session.use_strict_mode', '1');
            
            session_name($security['session_name']);
            session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
            
            // Régénérer l'ID de session pour prévenir les attaques de fixation
            if (!isset($_SESSION['created'])) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    public function login($username, $password) {
        try {
            $user = $this->db->fetch(
                "SELECT id, username, password, role FROM users WHERE username = :username AND active = 1 LIMIT 1",
                [':username' => $username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Réinitialiser les tentatives de connexion
                $this->db->update('users', 
                    ['failed_attempts' => 0, 'last_login' => date('Y-m-d H:i:s')],
                    'id = :id',
                    [':id' => $user['id']]
                );
                
                // Définir les variables de session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Régénérer l'ID de session après connexion réussie
                session_regenerate_id(true);
                
                return true;
            }
            
            // Gérer les tentatives échouées
            if ($user) {
                $this->db->update('users',
                    ['failed_attempts' => new PDO('failed_attempts + 1')],
                    'id = :id',
                    [':id' => $user['id']]
                );
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la connexion: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUsername() {
        return $_SESSION['username'] ?? 'Invité';
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? 'user';
    }
    
    public function isAdmin() {
        return $this->getRole() === 'admin';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}