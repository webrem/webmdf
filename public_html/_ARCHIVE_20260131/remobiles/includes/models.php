<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Database Models for R.E.Mobiles
 * Based on exact table structure from u498346438_calculrem.sql
 */

/**
 * Base Model Class
 */
abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primary_key = 'id';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primary_key} = :id LIMIT 1";
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Find all records
     */
    public function findAll($order_by = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($order_by) {
            $sql .= " ORDER BY {$order_by}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Insert new record
     */
    public function insert($data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return ":{$field}"; }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->execute($sql, $data);
        return $this->db->lastInsertId();
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $fields = array_keys($data);
        $sets = array_map(function($field) { return "{$field} = :{$field}"; }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . 
               " WHERE {$this->primary_key} = :id";
        
        $data['id'] = $id;
        return $this->db->execute($sql, $data);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primary_key} = :id";
        return $this->db->execute($sql, [':id' => $id]);
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] ?? 0;
    }
}

/**
 * User Model (admin_users table)
 */
class UserModel extends BaseModel {
    protected $table = 'admin_users';
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        return $this->db->fetch($sql, [':username' => $username]);
    }
    
    /**
     * Find active users
     */
    public function findActive() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY username";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin($user_id) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
        return $this->db->execute($sql, [':id' => $user_id]);
    }
}

/**
 * Client Model (clients table)
 */
class ClientModel extends BaseModel {
    protected $table = 'clients';
    
    /**
     * Find client by phone
     */
    public function findByPhone($phone) {
        $sql = "SELECT * FROM {$this->table} WHERE phone = :phone LIMIT 1";
        return $this->db->fetch($sql, [':phone' => $phone]);
    }
    
    /**
     * Search clients
     */
    public function search($query) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :query OR phone LIKE :query OR email LIKE :query 
                ORDER BY name";
        return $this->db->fetchAll($sql, [':query' => "%{$query}%"]);
    }
    
    /**
     * Get client with device count
     */
    public function getWithDeviceCount($client_id) {
        $sql = "SELECT c.*, COUNT(d.id) as device_count 
                FROM {$this->table} c 
                LEFT JOIN devices d ON c.id = d.client_id 
                WHERE c.id = :id 
                GROUP BY c.id";
        return $this->db->fetch($sql, [':id' => $client_id]);
    }
}

/**
 * Device Model (devices table)
 */
class DeviceModel extends BaseModel {
    protected $table = 'devices';
    
    /**
     * Find devices by client
     */
    public function findByClient($client_id) {
        $sql = "SELECT * FROM {$this->table} WHERE client_id = :client_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':client_id' => $client_id]);
    }
    
    /**
     * Find devices by status
     */
    public function findByStatus($status) {
        $sql = "SELECT d.*, c.name as client_name, c.phone as client_phone 
                FROM {$this->table} d 
                LEFT JOIN clients c ON d.client_id = c.id 
                WHERE d.status = :status 
                ORDER BY d.created_at DESC";
        return $this->db->fetchAll($sql, [':status' => $status]);
    }
    
    /**
     * Get device statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                status,
                COUNT(*) as count,
                SUM(repair_cost) as total_cost,
                AVG(repair_cost) as avg_cost
                FROM {$this->table} 
                GROUP BY status";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Update device status
     */
    public function updateStatus($device_id, $status, $notes = null) {
        $data = ['status' => $status];
        if ($notes !== null) {
            $data['notes'] = $notes;
        }
        return $this->update($device_id, $data);
    }
}

/**
 * Stock Article Model (stock_articles table)
 */
class StockArticleModel extends BaseModel {
    protected $table = 'stock_articles';
    
    /**
     * Find articles by category
     */
    public function findByCategory($category) {
        $sql = "SELECT * FROM {$this->table} WHERE category = :category ORDER BY name";
        return $this->db->fetchAll($sql, [':category' => $category]);
    }
    
    /**
     * Find low stock items
     */
    public function findLowStock($threshold = 5) {
        $sql = "SELECT * FROM {$this->table} WHERE quantity <= :threshold ORDER BY quantity ASC";
        return $this->db->fetchAll($sql, [':threshold' => $threshold]);
    }
    
    /**
     * Update stock quantity
     */
    public function updateQuantity($article_id, $quantity_change) {
        $sql = "UPDATE {$this->table} SET quantity = quantity + :change WHERE id = :id";
        return $this->db->execute($sql, [':change' => $quantity_change, ':id' => $article_id]);
    }
    
    /**
     * Search articles
     */
    public function search($query) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :query OR reference LIKE :query OR description LIKE :query 
                ORDER BY name";
        return $this->db->fetchAll($sql, [':query' => "%{$query}%"]);
    }
    
    /**
     * Get stock value
     */
    public function getStockValue() {
        $sql = "SELECT SUM(quantity * unit_price) as total_value FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['total_value'] ?? 0;
    }
}

/**
 * Article Model (articles table - alternative stock)
 */
class ArticleModel extends BaseModel {
    protected $table = 'articles';
    
    /**
     * Find articles by type
     */
    public function findByType($type) {
        $sql = "SELECT * FROM {$this->table} WHERE type = :type ORDER BY name";
        return $this->db->fetchAll($sql, [':type' => $type]);
    }
}

