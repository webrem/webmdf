<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * R.E.Mobiles System Validation Script
 * 
 * This script performs comprehensive validation of the new architecture
 * with the real database structure and data.
 * 
 * Run: php validate_system.php
 */

// Report all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color codes for terminal output
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_PURPLE = "\033[35m";

// Test results storage
$test_results = [];
$total_tests = 0;
$passed_tests = 0; 

// Header
printHeader();

// Test 1: Core Files Existence
runTest("Core Files Existence", function() {
    $required_files = [
        __DIR__ . '/includes/config.php',
        __DIR__ . '/includes/database.php',
        __DIR__ . '/includes/auth.php',
        __DIR__ . '/includes/security.php',
        __DIR__ . '/includes/models.php',
        __DIR__ . '/includes/init.php',
        __DIR__ . '/includes/helpers.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing required file: $file");
        }
    }
    return true;
});

// Test 2: Configuration Loading
runTest("Configuration Loading", function() {
    global $config;
    require_once __DIR__ . '/includes/config.php';
    
    if (!isset($config) || !is_array($config)) {
        throw new Exception("Configuration not loaded properly");
    }
    
    $required_keys = ['database', 'security', 'tables'];
    foreach ($required_keys as $key) {
        if (!isset($config[$key])) {
            throw new Exception("Missing configuration key: $key");
        }
    }
    
    return true;
});

// Test 3: Database Connection
runTest("Database Connection", function() {
    try {
        require_once __DIR__ . '/includes/database.php';
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Test connection
        $result = $pdo->query("SELECT 1");
        if (!$result) {
            throw new Exception("Database connection test failed");
        }
        
        return true;
    } catch (Exception $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
});

// Test 4: Database Table Structure
runTest("Database Table Structure", function() {
    global $config;
    require_once __DIR__ . '/includes/database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get list of tables from config
    $expected_tables = array_values($config['tables']);
    
    // Get actual tables from database
    $stmt = $pdo->query("SHOW TABLES");
    $actual_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check if expected tables exist
    $missing_tables = array_diff($expected_tables, $actual_tables);
    if (!empty($missing_tables)) {
        throw new Exception("Missing tables: " . implode(', ', $missing_tables));
    }
    
    return true;
});

// Test 5: User Tables Analysis
runTest("User Tables Analysis", function() {
    require_once __DIR__ . '/includes/database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    $user_tables = ['admin_users', 'users'];
    $user_count = 0;
    
    foreach ($user_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_count += $result['count'];
            
            // Check table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "  Table '$table': " . $result['count'] . " users, columns: " . implode(', ', $columns) . "\n";
        } catch (Exception $e) {
            echo "  Table '$table': Not accessible (" . $e->getMessage() . ")\n";
        }
    }
    
    if ($user_count === 0) {
        throw new Exception("No users found in any user table");
    }
    
    echo "  Total users found: $user_count\n";
    return true;
});

// Test 6: Authentication System
runTest("Authentication System", function() {
    require_once __DIR__ . '/includes/init.php';
    
    global $auth, $db;
    
    if (!isset($auth)) {
        throw new Exception("Authentication system not initialized");
    }
    
    // Test CSRF token generation
    $token = $auth->getCSRFToken();
    if (empty($token)) {
        throw new Exception("CSRF token generation failed");
    }
    
    // Test session initialization
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception("Session not active");
    }
    
    return true;
});

// Test 7: Model System
runTest("Model System", function() {
    require_once __DIR__ . '/includes/init.php';
    
    global $db;
    
    // Test basic model functionality
    $model = new BaseModel($db, 'admin_users');
    
    // Test find method
    $result = $model->find(1);
    if ($result === false) {
        echo "  Note: User with ID 1 not found, but model system works\n";
    }
    
    return true;
});

// Test 8: Security Features
runTest("Security Features", function() {
    require_once __DIR__ . '/includes/init.php';
    
    global $security;
    
    // Test input sanitization
    $test_input = "<script>alert('test')</script>";
    $sanitized = $security->sanitizeInput($test_input);
    
    if (strpos($sanitized, '<script>') !== false) {
        throw new Exception("Input sanitization failed");
    }
    
    // Test filename sanitization
    $test_filename = "../../../etc/passwd";
    $sanitized_filename = $security->sanitizeFilename($test_filename);
    
    if (strpos($sanitized_filename, '../') !== false) {
        throw new Exception("Filename sanitization failed");
    }
    
    return true;
});

// Test 9: Modern Pages Loading
runTest("Modern Pages Loading", function() {
    $modern_pages = [
        'login.php',
        'register.php',
        'dashboard.php',
        'historique.php',
        'clients.php',
        'devices.php',
        'stock.php',
        'settings.php'
    ];
    
    foreach ($modern_pages as $page) {
        $file_path = __DIR__ . '/' . $page;
        if (!file_exists($file_path)) {
            throw new Exception("Modern page missing: $page");
        }
        
        // Check if page includes proper initialization
        $content = file_get_contents($file_path);
        if (strpos($content, 'includes/init.php') === false) {
            throw new Exception("Page $page doesn't include init.php");
        }
    }
    
    return true;
});

