<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Legacy File Analysis Tool
 * 
 * This script analyzes the original 83 files and provides
 * detailed migration recommendations.
 * 
 * Run: php analyze_legacy.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color codes
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m"; 
const COLOR_BLUE = "\033[34m";
const COLOR_PURPLE = "\033[35m";
const COLOR_CYAN = "\033[36m";

// Analysis results
$analysis_results = [];
$total_files = 0;
$total_issues = 0;

// Header
printHeader();

// Main analysis
$legacy_dir = __DIR__ . '/legacy';
if (!file_exists($legacy_dir)) {
    echo COLOR_RED . "Legacy directory not found. Please create a 'legacy' folder with the original 83 files." . COLOR_RESET . "\n";
    exit(1);
}

// Analyze all PHP files
$files = scandir($legacy_dir);
$php_files = array_filter($files, function($file) {
    return pathinfo($file, PATHINFO_EXTENSION) === 'php';
});

echo COLOR_BLUE . "Found " . count($php_files) . " PHP files to analyze\n" . COLOR_RESET;
echo COLOR_BLUE . "=" . str_repeat("=", 70) . "\n\n" . COLOR_RESET;

foreach ($php_files as $file) {
    analyzeFile($legacy_dir . '/' . $file);
}

// Summary
printSummary();

// Detailed recommendations
printRecommendations();

function analyzeFile($file_path) {
    global $analysis_results, $total_files, $total_issues;
    
    $filename = basename($file_path);
    $total_files++;
    
    echo COLOR_YELLOW . "Analyzing: " . COLOR_RESET . $filename . "\n";
    
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    $analysis = [
        'filename' => $filename,
        'lines' => count($lines),
        'size' => strlen($content),
        'issues' => [],
        'functions' => [],
        'database_queries' => [],
        'security_issues' => [],
        'migration_priority' => 'medium'
    ];
    
    // Check for deprecated PHP functions
    $deprecated_functions = [
        'mysql_connect' => 'Use PDO instead',
        'mysql_query' => 'Use PDO prepared statements',
        'mysql_fetch_array' => 'Use PDO fetch',
        'mysql_fetch_assoc' => 'Use PDO fetch',
        'mysql_num_rows' => 'Use PDO rowCount',
        'mysql_real_escape_string' => 'Use PDO prepared statements',
        'ereg' => 'Use preg_match',
        'eregi' => 'Use preg_match with i modifier',
        'split' => 'Use explode or preg_split',
        'session_register' => 'Use $_SESSION directly',
        'session_unregister' => 'Use unset($_SESSION[\'key\'])',
        'session_is_registered' => 'Use isset($_SESSION[\'key\'])'
    ];
    
    foreach ($deprecated_functions as $func => $replacement) {
        if (strpos($content, $func) !== false) {
            $analysis['issues'][] = [
                'type' => 'deprecated_function',
                'function' => $func,
                'replacement' => $replacement,
                'severity' => 'high'
            ];
            $total_issues++;
        }
    }
    
    // Check for SQL injection vulnerabilities
    $sql_patterns = [
        '/\$_[A-Z]+\[[^\]]+\]\s*\.\s*["\'].*["\']\s*\./' => 'Direct variable concatenation in SQL',
        '/mysql_query\s*\(\s*["\'].*\$_[A-Z]+\[[^\]]+\]/' => 'Direct variable in SQL query',
        '/["\'].*\$[a-zA-Z_][a-zA-Z0-9_]*\s*\./' => 'Variable concatenation in string'
    ];
    
    foreach ($sql_patterns as $pattern => $description) {
        if (preg_match($pattern, $content)) {
            $analysis['security_issues'][] = [
                'type' => 'sql_injection',
                'description' => $description,
                'severity' => 'critical'
            ];
            $total_issues++;
        }
    }
    
    // Check for XSS vulnerabilities
    $xss_patterns = [
        '/echo\s+\$_[A-Z]+\[/' => 'Direct echo of user input',
        '/print\s+\$_[A-Z]+\[/' => 'Direct print of user input',
        '/echo\s+\$[a-zA-Z_][a-zA-Z0-9_]*\s*;/' => 'Direct echo of variable'
    ];
    
    foreach ($xss_patterns as $pattern => $description) {
        if (preg_match($pattern, $content)) {
            $analysis['security_issues'][] = [
                'type' => 'xss',
                'description' => $description,
                'severity' => 'high'
            ];
            $total_issues++;
        }
    }
    
    // Find includes and requires
    $include_patterns = [
        '/include\s*\(\s*["\']([^"\']+)["\']/' => 'include',
        '/require\s*\(\s*["\']([^"\']+)["\']/' => 'require',
        '/include_once\s*\(\s*["\']([^"\']+)["\']/' => 'include_once',
        '/require_once\s*\(\s*["\']([^"\']+)["\']/' => 'require_once'
    ];
    
    foreach ($include_patterns as $pattern => $type) {
        preg_match_all($pattern, $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $include_file) {
                $analysis['includes'][] = [
                    'type' => $type,
                    'file' => $include_file
                ];
            }
        }
    }
    
    // Find database queries
    $query_patterns = [
        '/mysql_query\s*\(\s*["\']([^"\']+)["\']/' => 'mysql_query',
        '/SELECT\s+.*FROM\s+([a-zA-Z_]+)/i' => 'SELECT',
        '/INSERT\s+INTO\s+([a-zA-Z_]+)/i' => 'INSERT',
        '/UPDATE\s+([a-zA-Z_]+)/i' => 'UPDATE',
        '/DELETE\s+FROM\s+([a-zA-Z_]+)/i' => 'DELETE'
    ];
    
    foreach ($query_patterns as $pattern => $type) {
        preg_match_all($pattern, $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $query_part) {
                $analysis['database_queries'][] = [
                    'type' => $type,
                    'query' => $query_part
                ];
            }
        }
    }
    
    // Determine migration priority
    $critical_count = count(array_filter($analysis['security_issues'], function($issue) {
        return $issue['severity'] === 'critical';
    }));
    
    $high_count = count(array_filter($analysis['security_issues'], function($issue) {
        return $issue['severity'] === 'high';
    }));
    
    $deprecated_count = count($analysis['issues']);
    
    if ($critical_count > 0) {
        $analysis['migration_priority'] = 'critical';
    } elseif ($high_count > 0 || $deprecated_count > 5) {
        $analysis['migration_priority'] = 'high';
    } elseif ($deprecated_count > 0) {
        $analysis['migration_priority'] = 'medium';
    } else {
        $analysis['migration_priority'] = 'low';
    }
    
    // Print analysis results
    printFileAnalysis($analysis);
    
    $analysis_results[] = $analysis;
}

