<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Test script for remshop1 database connection
 * Validates the new database setup and configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color codes
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";

// Header
printHeader();

// Test results
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

// Test 1: Configuration Loading
runTest("Configuration Loading", function() {
    $config = require_once __DIR__ . '/includes/config_remshop1.php';
    
    if (!isset($config) || !is_array($config)) {
        throw new Exception("Configuration not loaded properly");
    }
    
    $required_keys = ['database', 'security', 'tables'];
    foreach ($required_keys as $key) {
        if (!isset($config[$key])) {
            throw new Exception("Missing configuration key: $key");
        }
    }
    
    echo "  Database: " . COLOR_BLUE . $config['database']['name'] . COLOR_RESET . "\n";
    echo "  User: " . COLOR_BLUE . $config['database']['user'] . COLOR_RESET . "\n";
    echo "  Host: " . COLOR_BLUE . $config['database']['host'] . COLOR_RESET . "\n";
    
    return true;
});

// Test 2: Database Connection
runTest("Database Connection", function() {
    try {
        $db_config = [
            'host' => 'localhost',
            'name' => 'u498346438_remshop1',
            'user' => 'u498346438_remshop1',
            'pass' => 'Remshop104'
        ];
        
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
            $db_config['user'],
            $db_config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Test connection
        $result = $pdo->query("SELECT 1");
        if (!$result) {
            throw new Exception("Database connection test failed");
        }
        
        // Get database version
        $version = $pdo->query("SELECT VERSION() as version")-fetch()['version'];
        echo "  MySQL Version: " . COLOR_BLUE . $version . COLOR_RESET . "\n";
        
        return true;
    } catch (Exception $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
});

// Test 3: Table Structure
runTest("Table Structure", function() {
    $db_config = [
        'host' => 'localhost',
        'name' => 'u498346438_remshop1',
        'user' => 'u498346438_remshop1',
        'pass' => 'Remshop104'
    ];
    
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass']
    );
    
    // Get list of tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "  Tables found: " . COLOR_BLUE . count($tables) . COLOR_RESET . "\n";
    
    $expected_tables = [
        'users', 'clients', 'devices', 'repairs', 'stock_articles', 
        'articles', 'invoices', 'quotes', 'historiques'
    ];
    
    $missing_tables = array_diff($expected_tables, $tables);
    if (!empty($missing_tables)) {
        throw new Exception("Missing tables: " . implode(', ', $missing_tables));
    }
    
    // Show some key tables
    echo "  Key tables: " . COLOR_GREEN . implode(', ', array_slice($tables, 0, 5)) . COLOR_RESET . "\n";
    
    return true;
});

// Test 4: Default Data
runTest("Default Data", function() {
    $db_config = [
        'host' => 'localhost',
        'name' => 'u498346438_remshop1',
        'user' => 'u498346438_remshop1',
        'pass' => 'Remshop104'
    ];
    
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass']
    );
    
    // Check user roles
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_roles");
    $role_count = $stmt->fetch()['count'];
    echo "  User roles: " . COLOR_BLUE . $role_count . COLOR_RESET . "\n";
    
    // Check repair statuses
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM repair_status");
    $status_count = $stmt->fetch()['count'];
    echo "  Repair statuses: " . COLOR_BLUE . $status_count . COLOR_RESET . "\n";
    
    // Check device brands
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM device_brands");
    $brand_count = $stmt->fetch()['count'];
    echo "  Device brands: " . COLOR_BLUE . $brand_count . COLOR_RESET . "\n";
    
    // Check settings
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM settings");
    $setting_count = $stmt->fetch()['count'];
    echo "  Settings: " . COLOR_BLUE . $setting_count . COLOR_RESET . "\n";
    
    if ($role_count == 0 || $status_count == 0 || $brand_count == 0) {
        throw new Exception("Default data not found. Run setup script first.");
    }
    
    return true;
});

// Test 5: User Creation and Authentication
runTest("User Creation and Authentication", function() {
    $db_config = [
        'host' => 'localhost',
        'name' => 'u498346438_remshop1',
        'user' => 'u498346438_remshop1',
        'pass' => 'Remshop104'
    ];
    
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass']
    );
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $admin_exists = $stmt->fetch()['count'] > 0;
    
    if (!$admin_exists) {
        // Create admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'admin@remshop1.local',
            $password_hash,
            'Administrateur',
            'Système',
            1
        ]);
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Created admin user (admin/admin123)\n";
    } else {
        echo "  " . COLOR_BLUE . "Admin user already exists" . COLOR_RESET . "\n";
    }
    
    // Test password verification
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user && password_verify('admin123', $user['password'])) {
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Password verification works\n";
    } else {
        throw new Exception("Password verification failed");
    }
    
    return true;
});

