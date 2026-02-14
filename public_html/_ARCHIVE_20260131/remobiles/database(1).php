<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Gestionnaire de base de données avec PDO - Adapté pour l'ancienne configuration
 */

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->config = require 'config.php';
        $this->connect();
    }
    
    /**
     * Obtenir l'instance singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Se connecter à la base de données avec l'ancienne configuration
     */
    private function connect() {
        $db = $this->config['database'];
        
        try {
            // Utiliser l'ancienne configuration directement
            $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
            $options = $db['options'];
            
            $this->connection = new PDO($dsn, $db['username'], $db['password'], $options);
            
            // Forcer le mode UTF-8
            $this->connection->exec("SET NAMES {$db['charset']} COLLATE {$db['collation']}");
            
        } catch (PDOException $e) {
            error_log("Erreur de connexion DB: " . $e->getMessage());
            
            // En production, ne pas exposer les détails de l'erreur
            if (!defined('APP_DEBUG') || !APP_DEBUG) {
                die("Erreur de connexion à la base de données. Veuillez vérifier votre configuration.");
            } else {
                die("Erreur de connexion DB: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Obtenir la connexion PDO
     */
    public function getConnection() {
        // Vérifier si la connexion est toujours active
        if (!$this->connection || $this->connection->query("SELECT 1") === false) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Exécuter une requête avec des paramètres
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage() . " - SQL: " . $sql);
            
            if (defined('APP_DEBUG') && APP_DEBUG) {
                throw $e;
            } else {
                throw new RuntimeException("Erreur lors de l'exécution de la requête");
            }
        }
    }
    
    /**
     * Récupérer un seul enregistrement
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer plusieurs enregistrements
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer une valeur unique
     */
    public function fetchColumn($sql, $params = [], $column = 0) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * Insérer des données et retourner l'ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Mettre à jour des données
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $where";
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Supprimer des données
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Compter le nombre d'enregistrements
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        return (int)$this->fetchColumn($sql, $params);
    }
    
    /**
     * Vérifier si une table existe
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $result = $this->fetch($sql, [':table' => $table]);
        return !empty($result);
    }
    
    /**
     * Obtenir les colonnes d'une table
     */
    public function getTableColumns($table) {
        $sql = "SHOW COLUMNS FROM $table";
        return $this->fetchAll($sql);
    }
    
    /**
     * Obtenir les informations de la table
     */
    public function getTableInfo($table) {
        $sql = "DESCRIBE $table";
        return $this->fetchAll($sql);
    }
    
    /**
     * Commencer une transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Valider la transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Annuler la transaction
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Obtenir le dernier ID inséré
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Obtenir le nombre de lignes affectées
     */
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }
    
    /**
     * Échapper une valeur pour une requête (déprécié, utiliser prepared statements)
     */
    public function quote($value) {
        return $this->getConnection()->quote($value);
    }
    
    /**
     * Exécuter une requête brute (utiliser avec précaution)
     */
    public function exec($sql) {
        try {
            return $this->getConnection()->exec($sql);
        } catch (PDOException $e) {
            error_log("Erreur exec: " . $e->getMessage());
            throw $e;
        }
    }
}