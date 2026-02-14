<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Authentication System for R.E.Mobiles
 * Based on exact database structure from u498346438_calculrem.sql
 * Handles both admin_users and users tables for maximum compatibility
 */

class Auth {
    private $db;
    private $session_timeout = 3600; // 1 hour
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes
    
    public function __construct($database) {
        $this->db = $database;
        $this->initSession();
    }
    
    /**
     * Initialize secure session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception('Invalid CSRF token');
        }
        return true;
    }
    
    /**
     * Login user with enhanced security
     */
    public function login($username, $password, $csrf_token = null) {
        // Validate CSRF token if provided
        if ($csrf_token) {
            $this->validateCSRFToken($csrf_token);
        }
        
        // Check for brute force attempts
        if ($this->isAccountLocked($username)) {
            throw new Exception('Account temporarily locked due to too many failed login attempts');
        }
        
        // Sanitize username
        $username = $this->sanitizeInput($username);
        
        // Try to authenticate user
        $user = $this->authenticateUser($username, $password);
        
        if ($user) {
            // Reset failed attempts
            $this->resetFailedAttempts($username);
            
            // Create session
            $this->createUserSession($user);
            
            // Log successful login
            $this->logLoginAttempt($username, 'success');
            
            return true;
        } else {
            // Record failed attempt
            $this->recordFailedAttempt($username);
            $this->logLoginAttempt($username, 'failed');
            
            throw new Exception('Invalid username or password');
        }
    }
    
    /**
     * Authenticate user against database
     */
    private function authenticateUser($username, $password) {
        // Try admin_users table first (primary)
        $sql = "SELECT id, username, password, role, status FROM admin_users WHERE username = :username AND status = 'active' LIMIT 1";
        $user = $this->db->fetch($sql, [':username' => $username]);
        
        if (!$user) {
            // Fallback to users table
            $sql = "SELECT id, username, password, 'user' as role, 'active' as status FROM users WHERE username = :username LIMIT 1";
            $user = $this->db->fetch($sql, [':username' => $username]);
        }
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Create user session
     */
    private function createUserSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > $this->session_timeout) {
            $this->logout();
            return false;
        }
        
        // Validate session data
        if (!$this->validateSessionData()) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Validate session data integrity
     */
    private function validateSessionData() {
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Basic validation
        if ($_SESSION['ip_address'] !== $current_ip || 
            $_SESSION['user_agent'] !== $current_agent) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $_SESSION = [];
        session_destroy();
        
        // Clear session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, 
                $params['path'], $params['domain'], 
                $params['secure'], $params['httponly']);
        }
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Check user role
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if account is locked due to brute force
     */
    private function isAccountLocked($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                FROM login_attempts 
                WHERE (username = :username OR ip_address = :ip) 
                AND attempt_result = 'failed' 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL :lockout_duration SECOND)";
        
        $result = $this->db->fetch($sql, [
            ':username' => $username,
            ':ip' => $ip,
            ':lockout_duration' => $this->lockout_duration
        ]);
        
        return $result['attempts'] >= $this->max_login_attempts;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "INSERT INTO login_attempts (username, ip_address, attempt_result) VALUES (:username, :ip, 'failed')";
        $this->db->execute($sql, [':username' => $username, ':ip' => $ip]);
    }
    
    /**
     * Reset failed attempts on successful login
     */
    private function resetFailedAttempts($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "DELETE FROM login_attempts WHERE username = :username OR ip_address = :ip";
        $this->db->execute($sql, [':username' => $username, ':ip' => $ip]);
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($username, $result) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO login_logs (username, ip_address, user_agent, result) 
                VALUES (:username, :ip, :user_agent, :result)";
        
        $this->db->execute($sql, [
            ':username' => $username,
            ':ip' => $ip,
            ':user_agent' => $user_agent,
            ':result' => $result
        ]);
    }
    
    /**
     * Sanitize input
     */
    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Change user password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        // Verify current password
        $sql = "SELECT password FROM admin_users WHERE id = :id LIMIT 1";
        $user = $this->db->fetch($sql, [':id' => $user_id]);
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Validate new password
        if (strlen($new_password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin_users SET password = :password WHERE id = :id";
        $this->db->execute($sql, [':password' => $hashed_password, ':id' => $user_id]);
        
        return true;
    }
    
    /**
     * Get user permissions
     */
    public function getUserPermissions($user_id) {
        $sql = "SELECT permissions FROM admin_users WHERE id = :id LIMIT 1";
        $result = $this->db->fetch($sql, [':id' => $user_id]);
        
        if ($result && $result['permissions']) {
            return json_decode($result['permissions'], true) ?? [];
        }
        
        return [];
    }
}