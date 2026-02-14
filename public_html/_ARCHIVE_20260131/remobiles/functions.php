<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 
/**
 * Fonctions utilitaires globales
 */

/**
 * Sécuriser une chaîne de caractères
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Formater un prix en euros
 */
function formatPrice($price) {
    return number_format((float)$price, 2, ',', ' ') . ' €';
}

/**
 * Formater une date
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Générer une référence unique
 */
function generateReference($prefix = 'REF') {
    return $prefix . '-' . date('YmdHis') . '-' . substr(uniqid(), -4);
}

/**
 * Valider un numéro de téléphone français
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10 && preg_match('/^0[1-9][0-9]{8}$/', $phone);
}

/**
 * Valider un email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Rediriger avec un message
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    header("Location: $url");
    exit;
}

/**
 * Afficher un message flash
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type'];
        
        $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
        
        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($message);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
        echo "</div>";
        
        unset($_SESSION['flash_message']);
    }
}

/**
 * Logger une erreur
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Obtenir l'âge d'un fichier
 */
function getFileAge($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    return time() - filemtime($filePath);
}

/**
 * Nettoyer les anciens fichiers
 */
function cleanupOldFiles($directory, $maxAge = 3600) {
    if (!is_dir($directory)) {
        return;
    }
    
    $files = glob($directory . '/*');
    foreach ($files as $file) {
        if (is_file($file) && getFileAge($file) > $maxAge) {
            unlink($file);
        }
    }
}

/**
 * Vérifier si une chaîne est JSON valide
 */
function isValidJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Tronquer une chaîne
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Calculer la TVA
 */
function calculateTVA($amount, $rate = 20) {
    return $amount * ($rate / 100);
}

/**
 * Obtenir les statistiques du tableau de bord
 */
function getDashboardStats($db) {
    try {
        $stats = [];
        
        // Nombre total de réparations
        $result = $db->fetch("SELECT COUNT(*) as total FROM devices");
        $stats['total_repairs'] = $result['total'];
        
        // Réparations en cours
        $result = $db->fetch("SELECT COUNT(*) as total FROM devices WHERE status = 'En cours'");
        $stats['repairs_in_progress'] = $result['total'];
        
        // Réparations terminées aujourd'hui
        $result = $db->fetch("SELECT COUNT(*) as total FROM devices WHERE status = 'Terminé' AND DATE(completed_at) = CURDATE()");
        $stats['repairs_completed_today'] = $result['total'];
        
        // Chiffre d'affaires du mois
        $result = $db->fetch("SELECT SUM(prix_final) as total FROM ventes_historique WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['monthly_revenue'] = $result['total'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        logError("Erreur lors de la récupération des statistiques: " . $e->getMessage());
        return [];
    }
}