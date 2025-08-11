<?php
require_once '../includes/functions.php';
requireAdmin();

set_time_limit(300); // 5 minutes

$db = getDB();
$timestamp = date('Y-m-d_H-i-s');
$filename = "buffalo_marathon_backup_{$timestamp}.sql";

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');

echo "-- Buffalo Marathon 2025 Database Backup\n";
echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
echo "-- MySQL Version: " . $db->query('SELECT VERSION()')->fetchColumn() . "\n\n";

echo "SET FOREIGN_KEY_CHECKS = 0;\n";
echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
echo "SET time_zone = '+00:00';\n\n";

$tables = ['users', 'categories', 'registrations', 'schedules', 'announcements', 'payment_logs', 'activity_logs', 'email_queue', 'settings'];

foreach ($tables as $table) {
    // Get table structure
    $stmt = $db->query("SHOW CREATE TABLE `$table`");
    $create = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "-- Table structure for `$table`\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $create['Create Table'] . ";\n\n";
    
    // Get table data
    $stmt = $db->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($rows)) {
        echo "-- Data for table `$table`\n";
        echo "INSERT INTO `$table` VALUES\n";
        
        $values = [];
        foreach ($rows as $row) {
            $escaped = array_map(function($value) use ($db) {
                return $value === null ? 'NULL' : $db->quote($value);
            }, $row);
            $values[] = '(' . implode(',', $escaped) . ')';
        }
        
        echo implode(",\n", $values) . ";\n\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS = 1;\n";

logActivity('database_backup', "Database backup created: {$filename}");
exit();
?>