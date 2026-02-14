<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Modèle Stock pour R.E.Mobiles
 * Gère les opérations liées au stock
 */

require_once 'BaseModel.php';

class Stock extends BaseModel {
    protected $table = 'stock';
    protected $fillable = [
        'ref_article',
        'nom_article',
        'categorie',
        'marque',
        'modele',
        'quantite',
        'prix_achat',
        'prix_vente',
        'seuil_alerte',
        'emplacement',
        'fournisseur'
    ];
    
    protected $casts = [
        'quantite' => 'int',
        'prix_achat' => 'float',
        'prix_vente' => 'float',
        'seuil_alerte' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Récupérer les articles avec stock faible
     */
    public function getLowStockItems() {
        $sql = "SELECT * FROM {$this->table} WHERE quantite <= seuil_alerte ORDER BY quantite ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupérer les articles en rupture de stock
     */
    public function getOutOfStockItems() {
        $sql = "SELECT * FROM {$this->table} WHERE quantite = 0 ORDER BY nom_article ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Rechercher des articles
     */
    public function searchItems($searchTerm, $filters = []) {
        $whereParts = [];
        $params = [];
        
        // Recherche dans les champs textuels
        $searchFields = ['ref_article', 'nom_article', 'categorie', 'marque', 'modele'];
        $searchParts = [];
        
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
        
        $sql .= " ORDER BY nom_article ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Mettre à jour le stock d'un article
     */
    public function updateStock($id, $quantity, $movementType = 'ajustement', $reason = '') {
        $article = $this->find($id);
        if (!$article) {
            return false;
        }
        
        $newQuantity = max(0, $article['quantite'] + $quantity);
        
        $this->beginTransaction();
        
        try {
            // Mettre à jour la quantité
            $this->update($id, ['quantite' => $newQuantity]);
            
            // Enregistrer le mouvement
            $this->recordMovement($id, $movementType, $quantity, $reason);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Enregistrer un mouvement de stock
     */
    public function recordMovement($articleId, $type, $quantity, $reason = '', $userId = null) {
        $sql = "INSERT INTO stock_movements (ref_article, type, quantite, raison, user_id, created_at) 
                VALUES (:ref_article, :type, :quantite, :raison, :user_id, NOW())";
        
        $article = $this->find($articleId);
        if (!$article) {
            return false;
        }
        
        return $this->db->query($sql, [
            ':ref_article' => $article['ref_article'],
            ':type' => $type,
            ':quantite' => $quantity,
            ':raison' => $reason,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Obtenir l'historique des mouvements d'un article
     */
    public function getMovementHistory($articleId, $limit = 50) {
        $sql = "SELECT sm.*, u.username 
                FROM stock_movements sm
                LEFT JOIN users u ON sm.user_id = u.id
                WHERE sm.ref_article = (SELECT ref_article FROM {$this->table} WHERE id = :id)
                ORDER BY sm.created_at DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            ':id' => $articleId,
            ':limit' => $limit
        ]);
    }
    
    /**
     * Obtenir les statistiques du stock
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total_articles,
                SUM(quantite) as total_quantite,
                SUM(quantite * prix_achat) as valeur_achat,
                SUM(quantite * prix_vente) as valeur_vente,
                COUNT(CASE WHEN quantite = 0 THEN 1 END) as rupture_stock,
                COUNT(CASE WHEN quantite <= seuil_alerte THEN 1 END) as stock_faible,
                AVG(prix_vente - prix_achat) as marge_moyenne
                FROM {$this->table}";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Obtenir les statistiques par catégorie
     */
    public function getStatsByCategory() {
        $sql = "SELECT 
                categorie,
                COUNT(*) as nb_articles,
                SUM(quantite) as total_quantite,
                AVG(prix_vente) as prix_moyen,
                SUM(quantite * prix_vente) as valeur_stock
                FROM {$this->table}
                GROUP BY categorie
                ORDER BY valeur_stock DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Calculer la valeur totale du stock
     */
    public function getTotalValue() {
        $sql = "SELECT SUM(quantite * prix_vente) as total_value FROM {$this->table}";
        $result = $this->db->fetch($sql);
        
        return (float)($result['total_value'] ?? 0);
    }
    
    /**
     * Obtenir les alertes de stock
     */
    public function getAlerts() {
        $alerts = [];
        
        // Stock faible
        $lowStock = $this->getLowStockItems();
        if (!empty($lowStock)) {
            $alerts['low_stock'] = $lowStock;
        }
        
        // Rupture de stock
        $outOfStock = $this->getOutOfStockItems();
        if (!empty($outOfStock)) {
            $alerts['out_of_stock'] = $outOfStock;
        }
        
        return $alerts;
    }
    
    /**
     * Importer des articles depuis CSV
     */
    public function importFromCSV(array $data) {
        $this->beginTransaction();
        
        try {
            $imported = 0;
            $errors = [];
            $updated = 0;
            
            foreach ($data as $index => $row) {
                // Valider les données requises
                if (empty($row['ref_article']) || empty($row['nom_article'])) {
                    $errors[] = "Ligne $index: Référence et nom de l'article requis";
                    continue;
                }
                
                // Vérifier si l'article existe déjà
                $existing = $this->findBy('ref_article', $row['ref_article']);
                
                if ($existing) {
                    // Mettre à jour l'article existant
                    $updateData = [
                        'nom_article' => $row['nom_article'],
                        'categorie' => $row['categorie'] ?? $existing['categorie'],
                        'marque' => $row['marque'] ?? $existing['marque'],
                        'modele' => $row['modele'] ?? $existing['modele'],
                        'prix_achat' => isset($row['prix_achat']) ? (float)$row['prix_achat'] : $existing['prix_achat'],
                        'prix_vente' => isset($row['prix_vente']) ? (float)$row['prix_vente'] : $existing['prix_vente'],
                        'fournisseur' => $row['fournisseur'] ?? $existing['fournisseur']
                    ];
                    
                    // Ajouter la quantité si spécifiée
                    if (isset($row['quantite'])) {
                        $updateData['quantite'] = (int)$row['quantite'] + $existing['quantite'];
                    }
                    
                    $this->update($existing['id'], $updateData);
                    $updated++;
                } else {
                    // Créer un nouvel article
                    $articleData = [
                        'ref_article' => $row['ref_article'],
                        'nom_article' => $row['nom_article'],
                        'categorie' => $row['categorie'] ?? 'Autre',
                        'marque' => $row['marque'] ?? '',
                        'modele' => $row['modele'] ?? '',
                        'quantite' => (int)($row['quantite'] ?? 0),
                        'prix_achat' => (float)($row['prix_achat'] ?? 0),
                        'prix_vente' => (float)($row['prix_vente'] ?? 0),
                        'seuil_alerte' => (int)($row['seuil_alerte'] ?? 5),
                        'emplacement' => $row['emplacement'] ?? '',
                        'fournisseur' => $row['fournisseur'] ?? ''
                    ];
                    
                    $this->create($articleData);
                    $imported++;
                }
            }
            
            $this->commit();
            
            return [
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}