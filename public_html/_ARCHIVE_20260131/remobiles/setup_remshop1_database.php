<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Setup script for u498346438_remshop1 database
 * Creates complete database structure for independent testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_config = [
    'host' => 'localhost',
    'name' => 'u498346438_remshop1',
    'user' => 'u498346438_remshop1',
    'pass' => 'Remshop104'
];

// Color codes for terminal output
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";

// Header
printHeader();

try {
    // Connect to database
    echo COLOR_BLUE . "Connecting to database..." . COLOR_RESET . "\n";
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo COLOR_GREEN . "✓ Connected successfully to u498346438_remshop1 database" . COLOR_RESET . "\n\n";
    
    // Create tables in correct order (respecting foreign keys)
    $tables = getTableDefinitions();
    
    foreach ($tables as $table_name => $sql) {
        echo COLOR_YELLOW . "Creating table: $table_name" . COLOR_RESET . "... ";
        
        try {
            $pdo->exec($sql);
            echo COLOR_GREEN . "✓ OK" . COLOR_RESET . "\n";
        } catch (Exception $e) {
            echo COLOR_RED . "✗ FAILED" . COLOR_RESET . "\n";
            echo COLOR_RED . "Error: " . $e->getMessage() . COLOR_RESET . "\n";
        }
    }
    
    // Insert default data
    echo "\n" . COLOR_BLUE . "Inserting default data..." . COLOR_RESET . "\n";
    insertDefaultData($pdo);
    
    // Create indexes for better performance
    echo "\n" . COLOR_BLUE . "Creating indexes..." . COLOR_RESET . "\n";
    createIndexes($pdo);
    
    echo "\n" . COLOR_GREEN . "✓ Database setup completed successfully!" . COLOR_RESET . "\n";
    echo COLOR_BLUE . "You can now use the u498346438_remshop1 database for testing." . COLOR_RESET . "\n\n";
    
    // Show summary
    showDatabaseSummary($pdo);
    
} catch (Exception $e) {
    echo COLOR_RED . "✗ Database connection failed: " . $e->getMessage() . COLOR_RESET . "\n";
    exit(1);
}

function printHeader() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                    R.E.Mobiles Database Setup                                ║
║                    ─────────────────────────                                 ║
║                                                                              ║
║  Setting up complete database structure for u498346438_remshop1                         ║
║  This creates an independent system for testing.                             ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n\n";
}

