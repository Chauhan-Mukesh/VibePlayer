#!/usr/bin/env php
<?php
/**
 * VibePlayer Health Check Script
 * 
 * This script performs health checks without requiring network calls,
 * making it suitable for use in restricted build environments.
 * 
 * Exit codes:
 * 0 - Healthy
 * 1 - Unhealthy
 */

// Health check functions
function checkFile($file, $description) {
    if (!file_exists($file)) {
        error_log("Health check failed: $description - file not found: $file");
        return false;
    }
    return true;
}

function checkDirectory($dir, $description, $writable = false) {
    if (!is_dir($dir)) {
        error_log("Health check failed: $description - directory not found: $dir");
        return false;
    }
    
    if ($writable && !is_writable($dir)) {
        error_log("Health check failed: $description - directory not writable: $dir");
        return false;
    }
    
    return true;
}

function checkPhpSyntax($file) {
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
    
    if ($return_var !== 0) {
        error_log("Health check failed: PHP syntax error in $file: " . implode("\n", $output));
        return false;
    }
    
    return true;
}

// Determine if we're running in container or locally
$in_container = file_exists('/var/www/html/index.php');
$base_dir = $in_container ? '/var/www/html' : __DIR__;
$cache_dir = $in_container ? '/tmp/vibeplayercache' : sys_get_temp_dir() . '/vibeplayercache';

// Ensure cache directory exists for local testing
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0755, true);
}

// Perform health checks
$checks = [];

// Check critical files exist and have valid PHP syntax
$checks['index_file'] = checkFile($base_dir . '/index.php', 'Main index.php file');
$checks['index_syntax'] = checkPhpSyntax($base_dir . '/index.php');
$checks['htaccess_file'] = checkFile($base_dir . '/.htaccess', 'Apache .htaccess file');

// Check cache directory
$checks['cache_dir'] = checkDirectory($cache_dir, 'Cache directory', true);

// Check PHP configuration (only required in container)
$checks['php_config'] = $in_container ? file_exists('/usr/local/etc/php/conf.d/vibeplayer.ini') : true;

// Check Apache configuration (only required in container) 
$checks['apache_config'] = $in_container ? file_exists('/etc/apache2/apache2.conf') : true;

// Test basic PHP functionality without network calls
try {
    // Test JSON encoding/decoding
    $test_data = ['status' => 'healthy', 'timestamp' => time()];
    $json = json_encode($test_data);
    $decoded = json_decode($json, true);
    $checks['php_json'] = ($decoded['status'] === 'healthy');
} catch (Exception $e) {
    error_log("Health check failed: PHP JSON functionality error: " . $e->getMessage());
    $checks['php_json'] = false;
}

// Test file system operations
try {
    $test_file = $cache_dir . '/healthcheck_test_' . time();
    file_put_contents($test_file, 'test');
    $content = file_get_contents($test_file);
    unlink($test_file);
    $checks['filesystem'] = ($content === 'test');
} catch (Exception $e) {
    error_log("Health check failed: Filesystem operations error: " . $e->getMessage());
    $checks['filesystem'] = false;
}

// Check process status (if available and in container)
$checks['apache_process'] = true; // Default to true for non-container environments
if ($in_container) {
    try {
        // Check if we can determine if Apache is running
        $processes = [];
        exec('ps aux', $processes);
        $apache_running = false;
        foreach ($processes as $process) {
            if (strpos($process, 'apache2') !== false) {
                $apache_running = true;
                break;
            }
        }
        $checks['apache_process'] = $apache_running;
    } catch (Exception $e) {
        // Process check failed - not critical in all environments
        $checks['apache_process'] = true; // Assume it's running
    }
}

// Evaluate overall health
$failed_checks = array_filter($checks, function($result) { return !$result; });

if (empty($failed_checks)) {
    // All checks passed
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => time(),
        'checks' => $checks,
        'method' => 'local_checks'
    ]);
    exit(0);
} else {
    // Some checks failed
    echo json_encode([
        'status' => 'unhealthy', 
        'timestamp' => time(),
        'checks' => $checks,
        'failed' => array_keys($failed_checks),
        'method' => 'local_checks'
    ]);
    exit(1);
}
?>