function printFileAnalysis($analysis) {
    $priority_colors = [
        'critical' => COLOR_RED,
        'high' => COLOR_YELLOW,
        'medium' => COLOR_CYAN,
        'low' => COLOR_GREEN
    ];
    
    echo "  Lines: " . COLOR_BLUE . $analysis['lines'] . COLOR_RESET;
    echo " | Size: " . COLOR_BLUE . formatBytes($analysis['size']) . COLOR_RESET;
    echo " | Priority: " . $priority_colors[$analysis['migration_priority']] . strtoupper($analysis['migration_priority']) . COLOR_RESET . "\n";
    
    if (!empty($analysis['security_issues'])) {
        echo "  Security Issues:\n";
        foreach ($analysis['security_issues'] as $issue) {
            $color = $issue['severity'] === 'critical' ? COLOR_RED : COLOR_YELLOW;
            echo "    " . $color . "⚠ " . $issue['description'] . COLOR_RESET . "\n";
        }
    }
    
    if (!empty($analysis['issues'])) {
        echo "  Deprecated Functions:\n";
        foreach (array_slice($analysis['issues'], 0, 3) as $issue) {
            echo "    " . COLOR_YELLOW . $issue['function'] . COLOR_RESET . " → " . COLOR_GREEN . $issue['replacement'] . COLOR_RESET . "\n";
        }
        if (count($analysis['issues']) > 3) {
            echo "    " . COLOR_CYAN . "... and " . (count($analysis['issues']) - 3) . " more" . COLOR_RESET . "\n";
        }
    }
    
    if (!empty($analysis['database_queries'])) {
        echo "  Database Queries:\n";
        $unique_tables = array_unique(array_column($analysis['database_queries'], 'query'));
        foreach (array_slice($unique_tables, 0, 5) as $table) {
            echo "    " . COLOR_PURPLE . $table . COLOR_RESET . "\n";
        }
        if (count($unique_tables) > 5) {
            echo "    " . COLOR_CYAN . "... and " . (count($unique_tables) - 5) . " more tables" . COLOR_RESET . "\n";
        }
    }
    
    echo "\n";
}

