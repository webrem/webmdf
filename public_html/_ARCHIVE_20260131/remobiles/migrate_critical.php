<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Critical Files Migration Helper
 * 
 * This script helps migrate the most critical files quickly
 * by providing automated replacements for common patterns.
 * 
 * Run: php migrate_critical.php
 * 
 * WARNING: Always backup original files before running this script!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color codes
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";

// Migration patterns
$migration_patterns = [
    // Database connection patterns
    'mysql_connect' => [
        'pattern' => '/mysql_connect\s*\([^)]+\)\s*;/',
        'replacement' => '// Database connection handled by init.php',
        'description' => 'Replace mysql_connect with new system'
    ],
    'mysql_select_db' => [
        'pattern' => '/mysql_select_db\s*\([^)]+\)\s*;/',
        'replacement' => '// Database selection handled by init.php',
        'description' => 'Replace mysql_select_db with new system'
    ],
    'mysql_query' => [
        'pattern' => '/mysql_query\s*\(\s*["\']([^"\']+)["\']\s*\)/',
        'replacement' => '$db->query("$1")',
        'description' => 'Replace mysql_query with PDO (basic)'
    ],
    'mysql_fetch_array' => [
        'pattern' => '/mysql_fetch_array\s*\(\s*\$([^)]+)\s*\)/',
        'replacement' => '$1->fetch(PDO::FETCH_ASSOC)',
        'description' => 'Replace mysql_fetch_array with PDO fetch'
    ],
    'mysql_fetch_assoc' => [
        'pattern' => '/mysql_fetch_assoc\s*\(\s*\$([^)]+)\s*\)/',
        'replacement' => '$1->fetch(PDO::FETCH_ASSOC)',
        'description' => 'Replace mysql_fetch_assoc with PDO fetch'
    ],
    'mysql_num_rows' => [
        'pattern' => '/mysql_num_rows\s*\(\s*\$([^)]+)\s*\)/',
        'replacement' => '$1->rowCount()',
        'description' => 'Replace mysql_num_rows with rowCount'
    ],
    'mysql_real_escape_string' => [
        'pattern' => '/mysql_real_escape_string\s*\(\s*([^)]+)\s*\)/',
        'replacement' => '$1 /* Use prepared statements instead */',
        'description' => 'Replace mysql_real_escape_string (use prepared statements)'
    ],
    // Session handling
    'session_start_top' => [
        'pattern' => '/^\s*session_start\s*\(\s*\)\s*;/m',
        'replacement' => 'require_once __DIR__ . \'/includes/init.php\';',
        'description' => 'Replace session_start with init.php'
    ]
];

// Header
printHeader();

// Check if legacy directory exists
$legacy_dir = __DIR__ . '/legacy';
if (!file_exists($legacy_dir)) {
    echo COLOR_RED . "Legacy directory not found. Please create a 'legacy' folder with the original files." . COLOR_RESET . "\n";
    exit(1);
}

// Get files to migrate
echo COLOR_BLUE . "Available files for migration:" . COLOR_RESET . "\n";
$files = scandir($legacy_dir);
$php_files = array_filter($files, function($file) {
    return pathinfo($file, PATHINFO_EXTENSION) === 'php';
});

$file_list = array_values($php_files);
foreach ($file_list as $index => $file) {
    echo "  " . ($index + 1) . ". $file\n";
}

echo "\n" . COLOR_YELLOW . "Enter file numbers to migrate (comma-separated) or 'all' for all files: " . COLOR_RESET;
$input = trim(fgets(STDIN));

if ($input === 'all') {
    $selected_files = $file_list;
} else {
    $selected_indices = array_map('trim', explode(',', $input));
    $selected_files = [];
    foreach ($selected_indices as $index) {
        if (isset($file_list[$index - 1])) {
            $selected_files[] = $file_list[$index - 1];
        }
    }
}

if (empty($selected_files)) {
    echo COLOR_RED . "No valid files selected." . COLOR_RESET . "\n";
    exit(1);
}

// Confirm migration
echo "\n" . COLOR_YELLOW . "Selected files:" . COLOR_RESET . "\n";
foreach ($selected_files as $file) {
    echo "  - $file\n";
}

echo "\n" . COLOR_RED . "WARNING: This will modify the selected files." . COLOR_RESET . "\n";
echo COLOR_YELLOW . "Do you want to continue? (yes/no): " . COLOR_RESET;
$confirm = trim(fgets(STDIN));

