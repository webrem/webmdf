<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Classe de base pour tous les modèles
 * Fournit les opérations CRUD de base
 */

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $casts = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Récupérer tous les enregistrements
     */
    public function all($orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupérer un enregistrement par ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Récupérer un enregistrement par une colonne spécifique
     */
    public function findBy($column, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE $column = :value LIMIT 1";
        return $this->db->fetch($sql, [':value' => $value]);
    }
    
    /**
     * Récupérer plusieurs enregistrements par une colonne
     */
    public function where($column, $value, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table} WHERE $column = :value";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        return $this->db->fetchAll($sql, [':value' => $value]);
    }
    
    /**
     * Créer un nouvel enregistrement
     */
    public function create(array $data) {
        // Filtrer les données selon les champs fillable
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            throw new InvalidArgumentException("Aucune donnée valide à insérer");
        }
        
        // Ajouter les timestamps
        $filteredData['created_at'] = date('Y-m-d H:i:s');
        $filteredData['updated_at'] = date('Y-m-d H:i:s');
        
        // Construire la requête
        $columns = array_keys($filteredData);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->query($sql, $filteredData);
        
        return $this->db->getConnection()->lastInsertId();
    }
    
    /**
     * Mettre à jour un enregistrement
     */
    public function update($id, array $data) {
        // Filtrer les données selon les champs fillable
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            throw new InvalidArgumentException("Aucune donnée valide à mettre à jour");
        }
        
        // Ajouter le timestamp de mise à jour
        $filteredData['updated_at'] = date('Y-m-d H:i:s');
        
        // Construire la requête
        $setParts = [];
        foreach ($filteredData as $column => $value) {
            $setParts[] = "$column = :$column";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE {$this->primaryKey} = :id";
        
        // Ajouter l'ID aux paramètres
        $filteredData['id'] = $id;
        
        return $this->db->query($sql, $filteredData);
    }
    
    /**
     * Supprimer un enregistrement
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql, [':id' => $id]);
    }
    
    /**
     * Supprimer plusieurs enregistrements
     */
    public function deleteMultiple(array $ids) {
        if (empty($ids)) {
            return 0;
        }
        
        $placeholders = array_map(fn($i) => ":id$i", array_keys($ids));
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} IN (" . implode(', ', $placeholders) . ")";
        
        $params = [];
        foreach ($ids as $i => $id) {
            $params[":id$i"] = $id;
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Compter le nombre d'enregistrements
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "$column = :$column";
                $params[":$column"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Recherche avec pagination
     */
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        // Construire la clause WHERE
        $where = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    // Pour les conditions IN
                    $placeholders = array_map(fn($i) => ":{$column}_$i", array_keys($value));
                    $whereParts[] = "$column IN (" . implode(', ', $placeholders) . ")";
                    foreach ($value as $i => $val) {
                        $params[":{$column}_$i"] = $val;
                    }
                } else {
                    $whereParts[] = "$column = :$column";
                    $params[":$column"] = $value;
                }
            }
            $where = " WHERE " . implode(' AND ', $whereParts);
        }
        
        // Requête pour les données
        $sql = "SELECT * FROM {$this->table}$where";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $data = $this->db->fetchAll($sql, $params);
        
        // Compter le total pour la pagination
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}$where";
        $countParams = array_filter($params, fn($key) => !in_array($key, [':limit', ':offset']), ARRAY_FILTER_USE_KEY);
        $totalResult = $this->db->fetch($countSql, $countParams);
        $total = (int)($totalResult['total'] ?? 0);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Recherche plein texte
     */
    public function search($searchTerm, $searchFields, $conditions = []) {
        if (empty($searchFields)) {
            return [];
        }
        
        $whereParts = [];
        $params = [];
        
        // Construire la recherche plein texte
        $searchParts = [];
        foreach ($searchFields as $field) {
            $searchParts[] = "$field LIKE :search";
        }
        $whereParts[] = "(" . implode(' OR ', $searchParts) . ")";
        $params[':search'] = "%$searchTerm%";
        
        // Ajouter les conditions supplémentaires
        foreach ($conditions as $column => $value) {
            $whereParts[] = "$column = :$column";
            $params[":$column"] = $value;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereParts);
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Filtrer les données selon les champs fillable
     */
    protected function filterFillable(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Appliquer les casts de type
     */
    protected function castValue($value, $type) {
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                return (bool)$value;
            case 'string':
                return (string)$value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : (array)$value;
            case 'datetime':
                return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
            default:
                return $value;
        }
    }
    
    /**
     * Obtenir le nom de la table
     */
    public function getTable() {
        return $this->table;
    }
    
    /**
     * Obtenir la clé primaire
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }
    
    /**
     * Obtenir les champs fillable
     */
    public function getFillable() {
        return $this->fillable;
    }
    
    /**
     * Commencer une transaction
     */
    public function beginTransaction() {
        return $this->db->getConnection()->beginTransaction();
    }
    
    /**
     * Valider la transaction
     */
    public function commit() {
        return $this->db->getConnection()->commit();
    }
    
    /**
     * Annuler la transaction
     */
    public function rollback() {
        return $this->db->getConnection()->rollBack();
    }
}