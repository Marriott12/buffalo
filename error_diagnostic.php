<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>500 Error Diagnostic</h1>";
echo "<pre>";

echo "=== PHP Version ===\n";
echo "PHP Version: " . phpversion() . "\n\n";

echo "=== File Existence Check ===\n";
$files = [
    'config/config.php',
    'includes/functions.php',
    'includes/database.php',
    'includes/header.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file MISSING\n";
    }
}

echo "\n=== Config Loading Test ===\n";
try {
    define('BUFFALO_SECURE_ACCESS', true);
    include_once 'config/config.php';
    echo "✅ Config loaded successfully\n";
    echo "Site Name: " . (defined('SITE_NAME') ? SITE_NAME : 'NOT DEFINED') . "\n";
    echo "DB Host: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Config fatal error: " . $e->getMessage() . "\n";
}

echo "\n=== Functions Loading Test ===\n";
try {
    include_once 'includes/functions.php';
    echo "✅ Functions loaded successfully\n";
    
    // Test if key functions exist
    $functions = ['isLoggedIn', 'getDatabaseConnection', 'getRegistrationStats'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✅ Function $func exists\n";
        } else {
            echo "❌ Function $func missing\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Functions error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Functions fatal error: " . $e->getMessage() . "\n";
}

echo "\n=== Database Test ===\n";
try {
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        if ($pdo) {
            echo "✅ Database connection successful\n";
        } else {
            echo "❌ Database connection failed\n";
        }
    } else {
        echo "❌ getDatabaseConnection function not available\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Database fatal error: " . $e->getMessage() . "\n";
}

echo "\n=== Index.php Test ===\n";
try {
    ob_start();
    include 'index.php';
    $output = ob_get_clean();
    
    if (strlen($output) > 100) {
        echo "✅ Index.php loads (" . strlen($output) . " bytes)\n";
    } else {
        echo "❌ Index.php output too short: " . strlen($output) . " bytes\n";
        echo "Output: " . substr($output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Index.php error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Index.php fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";
?>