if ($confirm !== 'yes') {
    echo COLOR_YELLOW . "Migration cancelled." . COLOR_RESET . "\n";
    exit(0);
}

// Process each file
foreach ($selected_files as $file) {
    migrateFile($file);
}

// Summary
printSummary();

function migrateFile($filename) {
    global $migration_patterns;
    
    $source_path = __DIR__ . '/legacy/' . $filename;
    $backup_path = __DIR__ . '/legacy/' . $filename . '.backup';
    $target_path = __DIR__ . '/' . $filename;
    
    echo "\n" . COLOR_BLUE . "Migrating: $filename" . COLOR_RESET . "\n";
    
    // Check if source file exists
    if (!file_exists($source_path)) {
        echo COLOR_RED . "  Source file not found: $source_path" . COLOR_RESET . "\n";
        return false;
    }
    
    // Read source file
    $content = file_get_contents($source_path);
    $original_content = $content;
    
    // Create backup
    if (!copy($source_path, $backup_path)) {
        echo COLOR_YELLOW . "  Warning: Could not create backup" . COLOR_RESET . "\n";
    }
    
    // Apply migration patterns
    $changes_made = 0;
    foreach ($migration_patterns as $name => $pattern) {
        $new_content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
        if ($new_content !== $content) {
            $content = $new_content;
            $changes_made++;
            echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " " . $pattern['description'] . "\n";
        }
    }
    
    // Add required includes if not present
    if (strpos($content, 'includes/init.php') === false) {
        $content = "<?php\nrequire_once __DIR__ . '/includes/init.php';\n\n" . substr($content, 6);
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " Added init.php include\n";
        $changes_made++;
    }
    
    // Handle specific file types
    if ($filename === 'login.php') {
        $content = migrateLoginFile($content);
        $changes_made++;
    }
    
    if ($filename === 'index.php') {
        $content = migrateIndexFile($content);
        $changes_made++;
    }
    
    // Write migrated file
    if (file_put_contents($target_path, $content)) {
        echo "  " . COLOR_GREEN . "✓" . COLOR_RESET . " File migrated successfully\n";
        echo "  " . COLOR_BLUE . "Changes made: $changes_made" . COLOR_RESET . "\n";
        
        // Show before/after comparison
        $original_lines = count(explode("\n", $original_content));
        $new_lines = count(explode("\n", $content));
        echo "  " . COLOR_BLUE . "Lines: $original_lines → $new_lines" . COLOR_RESET . "\n";
        
        return true;
    } else {
        echo COLOR_RED . "  Failed to write migrated file" . COLOR_RESET . "\n";
        return false;
    }
}

function migrateLoginFile($content) {
    // Replace with modern login structure
    $modern_login = file_get_contents(__DIR__ . '/login.php');
    if ($modern_login !== false) {
        return $modern_login;
    }
    return $content;
}

function migrateIndexFile($content) {
    // Replace with modern dashboard structure
    $modern_dashboard = file_get_contents(__DIR__ . '/dashboard.php');
    if ($modern_dashboard !== false) {
        return $modern_dashboard;
    }
    return $content; 
}

function printHeader() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                    Critical Files Migration Helper                           ║
║                    ───────────────────────────────                           ║
║                                                                              ║
║  This tool helps migrate critical files by automatically replacing           ║
║  common deprecated patterns with modern equivalents.                       ║
║                                                                              ║
║  WARNING: Always backup your files before running this tool!               ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n\n";
}

function printSummary() {
    echo COLOR_BLUE . "
╔══════════════════════════════════════════════════════════════════════════════╗
║                              Migration Summary                               ║
╚══════════════════════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
    
    echo "Migration completed!\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Test the migrated files thoroughly\n";
    echo "  2. Run php validate_system.php to check the system\n";
    echo "  3. Review the migrated files for any manual adjustments needed\n";
    echo "  4. Test user authentication and database operations\n";
    echo "  5. Gradually migrate remaining files following the guide\n\n";
    
    echo "Important Notes:\n";
    echo "  • Migrated files are created in the root directory\n";
    echo "  • Original files are preserved in the legacy directory\n";
    echo "  • Some manual adjustments may still be required\n";
    echo "  • Always test in development environment first\n";
}