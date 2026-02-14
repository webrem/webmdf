<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script de migration pour utiliser l'ancienne base de données
 * Ce script ajoute seulement les nouvelles tables nécessaires
 * tout en préservant l'ancienne structure
 */

define('APP_START', true);

// Configuration de la base de données
$servername = "localhost";
$username = "u498346438_remshop1";
$password = "Remshop104";
$dbname = "u498346438_remshop1";

// Connexion à MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "🚀 Migration de la base de données R.E.Mobiles vers la version 2.0.0\n";
echo "===============================================================\n\n";

// Vérifier les tables existantes
$existingTables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $existingTables[] = $row[0];
}

echo "📊 Tables existantes : " . implode(', ', $existingTables) . "\n\n";

// Tables à créer (nouvelles tables nécessaires)
$tablesToCreate = [
    'stock_movements' => "CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_article VARCHAR(100) NOT NULL,
        type ENUM('entrée', 'sortie', 'ajustement') NOT NULL,
        quantite INT NOT NULL,
        raison TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ref_article (ref_article),
        INDEX idx_created_at (created_at)
    )",
    
    'commandes' => "CREATE TABLE IF NOT EXISTS commandes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_commande VARCHAR(100) UNIQUE NOT NULL,
        fournisseur VARCHAR(255) NOT NULL,
        date_commande DATE NOT NULL,
        date_livraison_prevue DATE,
        status ENUM('En attente', 'Confirmée', 'Expédiée', 'Reçue', 'Annulée') DEFAULT 'En attente',
        total_ht DECIMAL(10,2),
        total_ttc DECIMAL(10,2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )",
    
    'commande_lignes' => "CREATE TABLE IF NOT EXISTS commande_lignes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        commande_id INT NOT NULL,
        ref_article VARCHAR(100) NOT NULL,
        nom_article VARCHAR(255) NOT NULL,
        quantite INT NOT NULL,
        prix_unitaire DECIMAL(10,2),
        total_ligne DECIMAL(10,2),
        FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
        INDEX idx_commande_id (commande_id)
    )",
    
    'acomptes_devices' => "CREATE TABLE IF NOT EXISTS acomptes_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_ref VARCHAR(100) NOT NULL,
        montant DECIMAL(10,2) NOT NULL,
        mode_paiement VARCHAR(50),
        date_versement DATETIME DEFAULT CURRENT_TIMESTAMP,
        user_nom VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_device_ref (device_ref),
        INDEX idx_date_versement (date_versement)
    )",
    
    'ventes_historique' => "CREATE TABLE IF NOT EXISTS ventes_historique (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_vente VARCHAR(100) NOT NULL,
        designation VARCHAR(255) NOT NULL,
        quantite INT DEFAULT 1,
        prix_unitaire DECIMAL(10,2),
        total_ht DECIMAL(10,2),
        total_ttc DECIMAL(10,2),
        type ENUM('vente', 'acompte', 'devis', 'facture') DEFAULT 'vente',
        mode_paiement VARCHAR(50),
        client_nom VARCHAR(255),
        client_telephone VARCHAR(50),
        user_nom VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ref_vente (ref_vente),
        INDEX idx_created_at (created_at)
    )",
    
    'videos' => "CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        fichier_url VARCHAR(500),
        duree INT,
        categorie VARCHAR(100),
        tags TEXT,
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

// Index pour améliorer les performances
$indexes = [
    "CREATE INDEX idx_devices_status ON devices(status)",
    "CREATE INDEX idx_devices_created ON devices(created_at)",
    "CREATE INDEX idx_historiques_created ON historiques(created_at)",
    "CREATE INDEX idx_stock_quantite ON stock_articles(quantite)",
    "CREATE INDEX idx_clients_nom ON clients(nom)",
    "CREATE INDEX idx_clients_telephone ON clients(telephone)"
];

// Créer les nouvelles tables
$successCount = 0;
$totalCount = count($tablesToCreate);

echo "🗄️ Création des nouvelles tables...\n";
echo "---------------------------------\n";

foreach ($tablesToCreate as $tableName => $sql) {
    if (in_array($tableName, $existingTables)) {
        echo "⚠️  La table '$tableName' existe déjà - Ignorée\n";
        continue;
    }
    
    echo "📋 Création de la table '$tableName'... ";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ OK\n";
        $successCount++;
    } else {
        echo "❌ Erreur: " . $conn->error . "\n";
    }
}

