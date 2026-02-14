<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Modèle Device pour R.E.Mobiles
 * Gère les opérations liées aux appareils
 */

require_once 'BaseModel.php';

class Device extends BaseModel {
    protected $table = 'devices';
    protected $fillable = [
        'ref',
        'marque',
        'modele',
        'imei',
        'client_name',
        'client_phone',
        'problem_description',
        'diagnostic',
        'status',
        'priority',
        'technician_name',
        'estimated_cost',
        'final_cost',
        'deposit',
        'completed_at'
    ];
    
    protected $casts = [
        'estimated_cost' => 'float',
        'final_cost' => 'float',
        'deposit' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    /**
     * Récupérer les appareils par statut
     */
    public function getByStatus($status) {
        $sql = "SELECT d.*, c.remise_pct 
                FROM {$this->table} d
                LEFT JOIN clients c ON d.client_name = c.nom AND d.client_phone = c.telephone
                WHERE d.status = :status
                ORDER BY d.priority DESC, d.created_at ASC";
        
        return $this->db->fetchAll($sql, [':status' => $status]);
    }
    
    /**
     * Récupérer les appareils d'un technicien
     */
    public function getByTechnician($technicianName) {
        $sql = "SELECT * FROM {$this->table} WHERE technician_name = :technician ORDER BY priority DESC, created_at ASC";
        return $this->db->fetchAll($sql, [':technician' => $technicianName]);
    }
    
    /**
     * Récupérer les statistiques des appareils
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'En attente' THEN 1 END) as en_attente,
                COUNT(CASE WHEN status = 'En cours' THEN 1 END) as en_cours,
                COUNT(CASE WHEN status = 'Terminé' THEN 1 END) as termines,
                COUNT(CASE WHEN status = 'Livré' THEN 1 END) as livres,
                COUNT(CASE WHEN DATE(completed_at) = CURDATE() THEN 1 END) as termines_aujourd_hui,
                SUM(deposit) as total_deposits,
                AVG(final_cost) as cout_moyen
                FROM {$this->table}";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Mettre à jour le statut d'un appareil
     */
    public function updateStatus($id, $status, $technician = null) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($technician) {
            $data['technician_name'] = $technician;
        }
        
        if ($status === 'Terminé') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Ajouter un acompte
     */
    public function addDeposit($id, $amount) {
        $device = $this->find($id);
        if (!$device) {
            return false;
        }
        
        $newDeposit = $device['deposit'] + $amount;
        return $this->update($id, ['deposit' => $newDeposit]);
    }
    
    /**
     * Rechercher des appareils
     */
    public function search($searchTerm, $filters = []) {
        $whereParts = [];
        $params = [];
        
        // Recherche dans les champs textuels
        $searchParts = [];
        $searchFields = ['ref', 'marque', 'modele', 'client_name', 'problem_description'];
        
        foreach ($searchFields as $field) {
            $searchParts[] = "$field LIKE :search";
        }
        
        if (!empty($searchParts)) {
            $whereParts[] = "(" . implode(' OR ', $searchParts) . ")";
            $params[':search'] = "%$searchTerm%";
        }
        
        // Ajouter les filtres
        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                if ($value !== null && $value !== '') {
                    $whereParts[] = "$column = :$column";
                    $params[":$column"] = $value;
                }
            }
        }
        
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtenir les appareils en retard
     */
    public function getOverdueDevices($days = 7) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status NOT IN ('Terminé', 'Livré', 'Annulé') 
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                ORDER BY created_at ASC";
        
        return $this->db->fetchAll($sql, [':days' => $days]);
    }
    
    /**
     * Obtenir les statistiques par technicien
     */
    public function getStatsByTechnician() {
        $sql = "SELECT 
                technician_name,
                COUNT(*) as total_devices,
                COUNT(CASE WHEN status = 'En cours' THEN 1 END) as en_cours,
                COUNT(CASE WHEN status = 'Terminé' THEN 1 END) as termines,
                AVG(final_cost) as cout_moyen,
                SUM(final_cost) as chiffre_affaire
                FROM {$this->table}
                WHERE technician_name IS NOT NULL
                GROUP BY technician_name
                ORDER BY total_devices DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Obtenir les statistiques mensuelles
     */
    public function getMonthlyStats($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "SELECT 
                MONTH(created_at) as month,
                MONTHNAME(created_at) as month_name,
                COUNT(*) as total_devices,
                COUNT(CASE WHEN status = 'Terminé' THEN 1 END) as completed_devices,
                SUM(final_cost) as total_revenue,
                AVG(final_cost) as avg_cost
                FROM {$this->table}
                WHERE YEAR(created_at) = :year
                GROUP BY MONTH(created_at)
                ORDER BY month";
        
        return $this->db->fetchAll($sql, [':year' => $year]);
    }
}