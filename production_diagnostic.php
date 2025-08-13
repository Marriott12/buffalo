<?php
/**
 * Production Server Diagnostic Tool
 * Use this to diagnose 500 errors on envisagezm.com
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set up custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    echo "<div style='color: red; background: #ffeeee; padding: 10px; margin: 5px; border: 1px solid red;'>";
    echo "<strong>PHP Error [$errno]:</strong> $errstr<br>";
    echo "<strong>File:</strong> $errfile<br>";
    echo "<strong>Line:</strong> $errline<br>";
    echo "</div>";
    return true;
}
set_error_handler("customErrorHandler");

// Set up exception handler
function customExceptionHandler($exception) {
    echo "<div style='color: red; background: #ffeeee; padding: 10px; margin: 5px; border: 1px solid red;'>";
    echo "<strong>Uncaught Exception:</strong> " . $exception->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><pre>" . $exception->getTraceAsString() . "</pre>";
    echo "</div>";
}
set_exception_handler("customExceptionHandler");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Server Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #eeffee; padding: 5px; margin: 2px; }
        .error { color: red; background: #ffeeee; padding: 5px; margin: 2px; }
        .warning { color: orange; background: #fff9ee; padding: 5px; margin: 2px; }
        .info { color: blue; background: #eeeeff; padding: 5px; margin: 2px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🏃‍♂️ Buffalo Marathon - Production Server Diagnostic</h1>
    
    <?php
    echo "<div class='info'><strong>Diagnostic Time:</strong> " . date('Y-m-d H:i:s T') . "</div>";
    echo "<div class='info'><strong>Server:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</div>";
    
    echo "<h2>📊 Server Environment</h2>";
    echo "<div class='success'>PHP Version: " . phpversion() . "</div>";
    echo "<div class='success'>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
    echo "<div class='success'>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>";
    echo "<div class='success'>Current Directory: " . __DIR__ . "</div>";
    
    echo "<h2>📁 File Structure Check</h2>";
    $required_files = [
        'config/config.php' => 'Configuration file',
        'includes/functions.php' => 'Core functions',
        'includes/database.php' => 'Database functions',
        'includes/header.php' => 'Header template',
        'includes/footer.php' => 'Footer template',
        'index.php' => 'Homepage',
        'admin/dashboard.php' => 'Admin dashboard'
    ];
    
    foreach ($required_files as $file => $description) {
        if (file_exists($file)) {
            $size = filesize($file);
            echo "<div class='success'>✅ $file ($description) - " . number_format($size) . " bytes</div>";
        } else {
            echo "<div class='error'>❌ $file ($description) - MISSING</div>";
        }
    }
    
    echo "<h2>🔧 Configuration Test</h2>";
    try {
        define('BUFFALO_SECURE_ACCESS', true);
        if (file_exists('config/config.php')) {
            require_once 'config/config.php';
            echo "<div class='success'>✅ Configuration loaded successfully</div>";
            
            $constants_to_check = ['DB_HOST', 'DB_NAME', 'DB_USER', 'SITE_NAME', 'MARATHON_DATE'];
            foreach ($constants_to_check as $const) {
                if (defined($const)) {
                    echo "<div class='success'>✅ $const: " . constant($const) . "</div>";
                } else {
                    echo "<div class='error'>❌ $const: NOT DEFINED</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ config/config.php file not found</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Configuration error: " . $e->getMessage() . "</div>";
    } catch (Error $e) {
        echo "<div class='error'>❌ Configuration fatal error: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>🔧 Functions Test</h2>";
    try {
        if (file_exists('includes/functions.php')) {
            require_once 'includes/functions.php';
            echo "<div class='success'>✅ Functions file loaded successfully</div>";
            
            $test_functions = ['isLoggedIn', 'getDatabaseConnection', 'getRegistrationStats', 'getDaysUntilMarathon'];
            foreach ($test_functions as $func) {
                if (function_exists($func)) {
                    echo "<div class='success'>✅ Function $func exists</div>";
                } else {
                    echo "<div class='warning'>⚠️ Function $func missing</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ includes/functions.php file not found</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Functions error: " . $e->getMessage() . "</div>";
    } catch (Error $e) {
        echo "<div class='error'>❌ Functions fatal error: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>🗄️ Database Test</h2>";
    try {
        if (function_exists('getDatabaseConnection')) {
            $pdo = getDatabaseConnection();
            if ($pdo) {
                echo "<div class='success'>✅ Database connection successful</div>";
                
                // Test basic query
                try {
                    $result = $pdo->query("SELECT 1 as test")->fetch();
                    echo "<div class='success'>✅ Database query test successful</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Database query failed: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='error'>❌ Database connection failed (check credentials)</div>";
            }
        } else {
            echo "<div class='error'>❌ getDatabaseConnection function not available</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
    } catch (Error $e) {
        echo "<div class='error'>❌ Database fatal error: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>🏠 Homepage Test</h2>";
    try {
        ob_start();
        error_reporting(0); // Suppress errors for this test
        include 'index.php';
        $output = ob_get_clean();
        error_reporting(E_ALL);
        
        if (strlen($output) > 1000) {
            echo "<div class='success'>✅ Homepage loads successfully (" . number_format(strlen($output)) . " bytes)</div>";
        } else {
            echo "<div class='warning'>⚠️ Homepage output seems short: " . number_format(strlen($output)) . " bytes</div>";
            if (strlen($output) > 0) {
                echo "<div class='info'><strong>First 500 characters:</strong><pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre></div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Homepage error: " . $e->getMessage() . "</div>";
    } catch (Error $e) {
        echo "<div class='error'>❌ Homepage fatal error: " . $e->getMessage() . "</div>";
        echo "<div class='error'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</div>";
    }
    
    echo "<h2>📋 PHP Extensions Check</h2>";
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "<div class='success'>✅ $ext extension loaded</div>";
        } else {
            echo "<div class='error'>❌ $ext extension MISSING</div>";
        }
    }
    
    echo "<h2>📊 Server Information</h2>";
    echo "<div class='info'><strong>PHP Memory Limit:</strong> " . ini_get('memory_limit') . "</div>";
    echo "<div class='info'><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds</div>";
    echo "<div class='info'><strong>Upload Max Filesize:</strong> " . ini_get('upload_max_filesize') . "</div>";
    echo "<div class='info'><strong>Post Max Size:</strong> " . ini_get('post_max_size') . "</div>";
    
    echo "<h2>🎯 Recommendation</h2>";
    echo "<div class='info'>";
    echo "<p><strong>If you see this page without errors, your server can run PHP correctly.</strong></p>";
    echo "<p>If the homepage test failed, the issue is likely in the index.php file or missing database.</p>";
    echo "<p>Check the error details above and ensure all files are uploaded correctly.</p>";
    echo "</div>";
    ?>
    
    <hr>
    <p><small>Buffalo Marathon 2025 - Production Diagnostic Tool</small></p>
</body>
</html>
