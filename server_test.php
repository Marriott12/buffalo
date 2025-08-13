<?php
// Simple server test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Test - " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";

// Test basic PHP functionality
echo "Math test: 2 + 2 = " . (2 + 2) . "<br>";

// Test file access
if (file_exists('config/config.php')) {
    echo "Config file exists<br>";
} else {
    echo "Config file missing<br>";
}

// Test constants
define('TEST_CONSTANT', 'Working');
echo "Constants: " . TEST_CONSTANT . "<br>";

// Test array
$test_array = ['a' => 1, 'b' => 2];
echo "Array test: " . json_encode($test_array) . "<br>";

echo "Basic test completed successfully!";
?>