function getTableDefinitions() {
    return [
        // User management tables
        'user_roles' => "CREATE TABLE IF NOT EXISTS user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            permissions JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            phone VARCHAR(20),
            role_id INT DEFAULT 1,
            is_active BOOLEAN DEFAULT true,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'login_attempts' => "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            ip_address VARCHAR(45),
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT false
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'user_sessions' => "CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Client management
        'clients' => "CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_number VARCHAR(20) UNIQUE,
            company_name VARCHAR(100),
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            email VARCHAR(100),
            phone VARCHAR(20),
            mobile VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            postal_code VARCHAR(10),
            country VARCHAR(50) DEFAULT 'France',
            notes TEXT,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'client_contacts' => "CREATE TABLE IF NOT EXISTS client_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            name VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            role VARCHAR(50),
            is_primary BOOLEAN DEFAULT false,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Device and repair management
        'device_brands' => "CREATE TABLE IF NOT EXISTS device_brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'device_models' => "CREATE TABLE IF NOT EXISTS device_models (
            id INT AUTO_INCREMENT PRIMARY KEY,
            brand_id INT,
            name VARCHAR(100) NOT NULL,
            device_type VARCHAR(50),
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (brand_id) REFERENCES device_brands(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'devices' => "CREATE TABLE IF NOT EXISTS devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_number VARCHAR(20) UNIQUE,
            client_id INT,
            brand_id INT,
            model_id INT,
            device_type VARCHAR(50),
            serial_number VARCHAR(50),
            imei VARCHAR(20),
            color VARCHAR(30),
            storage VARCHAR(20),
            condition_note TEXT,
            password VARCHAR(50),
            accessories TEXT,
            problem_description TEXT,
            estimated_price DECIMAL(10,2),
            is_reparable BOOLEAN DEFAULT true,
            status VARCHAR(20) DEFAULT 'en_attente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
            FOREIGN KEY (brand_id) REFERENCES device_brands(id) ON DELETE SET NULL,
            FOREIGN KEY (model_id) REFERENCES device_models(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'repair_status' => "CREATE TABLE IF NOT EXISTS repair_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            color VARCHAR(7) DEFAULT '#000000',
            is_active BOOLEAN DEFAULT true,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'repairs' => "CREATE TABLE IF NOT EXISTS repairs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            repair_number VARCHAR(20) UNIQUE,
            device_id INT NOT NULL,
            client_id INT,
            technician_id INT,
            status_id INT DEFAULT 1,
            priority VARCHAR(20) DEFAULT 'normal',
            problem_description TEXT,
            diagnosis TEXT,
            solution TEXT,
            estimated_cost DECIMAL(10,2),
            final_cost DECIMAL(10,2),
            estimated_time INT,
            actual_time INT,
            warranty_period INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
            FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (status_id) REFERENCES repair_status(id) ON DELETE SET DEFAULT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'repair_parts' => "CREATE TABLE IF NOT EXISTS repair_parts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            repair_id INT NOT NULL,
            stock_article_id INT,
            part_name VARCHAR(100),
            quantity INT DEFAULT 1,
            unit_price DECIMAL(10,2),
            total_price DECIMAL(10,2),
            is_used BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
            FOREIGN KEY (stock_article_id) REFERENCES stock_articles(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Stock management
        'stock_categories' => "CREATE TABLE IF NOT EXISTS stock_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES stock_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'suppliers' => "CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact_person VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            postal_code VARCHAR(10),
            country VARCHAR(50) DEFAULT 'France',
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'stock_articles' => "CREATE TABLE IF NOT EXISTS stock_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_number VARCHAR(50) UNIQUE,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            category_id INT,
            supplier_id INT,
            brand VARCHAR(50),
            model VARCHAR(50),
            barcode VARCHAR(50),
            quantity_in_stock INT DEFAULT 0,
            min_stock_level INT DEFAULT 0,
            max_stock_level INT DEFAULT 0,
            purchase_price DECIMAL(10,2),
            selling_price DECIMAL(10,2),
            tax_rate DECIMAL(5,2) DEFAULT 20.00,
            location VARCHAR(50),
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES stock_categories(id) ON DELETE SET NULL,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'articles' => "CREATE TABLE IF NOT EXISTS articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            designation VARCHAR(100) NOT NULL,
            description TEXT,
            prix_achat DECIMAL(10,2),
            prix_vente DECIMAL(10,2),
            quantite_stock INT DEFAULT 0,
            seuil_minimum INT DEFAULT 0,
            categorie VARCHAR(50),
            fournisseur VARCHAR(100),
            reference_fournisseur VARCHAR(50),
            emplacement VARCHAR(50),
            est_actif BOOLEAN DEFAULT true,
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'stock_movements' => "CREATE TABLE IF NOT EXISTS stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id INT NOT NULL,
            movement_type ENUM('entry', 'exit', 'adjustment') NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2),
            total_value DECIMAL(10,2),
            reason VARCHAR(50),
            reference_document VARCHAR(50),
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (article_id) REFERENCES stock_articles(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Financial management
        'quotes' => "CREATE TABLE IF NOT EXISTS quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_number VARCHAR(20) UNIQUE,
            client_id INT,
            date_created DATE,
            date_validity DATE,
            status VARCHAR(20) DEFAULT 'draft',
            total_ht DECIMAL(12,2),
            total_tva DECIMAL(12,2),
            total_ttc DECIMAL(12,2),
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'quote_items' => "CREATE TABLE IF NOT EXISTS quote_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_id INT NOT NULL,
            description TEXT NOT NULL,
            quantity INT DEFAULT 1,
            unit_price DECIMAL(10,2),
            total_price DECIMAL(10,2),
            tva_rate DECIMAL(5,2) DEFAULT 20.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'invoices' => "CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(20) UNIQUE,
            client_id INT,
            quote_id INT,
            date_created DATE,
            date_due DATE,
            status VARCHAR(20) DEFAULT 'draft',
            total_ht DECIMAL(12,2),
            total_tva DECIMAL(12,2),
            total_ttc DECIMAL(12,2),
            amount_paid DECIMAL(12,2) DEFAULT 0.00,
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
            FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'invoice_items' => "CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            description TEXT NOT NULL,
            quantity INT DEFAULT 1,
            unit_price DECIMAL(10,2),
            total_price DECIMAL(10,2),
            tva_rate DECIMAL(5,2) DEFAULT 20.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'payments' => "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT,
            amount DECIMAL(12,2),
            payment_method VARCHAR(50),
            payment_date DATE,
            reference VARCHAR(50),
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // History and logs
        'historiques' => "CREATE TABLE IF NOT EXISTS historiques (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            table_name VARCHAR(50),
            record_id INT,
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'activity_logs' => "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'system_logs' => "CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('debug', 'info', 'warning', 'error', 'critical') DEFAULT 'info',
            message TEXT,
            context JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Settings
        'settings' => "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT,
            type VARCHAR(20) DEFAULT 'string',
            description TEXT,
            is_system BOOLEAN DEFAULT false,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            type VARCHAR(50),
            title VARCHAR(255),
            message TEXT,
            data JSON,
            is_read BOOLEAN DEFAULT false,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'templates' => "CREATE TABLE IF NOT EXISTS templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type VARCHAR(50),
            subject VARCHAR(255),
            content TEXT,
            variables JSON,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
}

function insertDefaultData($pdo) {
    // Insert default user roles
    $roles = [
        ['name' => 'admin', 'description' => 'Administrateur système', 'permissions' => json_encode(['*'])],
        ['name' => 'technician', 'description' => 'Technicien réparation', 'permissions' => json_encode(['repairs', 'devices', 'clients'])],
        ['name' => 'sales', 'description' => 'Vendeur', 'permissions' => json_encode(['stock', 'sales', 'clients'])],
        ['name' => 'user', 'description' => 'Utilisateur standard', 'permissions' => json_encode(['read_only'])]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO user_roles (name, description, permissions) VALUES (?, ?, ?)");
    foreach ($roles as $role) {
        try {
            $stmt->execute([$role['name'], $role['description'], $role['permissions']]);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Added role: " . $role['name'] . "\n";
        } catch (Exception $e) {
            echo "  " . COLOR_YELLOW . "⚠" . COLOR_RESET . " Role exists: " . $role['name'] . "\n";
        }
    }
    
    // Insert default repair statuses
    $statuses = [
        ['name' => 'En attente', 'color' => '#ffc107', 'sort_order' => 1],
        ['name' => 'Diagnostic', 'color' => '#17a2b8', 'sort_order' => 2],
        ['name' => 'En cours', 'color' => '#007bff', 'sort_order' => 3],
        ['name' => 'Terminé', 'color' => '#28a745', 'sort_order' => 4],
        ['name' => 'Livré', 'color' => '#6f42c1', 'sort_order' => 5],
        ['name' => 'Annulé', 'color' => '#dc3545', 'sort_order' => 6]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO repair_status (name, color, sort_order) VALUES (?, ?, ?)");
    foreach ($statuses as $status) {
        try {
            $stmt->execute([$status['name'], $status['color'], $status['sort_order']]);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Added status: " . $status['name'] . "\n";
        } catch (Exception $e) {
            echo "  " . COLOR_YELLOW . "⚠" . COLOR_RESET . " Status exists: " . $status['name'] . "\n";
        }
    }
    
    // Insert default device brands
    $brands = ['Apple', 'Samsung', 'Huawei', 'Xiaomi', 'OPPO', 'Vivo', 'OnePlus', 'Sony', 'Nokia', 'Motorola'];
    $stmt = $pdo->prepare("INSERT INTO device_brands (name) VALUES (?)");
    foreach ($brands as $brand) {
        try {
            $stmt->execute([$brand]);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Added brand: " . $brand . "\n";
        } catch (Exception $e) {
            echo "  " . COLOR_YELLOW . "⚠" . COLOR_RESET . " Brand exists: " . $brand . "\n";
        }
    }
    
    // Insert default settings
    $settings = [
        ['key_name' => 'app_name', 'value' => 'R.E.Mobiles - Gestion', 'description' => 'Nom de l\'application'],
        ['key_name' => 'app_version', 'value' => '2.0.0', 'description' => 'Version de l\'application'],
        ['key_name' => 'currency', 'value' => 'EUR', 'description' => 'Devise par défaut'],
        ['key_name' => 'currency_symbol', 'value' => '€', 'description' => 'Symbole de la devise'],
        ['key_name' => 'tax_rate', 'value' => '20.00', 'type' => 'number', 'description' => 'Taux de TVA par défaut'],
        ['key_name' => 'items_per_page', 'value' => '20', 'type' => 'number', 'description' => 'Nombre d\'éléments par page'],
        ['key_name' => 'company_name', 'value' => 'R.E.Mobiles', 'description' => 'Nom de l\'entreprise'],
        ['key_name' => 'company_address', 'value' => '', 'description' => 'Adresse de l\'entreprise'],
        ['key_name' => 'company_phone', 'value' => '', 'description' => 'Téléphone de l\'entreprise'],
        ['key_name' => 'company_email', 'value' => '', 'description' => 'Email de l\'entreprise'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value, type, description) VALUES (?, ?, ?, ?)");
    foreach ($settings as $setting) {
        try {
            $stmt->execute([
                $setting['key_name'], 
                $setting['value'], 
                $setting['type'] ?? 'string', 
                $setting['description']
            ]);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Added setting: " . $setting['key_name'] . "\n";
        } catch (Exception $e) {
            echo "  " . COLOR_YELLOW . "⚠" . COLOR_RESET . " Setting exists: " . $setting['key_name'] . "\n";
        }
    }
}

function createIndexes($pdo) {
    $indexes = [
        "CREATE INDEX idx_users_username ON users(username)",
        "CREATE INDEX idx_users_email ON users(email)",
        "CREATE INDEX idx_users_active ON users(is_active)",
        "CREATE INDEX idx_clients_number ON clients(client_number)",
        "CREATE INDEX idx_devices_number ON devices(device_number)",
        "CREATE INDEX idx_repairs_number ON repairs(repair_number)",
        "CREATE INDEX idx_repairs_status ON repairs(status_id)",
        "CREATE INDEX idx_stock_articles_number ON stock_articles(article_number)",
        "CREATE INDEX idx_articles_code ON articles(code)",
        "CREATE INDEX idx_activity_logs_user ON activity_logs(user_id)",
        "CREATE INDEX idx_activity_logs_created ON activity_logs(created_at)",
        "CREATE INDEX idx_notifications_user ON notifications(user_id)",
        "CREATE INDEX idx_notifications_read ON notifications(is_read)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Created index\n";
        } catch (Exception $e) {
            echo "  " . COLOR_YELLOW . "⚠" . COLOR_RESET . " Index exists or failed\n";
        }
    }
}

function showDatabaseSummary($pdo) {
    // Get table count
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables 
                         WHERE table_schema = DATABASE()");
    $table_count = $stmt->fetch()['table_count'];
    
    // Get total size
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                         FROM information_schema.tables 
                         WHERE table_schema = DATABASE()");
    $size_mb = $stmt->fetch()['size_mb'];
    
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                          Database Summary                                    ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
    
    echo "Tables created: " . COLOR_GREEN . $table_count . COLOR_RESET . "\n";
    echo "Database size: " . COLOR_BLUE . $size_mb . " MB" . COLOR_RESET . "\n";
    echo "Connection string: " . COLOR_YELLOW . "mysql:host=localhost;dbname=u498346438_remshop1" . COLOR_RESET . "\n";
    echo "Username: " . COLOR_YELLOW . "u498346438_remshop1" . COLOR_RESET . "\n";
    echo "Password: " . COLOR_YELLOW . "Remshop104" . COLOR_RESET . "\n\n";
    
    echo COLOR_GREEN . "The database is ready for testing!" . COLOR_RESET . "\n";
    echo "You can now run: php validate_system.php to test the system.\n";
}