/**
 * Historique Model (historiques table)
 */
class HistoriqueModel extends BaseModel {
    protected $table = 'historiques';
    
    /**
     * Add history record
     */
    public function addRecord($user_id, $action, $entity_type, $entity_id, $details = null) {
        $data = [
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        return $this->insert($data);
    }
    
    /**
     * Get history by entity
     */
    public function getByEntity($entity_type, $entity_id) {
        $sql = "SELECT h.*, u.username 
                FROM {$this->table} h 
                LEFT JOIN admin_users u ON h.user_id = u.id 
                WHERE h.entity_type = :entity_type AND h.entity_id = :entity_id 
                ORDER BY h.created_at DESC";
        return $this->db->fetchAll($sql, [
            ':entity_type' => $entity_type,
            ':entity_id' => $entity_id
        ]);
    }
    
    /**
     * Get recent history
     */
    public function getRecent($limit = 50) {
        $sql = "SELECT h.*, u.username 
                FROM {$this->table} h 
                LEFT JOIN admin_users u ON h.user_id = u.id 
                ORDER BY h.created_at DESC 
                LIMIT :limit";
        return $this->db->fetchAll($sql, [':limit' => $limit]);
    }
}

/**
 * Acompte Model (acomptes table)
 */
class AcompteModel extends BaseModel {
    protected $table = 'acomptes';
    
    /**
     * Find acomptes by client
     */
    public function findByClient($client_id) {
        $sql = "SELECT * FROM {$this->table} WHERE client_id = :client_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':client_id' => $client_id]);
    }
    
    /**
     * Get total acomptes for client
     */
    public function getTotalByClient($client_id) {
        $sql = "SELECT SUM(amount) as total FROM {$this->table} WHERE client_id = :client_id AND status = 'active'";
        $result = $this->db->fetch($sql, [':client_id' => $client_id]);
        return $result['total'] ?? 0;
    }
}

/**
 * Acompte Commande Model (acomptes_commandes table)
 */
class AcompteCommandeModel extends BaseModel {
    protected $table = 'acomptes_commandes';
    
    /**
     * Find by commande
     */
    public function findByCommande($commande_id) {
        $sql = "SELECT * FROM {$this->table} WHERE commande_id = :commande_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':commande_id' => $commande_id]);
    }
}

/**
 * Acompte Device Model (acomptes_devices table)
 */
class AcompteDeviceModel extends BaseModel {
    protected $table = 'acomptes_devices';
    
    /**
     * Find by device
     */
    public function findByDevice($device_id) {
        $sql = "SELECT * FROM {$this->table} WHERE device_id = :device_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':device_id' => $device_id]);
    }
}

/**
 * Login Attempts Model (login_attempts table)
 */
class LoginAttemptModel extends BaseModel {
    protected $table = 'login_attempts';
    
    /**
     * Clean old attempts
     */
    public function cleanOldAttempts($hours = 24) {
        $sql = "DELETE FROM {$this->table} WHERE attempt_time < DATE_SUB(NOW(), INTERVAL :hours HOUR)";
        return $this->db->execute($sql, [':hours' => $hours]);
    }
}

/**
 * Login Logs Model (login_logs table)
 */
class LoginLogModel extends BaseModel {
    protected $table = 'login_logs';
    
    /**
     * Get recent login attempts
     */
    public function getRecent($limit = 100) {
        $sql = "SELECT * FROM {$this->table} ORDER BY login_time DESC LIMIT :limit";
        return $this->db->fetchAll($sql, [':limit' => $limit]);
    }
    
    /**
     * Get failed attempts by IP
     */
    public function getFailedByIP($ip, $hours = 24) {
        $sql = "SELECT COUNT(*) as failed_count FROM {$this->table} 
                WHERE ip_address = :ip AND result = 'failed' 
                AND login_time > DATE_SUB(NOW(), INTERVAL :hours HOUR)";
        
        $result = $this->db->fetch($sql, [':ip' => $ip, ':hours' => $hours]);
        return $result['failed_count'] ?? 0;
    }
}

/**
 * Model Factory
 */
class ModelFactory {
    private static $instances = [];
    private static $database;
    
    public static function setDatabase($database) {
        self::$database = $database;
    }
    
    /**
     * Get model instance
     */
    public static function get($model_class) {
        if (!isset(self::$instances[$model_class])) {
            if (!self::$database) {
                throw new Exception('Database not set in ModelFactory');
            }
            self::$instances[$model_class] = new $model_class(self::$database);
        }
        return self::$instances[$model_class];
    }
    
    /**
     * Get specific models
     */
    public static function user() { return self::get('UserModel'); }
    public static function client() { return self::get('ClientModel'); }
    public static function device() { return self::get('DeviceModel'); }
    public static function stockArticle() { return self::get('StockArticleModel'); }
    public static function article() { return self::get('ArticleModel'); }
    public static function historique() { return self::get('HistoriqueModel'); }
    public static function acompte() { return self::get('AcompteModel'); }
    public static function acompteCommande() { return self::get('AcompteCommandeModel'); }
    public static function acompteDevice() { return self::get('AcompteDeviceModel'); }
    public static function loginAttempt() { return self::get('LoginAttemptModel'); }
    public static function loginLog() { return self::get('LoginLogModel'); }
}