function printSummary() {
    global $analysis_results, $total_files, $total_issues;
    
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                              Analysis Summary                                ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
    
    echo "Files Analyzed: " . COLOR_YELLOW . $total_files . COLOR_RESET . "\n";
    echo "Total Issues Found: " . COLOR_RED . $total_issues . COLOR_RESET . "\n";
    
    // Priority breakdown
    $priorities = array_column($analysis_results, 'migration_priority');
    $priority_counts = array_count_values($priorities);
    
    echo "\nMigration Priority Breakdown:\n";
    foreach (['critical', 'high', 'medium', 'low'] as $priority) {
        $count = $priority_counts[$priority] ?? 0;
        $color = [
            'critical' => COLOR_RED,
            'high' => COLOR_YELLOW,
            'medium' => COLOR_CYAN,
            'low' => COLOR_GREEN
        ][$priority];
        echo "  $color" . strtoupper($priority) . COLOR_RESET . ": $count files\n";
    }
    
    // Most problematic files
    $problematic_files = array_filter($analysis_results, function($analysis) {
        return $analysis['migration_priority'] === 'critical' || 
               $analysis['migration_priority'] === 'high';
    });
    
    if (!empty($problematic_files)) {
        echo "\nMost Critical Files (Migrate First):\n";
        foreach (array_slice($problematic_files, 0, 10) as $file) {
            $color = $file['migration_priority'] === 'critical' ? COLOR_RED : COLOR_YELLOW;
            echo "  $color" . $file['filename'] . COLOR_RESET . "\n";
        }
    }
}

function printRecommendations() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                           Migration Recommendations                          ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
    
    echo COLOR_GREEN . "Phase 1: Critical Security Fixes (Do First!)" . COLOR_RESET . "\n";
    echo "  • Migrate files with SQL injection vulnerabilities\n";
    echo "  • Fix XSS vulnerabilities in user-facing pages\n";
    echo "  • Replace all mysql_* functions with PDO\n";
    echo "  • Implement CSRF protection on all forms\n\n";
    
    echo COLOR_YELLOW . "Phase 2: Core Functionality Migration" . COLOR_RESET . "\n";
    echo "  • Migrate login.php, index.php, and config.php first\n";
    echo "  • Update database connection methods\n";
    echo "  • Implement new authentication system\n";
    echo "  • Add session security\n\n";
    
    echo COLOR_CYAN . "Phase 3: Complete Modernization" . COLOR_RESET . "\n";
    echo "  • Apply glass morphism design to all pages\n";
    echo "  • Make all pages responsive\n";
    echo "  • Add modern animations and interactions\n";
    echo "  • Implement comprehensive testing\n\n";
    
    echo COLOR_PURPLE . "Migration Tools Available:" . COLOR_RESET . "\n";
    echo "  • validate_system.php - Test new architecture\n";
    echo "  • ADAPTATION_GUIDE.md - Complete migration guide\n";
    echo "  • Modern page templates in /pages/ directory\n";
    echo "  • Security classes in /includes/ directory\n\n";
    
    echo COLOR_BLUE . "Next Steps:" . COLOR_RESET . "\n";
    echo "  1. Run php validate_system.php to test the new architecture\n";
    echo "  2. Start with critical priority files\n";
    echo "  3. Follow the patterns in the modern page templates\n";
    echo "  4. Test each migrated file thoroughly\n";
    echo "  5. Keep original files as backup during migration\n";
}

function formatBytes($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    
    return round($size, 2) . ' ' . $units[$unit];
}

function printHeader() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                    Legacy File Analysis Tool                                 ║
║                    ─────────────────────────                                 ║
║                                                                              ║
║  This tool analyzes the original 83 files and provides detailed              ║
║  migration recommendations for the new architecture.                         ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n\n";
}