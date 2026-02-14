<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Modèle Client pour R.E.Mobiles
 * Gère les opérations liées aux clients
 */

require_once 'BaseModel.php';

class Client extends BaseModel {
    protected $table = 'clients';
    protected $fillable = [
        'nom',
        'societe',
        'telephone',
        'email',
        'adresse',
        'ville',
        'code_postal',
        'type_client',
        'remise_pct',
        'notes'
    ];
    
    protected $casts = [
        'remise_pct' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Rechercher des clients
     */
    public function searchClients($searchTerm, $limit = 10) {
        $sql = "SELECT id, nom, telephone, email, remise_pct FROM {$this->table} 
                WHERE nom LIKE :search OR telephone LIKE :search OR email LIKE :search 
                ORDER BY nom ASC LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            ':search' => "%$searchTerm%",
            ':limit' => $limit
        ]);
    }
    
    /**
     * Obtenir les statistiques des clients
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total_clients,
                COUNT(CASE WHEN type_client = 'Professionnel' THEN 1 END) as professionnels,
                COUNT(CASE WHEN type_client = 'Particulier' THEN 1 END) as particuliers,
                AVG(remise_pct) as remise_moyenne
                FROM {$this->table}";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Obtenir les clients avec le plus d'activité
     */
    public function getTopClients($limit = 10) {
        $sql = "SELECT c.*, COUNT(d.id) as total_devices
                FROM {$this->table} c
                LEFT JOIN devices d ON c.nom = d.client_name
                GROUP BY c.id
                ORDER BY total_devices DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [':limit' => $limit]);
    }
    
    /**
     * Vérifier si un client existe
     */
    public function exists($nom, $telephone) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nom = :nom AND telephone = :telephone";
        $result = $this->db->fetch($sql, [
            ':nom' => $nom,
            ':telephone' => $telephone
        ]);
        
        return $result['count'] > 0;
    }
    
    /**
     * Obtenir ou créer un client
     */
    public function firstOrCreate(array $data) {
        // Vérifier si le client existe déjà
        $client = $this->findBy('telephone', $data['telephone']);
        
        if ($client) {
            return $client;
        }
        
        // Créer un nouveau client
        $id = $this->create($data);
        return $this->find($id);
    }
    
    /**
     * Importer des clients depuis CSV
     */
    public function importFromCSV(array $data) {
        $this->beginTransaction();
        
        try {
            $imported = 0;
            $errors = [];
            
            foreach ($data as $index => $row) {
                // Valider les données requises
                if (empty($row['nom']) || empty($row['telephone'])) {
                    $errors[] = "Ligne $index: Nom et téléphone requis";
                    continue;
                }
                
                // Préparer les données
                $clientData = [
                    'nom' => $row['nom'],
                    'telephone' => $row['telephone'],
                    'societe' => $row['societe'] ?? '',
                    'email' => $row['email'] ?? '',
                    'adresse' => $row['adresse'] ?? '',
                    'ville' => $row['ville'] ?? '',
                    'code_postal' => $row['code_postal'] ?? '',
                    'type_client' => $row['type_client'] ?? 'Particulier',
                    'remise_pct' => (int)($row['remise_pct'] ?? 0),
                    'notes' => $row['notes'] ?? ''
                ];
                
                // Créer le client s'il n'existe pas
                if (!$this->exists($clientData['nom'], $clientData['telephone'])) {
                    $this->create($clientData);
                    $imported++;
                }
            }
            
            $this->commit();
            
            return [
                'imported' => $imported,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}