<?php
/**
 * Buffalo Marathon 2025 - Quick Database Status Check
 * Run this anytime to verify database health
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'config/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Buffalo Marathon 2025 - Database Status Check\n";
echo "=============================================\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Test database connection
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Database Connection: SUCCESS\n";
    echo "📊 MySQL Version: " . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";
    echo "🗃️  Database: " . DB_NAME . "\n\n";
    
    // Check required tables
    $required_tables = [
        'users', 'categories', 'registrations', 'payments', 
        'settings', 'announcements', 'email_queue', 'audit_log',
        'security_events', 'ip_blocks', 'system_metrics', 'analytics_events'
    ];
    
    $existing_tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Table Status:\n";
    echo "================\n";
    
    $table_status = true;
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "✅ $table ($count records)\n";
        } else {
            echo "❌ $table (MISSING)\n";
            $table_status = false;
        }
    }
    
    echo "\n📈 Data Summary:\n";
    echo "================\n";
    
    // Get key statistics
    if (in_array('users', $existing_tables)) {
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $admin_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
        echo "👥 Users: $total_users total, $active_users active, $admin_users admins\n";
    }
    
    if (in_array('categories', $existing_tables)) {
        $total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $active_categories = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
        echo "🏃 Categories: $total_categories total, $active_categories active\n";
    }
    
    if (in_array('registrations', $existing_tables)) {
        $total_registrations = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
        $confirmed_registrations = $pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'confirmed'")->fetchColumn();
        $paid_registrations = $pdo->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid'")->fetchColumn();
        echo "📝 Registrations: $total_registrations total, $confirmed_registrations confirmed, $paid_registrations paid\n";
    }
    
    if (in_array('settings', $existing_tables)) {
        $total_settings = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        echo "⚙️  Settings: $total_settings configured\n";
    }
    
    if (in_array('email_queue', $existing_tables)) {
        $pending_emails = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
        $sent_emails = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'")->fetchColumn();
        echo "📧 Email Queue: $pending_emails pending, $sent_emails sent\n";
    }
    
    echo "\n🔧 System Health:\n";
    echo "=================\n";
    
    // Check system health indicators
    $registration_open = time() < strtotime(REGISTRATION_DEADLINE);
    echo ($registration_open ? "✅" : "⚠️ ") . " Registration Status: " . ($registration_open ? "OPEN" : "CLOSED") . "\n";
    
    $early_bird_active = time() < strtotime(EARLY_BIRD_DEADLINE);
    echo ($early_bird_active ? "✅" : "⚠️ ") . " Early Bird: " . ($early_bird_active ? "ACTIVE" : "EXPIRED") . "\n";
    
    $days_until_marathon = ceil((strtotime(MARATHON_DATE) - time()) / (60 * 60 * 24));
    echo "📅 Days Until Marathon: $days_until_marathon\n";
    
    $days_until_deadline = ceil((strtotime(REGISTRATION_DEADLINE) - time()) / (60 * 60 * 24));
    echo "⏰ Days Until Registration Deadline: $days_until_deadline\n";
    
    echo "\n🎯 Overall Status:\n";
    echo "==================\n";
    
    if ($table_status && $total_users > 0 && $total_categories > 0) {
        echo "🎉 EXCELLENT: Database is fully configured and ready!\n";
        echo "✅ All required tables exist\n";
        echo "✅ Default data is loaded\n";
        echo "✅ System is operational\n";
    } elseif ($table_status) {
        echo "👍 GOOD: Database structure ready, needs initial data\n";
        echo "✅ All required tables exist\n";
        echo "⚠️  Run setup_database_complete.php to load default data\n";
    } else {
        echo "🚨 ATTENTION NEEDED: Missing required tables\n";
        echo "❌ Database structure incomplete\n";
        echo "🔧 Run setup_database_complete.php to fix\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "🔧 Troubleshooting:\n";
    echo "===================\n";
    echo "1. Check if WAMP/XAMPP is running\n";
    echo "2. Verify MySQL service is started\n";
    echo "3. Check database credentials in config/config.php\n";
    echo "4. Ensure database '" . DB_NAME . "' exists\n";
    echo "5. Run setup_database_complete.php to create database\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Status check completed at " . date('Y-m-d H:i:s') . "\n";
?>