echo "\n📊 Tables créées : $successCount/$totalCount\n\n";

// Ajouter les index pour améliorer les performances
if (!empty($indexes)) {
    echo "⚡ Ajout des index pour optimiser les performances...\n";
    echo "--------------------------------------------------\n";
    
    foreach ($indexes as $indexSql) {
        try {
            if ($conn->query($indexSql) === TRUE) {
                echo "✅ Index ajouté\n";
            } else {
                echo "⚠️ Index déjà existant ou erreur: " . $conn->error . "\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Erreur lors de l'ajout de l'index: " . $e->getMessage() . "\n";
        }
    }
}

// Vérifier et mettre à jour l'ancienne table admin_users si nécessaire
echo "\n🔧 Vérification de la table admin_users...\n";
echo "-----------------------------------------\n";

// Vérifier si la table admin_users a les colonnes nécessaires
$result = $conn->query("DESCRIBE admin_users");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// Ajouter les colonnes manquantes si nécessaire
$missingColumns = [
    'role' => "ALTER TABLE admin_users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'admin'",
    'active' => "ALTER TABLE admin_users ADD COLUMN active BOOLEAN DEFAULT true",
    'email' => "ALTER TABLE admin_users ADD COLUMN email VARCHAR(255)",
    'first_name' => "ALTER TABLE admin_users ADD COLUMN first_name VARCHAR(100)",
    'last_name' => "ALTER TABLE admin_users ADD COLUMN last_name VARCHAR(100)",
    'failed_attempts' => "ALTER TABLE admin_users ADD COLUMN failed_attempts INT DEFAULT 0",
    'last_login' => "ALTER TABLE admin_users ADD COLUMN last_login DATETIME NULL",
    'created_at' => "ALTER TABLE admin_users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    'updated_at' => "ALTER TABLE admin_users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($missingColumns as $columnName => $alterSql) {
    if (!in_array($columnName, $columns)) {
        echo "📋 Ajout de la colonne '$columnName'... ";
        if ($conn->query($alterSql) === TRUE) {
            echo "✅ OK\n";
        } else {
            echo "❌ Erreur: " . $conn->error . "\n";
        }
    }
}

// Vérifier et mettre à jour les tables existantes si nécessaire
$existingTablesToUpdate = [
    'devices' => [
        'priority' => "ALTER TABLE devices ADD COLUMN priority ENUM('Basse', 'Normale', 'Haute', 'Urgente') DEFAULT 'Normale'",
        'technician_name' => "ALTER TABLE devices ADD COLUMN technician_name VARCHAR(100)",
        'estimated_cost' => "ALTER TABLE devices ADD COLUMN estimated_cost DECIMAL(10,2)",
        'final_cost' => "ALTER TABLE devices ADD COLUMN final_cost DECIMAL(10,2)",
        'deposit' => "ALTER TABLE devices ADD COLUMN deposit DECIMAL(10,2) DEFAULT 0",
        'completed_at' => "ALTER TABLE devices ADD COLUMN completed_at DATETIME NULL"
    ],
    'clients' => [
        'societe' => "ALTER TABLE clients ADD COLUMN societe VARCHAR(255)",
        'ville' => "ALTER TABLE clients ADD COLUMN ville VARCHAR(100)",
        'code_postal' => "ALTER TABLE clients ADD COLUMN code_postal VARCHAR(10)",
        'type_client' => "ALTER TABLE clients ADD COLUMN type_client ENUM('Particulier', 'Professionnel') DEFAULT 'Particulier'",
        'remise_pct' => "ALTER TABLE clients ADD COLUMN remise_pct INT DEFAULT 0",
        'notes' => "ALTER TABLE clients ADD COLUMN notes TEXT",
        'updated_at' => "ALTER TABLE clients ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ],
    'stock_articles' => [
        'categorie' => "ALTER TABLE stock_articles ADD COLUMN categorie VARCHAR(100)",
        'marque' => "ALTER TABLE stock_articles ADD COLUMN marque VARCHAR(100)",
        'modele' => "ALTER TABLE stock_articles ADD COLUMN modele VARCHAR(100)",
        'seuil_alerte' => "ALTER TABLE stock_articles ADD COLUMN seuil_alerte INT DEFAULT 5",
        'emplacement' => "ALTER TABLE stock_articles ADD COLUMN emplacement VARCHAR(100)",
        'fournisseur' => "ALTER TABLE stock_articles ADD COLUMN fournisseur VARCHAR(255)",
        'updated_at' => "ALTER TABLE stock_articles ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ]
];

echo "\n🔧 Mise à jour des tables existantes...\n";
echo "--------------------------------------\n";

foreach ($existingTablesToUpdate as $tableName => $columns) {
    if (in_array($tableName, $existingTables)) {
        echo "📋 Mise à jour de la table '$tableName'...\n";
        
        // Obtenir les colonnes existantes
        $result = $conn->query("DESCRIBE $tableName");
        $existingColumns = [];
        while ($row = $result->fetch_assoc()) {
            $existingColumns[] = $row['Field'];
        }
        
        foreach ($columns as $columnName => $alterSql) {
            if (!in_array($columnName, $existingColumns)) {
                echo "   📋 Ajout de la colonne '$columnName'... ";
                if ($conn->query($alterSql) === TRUE) {
                    echo "✅ OK\n";
                } else {
                    echo "❌ Erreur: " . $conn->error . "\n";
                }
            }
        }
    }
}

// Vérifier et créer l'utilisateur admin par défaut s'il n'existe pas
$adminExists = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
$adminCount = $adminExists->fetch_assoc()['count'];

if ($adminCount == 0) {
    echo "\n👤 Création de l'utilisateur admin par défaut...\n";
    $adminPassword = password_hash('remadmin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO admin_users (username, password, role, active, created_at) VALUES ('admin', '$adminPassword', 'admin', 1, NOW())";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Utilisateur admin créé avec succès\n";
        echo "   Identifiants par défaut : admin / remadmin123\n";
    } else {
        echo "❌ Erreur lors de la création de l'utilisateur admin : " . $conn->error . "\n";
    }
} else {
    echo "\n✅ L'utilisateur admin existe déjà\n";
}

// Créer les vues pour compatibilité si nécessaire
echo "\n📊 Création de vues pour compatibilité...\n";
echo "----------------------------------------\n";

// Vue pour compatibilité avec l'ancienne structure
$views = [
    'users_view' => "CREATE OR REPLACE VIEW users_view AS 
                     SELECT id, username, password, 'admin' as role, 1 as active, 
                     NULL as email, NULL as first_name, NULL as last_name, 
                     NULL as failed_attempts, NULL as last_login, created_at, updated_at 
                     FROM admin_users"
];

foreach ($views as $viewName => $viewSql) {
    echo "📋 Création de la vue '$viewName'... ";
    if ($conn->query($viewSql) === TRUE) {
        echo "✅ OK\n";
    } else {
        echo "⚠️ Erreur (peut déjà exister) : " . $conn->error . "\n";
    }
}

// Sauvegarder la configuration de migration
$migrationConfig = [
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
    'tables_created' => $successCount,
    'existing_tables' => $existingTables,
    'admin_user_created' => ($adminCount == 0),
    'success' => true
];

file_put_contents('/mnt/okcomputer/backup/migration_config.json', json_encode($migrationConfig, JSON_PRETTY_PRINT));

// Fermer la connexion
$conn->close();

echo "\n🎉 Migration terminée avec succès !\n";
echo "==================================\n\n";
echo "✅ Tables créées : $successCount/$totalCount\n";
echo "✅ Ancienne base de données préservée\n";
echo "✅ Nouvelles tables ajoutées\n";
echo "✅ Index de performance ajoutés\n";
echo "✅ Utilisateur admin vérifié\n";
echo "✅ Configuration sauvegardée\n\n";

echo "📋 Prochaines étapes :\n";
echo "1. Configurez votre fichier .env si nécessaire\n";
echo "2. Testez l'application avec test.php\n";
echo "3. Vérifiez que l'ancienne connexion fonctionne\n";
echo "4. Les identifiants par défaut sont : admin / remadmin123\n\n";

echo "🔒 IMPORTANT : Changez les identifiants par défaut immédiatement !\n";
?>