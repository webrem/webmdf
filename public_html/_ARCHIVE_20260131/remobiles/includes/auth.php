<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Système d'authentification sécurisé - Adapté pour l'ancienne base de données
 */

class Auth {
    private $db;
    private $config;
    private $security;
    
    public function __construct() {
        $this->config = require 'config.php';
        $this->db = Database::getInstance();
        $this->security = new Security();
        $this->initSession();
    }
    
    /**
     * Initialiser la session avec sécurité renforcée
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $security = $this->config['security'];
            
            // Configuration des cookies de session
            ini_set('session.cookie_secure', $security['cookie_secure'] ? '1' : '0');
            ini_set('session.cookie_httponly', $security['cookie_httponly'] ? '1' : '0');
            ini_set('session.cookie_samesite', $security['cookie_samesite']);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.gc_maxlifetime', $security['session_lifetime']);
            
            // Nom personnalisé pour la session
            session_name($security['session_name']);
            session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
            
            // Régénérer l'ID de session pour prévenir les attaques
            if (!isset($_SESSION['created'])) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
                $_SESSION['last_activity'] = time();
            }
            
            // Vérifier le timeout de la session
            $this->checkSessionTimeout();
        }
    }
    
    /**
     * Vérifier le timeout de la session
     */
    private function checkSessionTimeout() {
        $sessionLifetime = $this->config['security']['session_lifetime'];
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionLifetime)) {
            // Session expirée
            $this->logout();
            return false;
        }
        
        // Mettre à jour la dernière activité
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Connexion de l'utilisateur - UTILISER L'ANCIENNE TABLE admin_users
     */
    public function login($username, $password) {
        try {
            // Vérifier les tentatives de connexion
            if (!$this->security->checkLoginAttempts($username)) {
                $this->security->logSecurityEvent('too_many_login_attempts', ['username' => $username]);
                return false;
            }
            
            // Nettoyer le nom d'utilisateur
            $username = $this->security->sanitizeInput($username);
            
            // Requête sur l'ancienne table admin_users
            $sql = "SELECT id, username, password FROM " . TABLE_USERS . " WHERE username = :username AND active = 1 LIMIT 1";
            $user = $this->db->fetch($sql, [':username' => $username]);
            
            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $this->security->resetLoginAttempts($username);
                
                // Mettre à jour les informations de connexion
                $updateSql = "UPDATE " . TABLE_USERS . " SET last_login = NOW() WHERE id = :id";
                $this->db->query($updateSql, [':id' => $user['id']]);
                
                // Définir les variables de session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin'; // Par défaut, tous les utilisateurs de l'ancienne table sont admin
                $_SESSION['login_time'] = time();
                
                // Régénérer l'ID de session après connexion réussie
                session_regenerate_id(true);
                
                $this->security->logSecurityEvent('successful_login', ['username' => $username]);
                return true;
            }
            
            // Échec de connexion
            $this->security->recordLoginAttempt($username);
            $this->security->logSecurityEvent('failed_login', ['username' => $username]);
            return false;
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('login_error', ['username' => $username, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Déconnexion de l'utilisateur
     */
    public function logout() {
        // Détruire toutes les variables de session
        $_SESSION = [];
        
        // Détruire le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
        
        $this->security->logSecurityEvent('logout', ['user_id' => $this->getUserId()]);
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Obtenir l'ID de l'utilisateur
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtenir le nom d'utilisateur
     */
    public function getUsername() {
        return $_SESSION['username'] ?? 'Invité';
    }
    
    /**
     * Obtenir le rôle de l'utilisateur
     */
    public function getRole() {
        return $_SESSION['role'] ?? 'user';
    }
    
    /**
     * Vérifier si l'utilisateur est administrateur
     */
    public function isAdmin() {
        return $this->getRole() === 'admin';
    }
    
    /**
     * Exiger une connexion
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $this->security->logSecurityEvent('access_denied_not_logged_in', ['url' => $_SERVER['REQUEST_URI']]);
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Exiger un rôle administrateur
     */
    public function requireAdmin() {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            $this->security->logSecurityEvent('access_denied_insufficient_permissions', [
                'user_id' => $this->getUserId(),
                'role' => $this->getRole(),
                'url' => $_SERVER['REQUEST_URI']
            ]);
            
            $_SESSION['flash_message'] = [
                'message' => 'Accès refusé. Vous devez être administrateur pour accéder à cette page.',
                'type' => 'error'
            ];
            
            header('Location: dashboard.php');
            exit;
        }
    }
    
    /**
     * Générer un token CSRF
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valider un token CSRF
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $sql = "SELECT id, username, role, created_at, last_login FROM " . TABLE_USERS . " WHERE id = :id LIMIT 1";
            return $this->db->fetch($sql, [':id' => $this->getUserId()]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Mettre à jour les informations de l'utilisateur
     */
    public function updateUser($userId, array $data) {
        $allowedFields = ['username', 'password', 'email', 'first_name', 'last_name'];
        $updateData = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                if ($key === 'password') {
                    $updateData[$key] = password_hash($value, PASSWORD_DEFAULT);
                } else {
                    $updateData[$key] = $value;
                }
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $setParts = [];
        foreach ($updateData as $column => $value) {
            $setParts[] = "$column = :$column";
        }
        
        $sql = "UPDATE " . TABLE_USERS . " SET " . implode(', ', $setParts) . " WHERE id = :id";
        $updateData['id'] = $userId;
        
        try {
            return $this->db->query($sql, $updateData);
        } catch (Exception $e) {
            $this->security->logSecurityEvent('user_update_error', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function createUser(array $data) {
        $requiredFields = ['username', 'password'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Le champ $field est requis");
            }
        }
        
        // Vérifier si l'utilisateur existe déjà
        $existing = $this->db->fetch(
            "SELECT COUNT(*) as count FROM " . TABLE_USERS . " WHERE username = :username",
            [':username' => $data['username']]
        );
        
        if ($existing && $existing['count'] > 0) {
            throw new RuntimeException("Un utilisateur avec ce nom existe déjà");
        }
        
        $userData = [
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Ajouter les champs optionnels
        $optionalFields = ['email', 'first_name', 'last_name', 'role'];
        foreach ($optionalFields as $field) {
            if (isset($data[$field])) {
                $userData[$field] = $data[$field];
            }
        }
        
        $columns = array_keys($userData);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = "INSERT INTO " . TABLE_USERS . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $this->db->query($sql, $userData);
            $userId = $this->db->getConnection()->lastInsertId();
            
            $this->security->logSecurityEvent('user_created', ['user_id' => $userId, 'username' => $data['username']]);
            return $userId;
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('user_creation_error', ['username' => $data['username'], 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($userId) {
        if ($userId == $this->getUserId()) {
            throw new RuntimeException("Vous ne pouvez pas supprimer votre propre compte");
        }
        
        try {
            $sql = "DELETE FROM " . TABLE_USERS . " WHERE id = :id";
            $result = $this->db->query($sql, [':id' => $userId]);
            
            $this->security->logSecurityEvent('user_deleted', ['user_id' => $userId]);
            return $result;
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('user_deletion_error', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Obtenir la liste des utilisateurs
     */
    public function getUsers($limit = 100, $offset = 0) {
        try {
            $sql = "SELECT id, username, role, email, first_name, last_name, active, created_at, last_login 
                    FROM " . TABLE_USERS . " 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            return $this->db->fetchAll($sql, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('get_users_error', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Rechercher des utilisateurs
     */
    public function searchUsers($searchTerm, $limit = 20) {
        try {
            $sql = "SELECT id, username, role, email, first_name, last_name, active, created_at 
                    FROM " . TABLE_USERS . " 
                    WHERE username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, [
                ':search' => "%$searchTerm%",
                ':limit' => $limit
            ]);
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('search_users_error', ['search' => $searchTerm, 'error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Compter le nombre d'utilisateurs
     */
    public function countUsers() {
        try {
            $sql = "SELECT COUNT(*) as count FROM " . TABLE_USERS;
            $result = $this->db->fetch($sql);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
}