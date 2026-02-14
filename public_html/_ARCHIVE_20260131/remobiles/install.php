<?php
/**
 * Installation et mise à jour de la base de données R.E.Mobiles
 * Version corrigée – full clean install
 */

define('APP_START', true);

// Configuration de la base
$dbConfig = [
    'host' => 'localhost',
    'username' => 'u498346438_remshop1',
    'password' => 'Remshop104',
    'dbname' => 'u498346438_remshop1'
];

// Connexion à MySQL
$conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password']);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Erreur de connexion MySQL: ' . $conn->connect_error]));
}

// Création de la base si absente
if (!$conn->select_db($dbConfig['dbname'])) {
    $sql = "CREATE DATABASE " . $dbConfig['dbname'] . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        die(json_encode(['success' => false, 'error' => 'Erreur création base: ' . $conn->error]));
    }
    $conn->select_db($dbConfig['dbname']);
}

$conn->set_charset("utf8mb4");

// 🧹 Suppression automatique des anciennes tables
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DROP TABLE IF EXISTS users, clients, devices, device_parts, historiques, stock, stock_movements, commandes, commande_lignes, acomptes_devices, ventes_historique, videos");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 🧱 Script SQL
$sqlScript = "
-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    email VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    active BOOLEAN DEFAULT true,
    failed_attempts INT DEFAULT 0,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des clients
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    telephone VARCHAR(50),
    email VARCHAR(255),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des appareils
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(100) UNIQUE NOT NULL,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    imei VARCHAR(20),
    client_name VARCHAR(255) NOT NULL,
    client_phone VARCHAR(50),
    problem_description TEXT,
    diagnostic TEXT,
    status ENUM('En attente', 'En cours', 'Terminé', 'Livré', 'Annulé') DEFAULT 'En attente',
    priority ENUM('Basse', 'Normale', 'Haute', 'Urgente') DEFAULT 'Normale',
    technician_name VARCHAR(100),
    estimated_cost DECIMAL(10,2),
    final_cost DECIMAL(10,2),
    deposit DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des pièces appareils
CREATE TABLE device_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    part_name VARCHAR(255) NOT NULL,
    part_reference VARCHAR(100),
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    supplier VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table historiques de prix
CREATE TABLE historiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    piece VARCHAR(255) NOT NULL,
    prix_achat DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL,
    main_oeuvre DECIMAL(10,2) NOT NULL,
    client_nom VARCHAR(255) NOT NULL,
    client_tel VARCHAR(50) NOT NULL,
    doc_type ENUM('DEVIS', 'PROFORMA', 'FACTURE') NOT NULL,
    prix_final DECIMAL(10,2) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table stock
CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_article VARCHAR(100) UNIQUE NOT NULL,
    nom_article VARCHAR(255) NOT NULL,
    categorie VARCHAR(100),
    marque VARCHAR(100),
    modele VARCHAR(100),
    quantite INT DEFAULT 0,
    prix_achat DECIMAL(10,2),
    prix_vente DECIMAL(10,2),
    seuil_alerte INT DEFAULT 5,
    emplacement VARCHAR(100),
    fournisseur VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table mouvements stock
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_article VARCHAR(100) NOT NULL,
    type ENUM('entrée', 'sortie', 'ajustement') NOT NULL,
    quantite INT NOT NULL,
    raison TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ref_article) REFERENCES stock(ref_article) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table commandes
CREATE TABLE commandes (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lignes de commande
CREATE TABLE commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    ref_article VARCHAR(100) NOT NULL,
    nom_article VARCHAR(255) NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2),
    total_ligne DECIMAL(10,2),
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table acomptes
CREATE TABLE acomptes_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    mode_paiement VARCHAR(50),
    date_versement DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_nom VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table ventes historique
CREATE TABLE ventes_historique (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table vidéos
CREATE TABLE videos (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index performances
CREATE INDEX idx_devices_status ON devices(status);
CREATE INDEX idx_devices_created ON devices(created_at);
CREATE INDEX idx_historiques_created ON historiques(created_at);
CREATE INDEX idx_stock_quantite ON stock(quantite);
CREATE INDEX idx_commandes_status ON commandes(status);
CREATE INDEX idx_ventes_created ON ventes_historique(created_at);
";

// ▶️ Exécution
$queries = array_filter(array_map('trim', explode(';', $sqlScript)));
$success = true;
$errors = [];

foreach ($queries as $query) {
    if (empty($query)) continue;
    if (!$conn->query($query)) {
        $errors[] = "Erreur SQL: " . $conn->error;
        $success = false;
    }
}

// 👑 Création admin
$adminPassword = password_hash('remadmin123', PASSWORD_DEFAULT);
$insertAdmin = "INSERT INTO users (username, password, role, first_name, last_name)
                VALUES ('admin', ?, 'admin', 'Administrateur', 'Principal')";
$stmt = $conn->prepare($insertAdmin);
$stmt->bind_param('s', $adminPassword);
$stmt->execute();
$stmt->close();

$conn->close();

// Réponse JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'message' => $success ? 'Base de données installée avec succès !' : 'Erreurs rencontrées'
    ]);
    exit;
}
?>
