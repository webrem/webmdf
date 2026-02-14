<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Classe de sécurité pour R.E.Mobiles
 * Protection contre les attaques courantes
 */

class Security {
    private $blockedIPs = [];
    private $maxAttempts = 5;
    private $lockoutTime = 900; // 15 minutes
    
    public function __construct() {
        // Initialiser la sécurité
        $this->initSecurityHeaders();
    }
    
    /**
     * Initialiser les headers de sécurité
     */
    private function initSecurityHeaders() {
        if (!headers_sent()) {
            // Content Security Policy
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none';");
            
            // X-Frame-Options
            header("X-Frame-Options: DENY");
            
            // X-Content-Type-Options
            header("X-Content-Type-Options: nosniff");
            
            // X-XSS-Protection
            header("X-XSS-Protection: 1; mode=block");
            
            // Referrer Policy
            header("Referrer-Policy: strict-origin-when-cross-origin");
            
            // Permissions Policy
            header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
        }
    }
    
    /**
     * Valider et nettoyer une entrée utilisateur
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'string':
            default:
                return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Valider une entrée selon son type
     */
    public function validateInput($input, $type, $required = true) {
        if ($required && empty($input)) {
            return false;
        }
        
        if (!$required && empty($input)) {
            return true;
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
            case 'phone':
                return $this->validatePhone($input);
            case 'price':
                return is_numeric($input) && $input >= 0;
            case 'string':
            default:
                return is_string($input) && strlen($input) > 0;
        }
    }
    
    /**
     * Valider un numéro de téléphone français
     */
    private function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) === 10 && preg_match('/^0[1-9][0-9]{8}$/', $phone);
    }
    
    /**
     * Vérifier et bloquer les IPs malveillantes
     */
    public function checkIPBlock() {
        $ip = $this->getClientIP();
        
        // Vérifier si l'IP est bloquée
        if ($this->isIPBlocked($ip)) {
            $this->logSecurityEvent('blocked_ip_attempt', ['ip' => $ip]);
            http_response_code(403);
            die('Accès refusé');
        }
        
        return true;
    }
    
    /**
     * Obtenir l'IP du client
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Vérifier si une IP est bloquée
     */
    private function isIPBlocked($ip) {
        // Ici vous pourriez vérifier dans une base de données ou un fichier
        // Pour l'instant, on utilise un tableau en mémoire
        return in_array($ip, $this->blockedIPs);
    }
    
    /**
     * Bloquer une IP temporairement
     */
    public function blockIP($ip, $duration = null) {
        $duration = $duration ?? $this->lockoutTime;
        // Dans une implémentation réelle, stocker dans la base de données
        $this->blockedIPs[] = $ip;
        $this->logSecurityEvent('ip_blocked', ['ip' => $ip, 'duration' => $duration]);
    }
    
    /**
     * Vérifier les tentatives de connexion
     */
    public function checkLoginAttempts($identifier) {
        $key = "login_attempts_$identifier";
        $attempts = $_SESSION[$key] ?? 0;
        $lastAttempt = $_SESSION[$key . '_last'] ?? 0;
        
        // Vérifier si le délai de verrouillage est passé
        if (time() - $lastAttempt > $this->lockoutTime) {
            unset($_SESSION[$key]);
            unset($_SESSION[$key . '_last']);
            return true;
        }
        
        // Vérifier le nombre de tentatives
        if ($attempts >= $this->maxAttempts) {
            $this->blockIP($this->getClientIP());
            return false;
        }
        
        return true;
    }
    
    /**
     * Enregistrer une tentative de connexion
     */
    public function recordLoginAttempt($identifier) {
        $key = "login_attempts_$identifier";
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        $_SESSION[$key . '_last'] = time();
    }
    
    /**
     * Réinitialiser les tentatives de connexion
     */
    public function resetLoginAttempts($identifier) {
        $key = "login_attempts_$identifier";
        unset($_SESSION[$key]);
        unset($_SESSION[$key . '_last']);
    }
    
    /**
     * Journaliser les événements de sécurité
     */
    public function logSecurityEvent($event, $data = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'data' => $data
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents(BASE_PATH . '/logs/security.log', $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Vérifier la taille d'un téléchargement de fichier
     */
    public function checkFileUpload($file, $maxSize = 2097152) { // 2MB par défaut
        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return false;
            default:
                return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valider le type d'un fichier téléchargé
     */
    public function validateFileType($file, $allowedTypes = []) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (empty($allowedTypes)) {
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/csv',
                'video/mp4', 'video/webm'
            ];
        }
        
        return in_array($mimeType, $allowedTypes);
    }
    
    /**
     * Nettoyer un nom de fichier
     */
    public function sanitizeFilename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9.-]/', '_', $filename);
        $filename = preg_replace('/_{2,}/', '_', $filename);
        return trim($filename, '_');
    }
    
    /**
     * Générer un nom de fichier unique
     */
    public function generateUniqueFilename($originalName, $prefix = '') {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = $this->sanitizeFilename($basename);
        
        $uniqueId = uniqid($prefix, true);
        $timestamp = date('YmdHis');
        
        return $timestamp . '_' . $uniqueId . '_' . $basename . '.' . $extension;
    }
}