// Test 10: Legacy File Analysis
runTest("Legacy File Analysis", function() {
    $legacy_files = scandir(__DIR__ . '/legacy');
    $legacy_files = array_diff($legacy_files, ['.', '..']);
    
    echo "  Found " . count($legacy_files) . " legacy files\n";
    
    // Analyze key files
    $key_files = ['index.php', 'login.php', 'config.php'];
    foreach ($key_files as $file) {
        if (in_array($file, $legacy_files)) {
            $content = file_get_contents(__DIR__ . '/legacy/' . $file);
            
            // Check for common patterns
            $patterns = [
                'mysql_connect' => 'MySQL extension (deprecated)',
                'mysql_query' => 'MySQL extension (deprecated)',
                '$_GET[.*].*mysql_query' => 'Direct GET in query (SQL injection risk)',
                'echo.*\$_POST' => 'Direct echo of POST (XSS risk)',
                'session_start' => 'Session management',
                'include.*config' => 'Configuration inclusion'
            ];
            
            echo "  Analysis of $file:\n";
            foreach ($patterns as $pattern => $description) {
                if (preg_match("/$pattern/i", $content)) {
                    echo "    - Found: $description\n";
                }
            }
        }
    }
    
    return true;
});

// Test 11: Database Migration Status
runTest("Database Migration Status", function() {
    require_once __DIR__ . '/includes/database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if migration table exists
    try {
        $stmt = $pdo->query("SELECT 1 FROM migration_log LIMIT 1");
        echo "  Migration log table exists\n";
    } catch (Exception $e) {
        echo "  Migration log table not found (normal for first run)\n";
    }
    
    // Check table structures
    $tables_to_check = ['admin_users', 'clients', 'devices', 'stock_articles'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "  Table '$table' has " . count($columns) . " columns\n";
        } catch (Exception $e) {
            echo "  Table '$table' error: " . $e->getMessage() . "\n";
        }
    }
    
    return true;
});

// Test 12: Performance Check
runTest("Performance Check", function() {
    $start_time = microtime(true);
    
    require_once __DIR__ . '/includes/init.php';
    
    global $db;
    
    // Test query performance
    $queries = [
        "SELECT COUNT(*) FROM admin_users",
        "SELECT COUNT(*) FROM clients",
        "SELECT COUNT(*) FROM devices",
        "SELECT COUNT(*) FROM stock_articles"
    ];
    
    foreach ($queries as $query) {
        $start_query = microtime(true);
        $result = $db->fetch($query);
        $query_time = microtime(true) - $start_query;
        
        if ($query_time > 1.0) {
            echo "  Warning: Query '$query' took " . number_format($query_time, 4) . " seconds\n";
        }
    }
    
    $total_time = microtime(true) - $start_time;
    echo "  Total test time: " . number_format($total_time, 4) . " seconds\n";
    
    return true;
});

// Test 13: File Permissions
runTest("File Permissions", function() {
    $directories_to_check = [
        __DIR__ . '/uploads',
        __DIR__ . '/logs',
        __DIR__ . '/cache'
    ];
    
    foreach ($directories_to_check as $dir) {
        if (!file_exists($dir)) {
            echo "  Creating directory: $dir\n";
            mkdir($dir, 0755, true);
        }
        
        if (!is_writable($dir)) {
            throw new Exception("Directory not writable: $dir");
        }
    }
    
    return true;
});

// Test 14: Backup Verification
runTest("Backup Verification", function() {
    $backup_dirs = [
        __DIR__ . '/legacy',
        __DIR__ . '/backups'
    ];
    
    foreach ($backup_dirs as $dir) {
        if (file_exists($dir)) {
            $files = scandir($dir);
            $file_count = count(array_diff($files, ['.', '..']));
            echo "  Directory '$dir' contains $file_count files\n";
        } else {
            echo "  Directory '$dir' not found\n";
        }
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
║                    R.E.Mobiles System Validation                             ║
║                    ───────────────────────────                               ║
║                                                                              ║
║  This script validates the new architecture against the real database        ║
║  structure and provides comprehensive compatibility testing.                 ║
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
║                              Validation Summary                              ║
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
    echo COLOR_BLUE . "Recommendations:" . COLOR_RESET . "\n";
    
    if ($passed_tests == $total_tests) {
        echo COLOR_GREEN . "  ✓ All tests passed! System is ready for production." . COLOR_RESET . "\n";
    } else {
        echo COLOR_YELLOW . "  ⚠ Some tests failed. Please address the issues above before proceeding." . COLOR_RESET . "\n";
    }
    
    echo "\n" . COLOR_BLUE . "Next Steps:" . COLOR_RESET . "\n";
    echo "  1. Review the ADAPTATION_GUIDE.md for file migration instructions\n";
    echo "  2. Use the validation results to fix any issues\n";
    echo "  3. Test with actual user login\n";
    echo "  4. Gradually migrate original files following the guide\n";
    echo "\n";
}

// Exit with appropriate code
exit($passed_tests === $total_tests ? 0 : 1);