// Test 6: Sample Data Creation
runTest("Sample Data Creation", function() {
    $db_config = [
        'host' => 'localhost',
        'name' => 'u498346438_remshop1',
        'user' => 'u498346438_remshop1',
        'pass' => 'Remshop104'
    ];
    
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass']
    );
    
    // Check if sample client exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $client_count = $stmt->fetch()['count'];
    
    if ($client_count == 0) {
        // Create sample client
        $stmt = $pdo->prepare("INSERT INTO clients (company_name, first_name, last_name, email, phone, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Exemple Entreprise',
            'Jean',
            'Dupont',
            'jean.dupont@example.com',
            '0612345678',
            '123 Rue de l\'Exemple',
            'Paris',
            '75001'
        ]);
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Created sample client\n";
    }
    
    // Create sample stock articles
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stock_articles");
    $article_count = $stmt->fetch()['count'];
    
    if ($article_count == 0) {
        $sample_articles = [
            ['name' => 'Écran iPhone 12', 'selling_price' => 89.99, 'purchase_price' => 45.00],
            ['name' => 'Batterie Samsung Galaxy S21', 'selling_price' => 34.99, 'purchase_price' => 18.00],
            ['name' => 'Chargeur USB-C', 'selling_price' => 19.99, 'purchase_price' => 8.50],
            ['name' => 'Câble Lightning', 'selling_price' => 12.99, 'purchase_price' => 5.00],
            ['name' => 'Protection Écran Verre Trempé', 'selling_price' => 9.99, 'purchase_price' => 3.00]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO stock_articles (name, selling_price, purchase_price, quantity_in_stock) VALUES (?, ?, ?, ?)");
        foreach ($sample_articles as $article) {
            $stmt->execute([
                $article['name'],
                $article['selling_price'],
                $article['purchase_price'],
                rand(5, 50)
            ]);
        }
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Created " . count($sample_articles) . " sample articles\n";
    }
    
    return true;
});

// Summary
printSummary();

// Helper Functions
function printHeader() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                    R.E.Mobiles Database Connection Test                      ║
║                    ─────────────────────────────────────                     ║
║                                                                              ║
║  Testing connection to u498346438_remshop1 database with provided credentials           ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n\n";
}

function runTest($name, $testFunction) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    echo COLOR_YELLOW . "Testing: " . COLOR_RESET . $name . "... ";
    
    try {
        $result = $testFunction();
        if ($result === true) {
            echo COLOR_GREEN . "✓ PASS" . COLOR_RESET . "\n";
            $passed_tests++;
            $test_results[$name] = ['status' => 'PASS', 'message' => ''];
        } else {
            echo COLOR_RED . "✗ FAIL" . COLOR_RESET . "\n";
            $test_results[$name] = ['status' => 'FAIL', 'message' => 'Test returned false'];
        }
    } catch (Exception $e) {
        echo COLOR_RED . "✗ FAIL" . COLOR_RESET . "\n";
        echo COLOR_RED . "  Error: " . $e->getMessage() . COLOR_RESET . "\n";
        $test_results[$name] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }
    
    echo "\n";
}

function printSummary() {
    global $total_tests, $passed_tests, $test_results;
    
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                              Test Summary                                    ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
    
    echo "Total Tests: " . COLOR_YELLOW . $total_tests . COLOR_RESET . "\n";
    echo "Passed: " . COLOR_GREEN . $passed_tests . COLOR_RESET . "\n";
    echo "Failed: " . COLOR_RED . ($total_tests - $passed_tests) . COLOR_RESET . "\n";
    echo "Success Rate: " . COLOR_BLUE . number_format(($passed_tests / $total_tests) * 100, 1) . "%" . COLOR_RESET . "\n\n";
    
    // Show failed tests
    $failed_tests = array_filter($test_results, function($result) {
        return $result['status'] === 'FAIL';
    });
    
    if (!empty($failed_tests)) {
        echo COLOR_RED . "Failed Tests:" . COLOR_RESET . "\n";
        foreach ($failed_tests as $name => $result) {
            echo "  • $name: " . $result['message'] . "\n";
        }
        echo "\n";
    }
    
    // Recommendations
    echo COLOR_BLUE . "Next Steps:" . COLOR_RESET . "\n";
    
    if ($passed_tests == $total_tests) {
        echo COLOR_GREEN . "  ✓ All tests passed! Database is ready for use." . COLOR_RESET . "\n";
        echo "  1. Update your pages to use init_remshop1.php\n";
        echo "  2. Test the login system with admin/admin123\n";
        echo "  3. Start using the independent system for testing\n";
    } else {
        echo COLOR_YELLOW . "  ⚠ Some tests failed. Please address the issues above." . COLOR_RESET . "\n";
        echo "  1. Check database credentials\n";
        echo "  2. Run setup_remshop1_database.php if needed\n";
        echo "  3. Verify database permissions\n";
    }
    
    echo "\n";
}

// Exit with appropriate code
exit($passed_tests === $total_tests ? 0 : 1);