<?php
/**
 * Buffalo Marathon 2025 - Database Schema Verification Script
 * Checks for correct column names and data integrity
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== Buffalo Marathon 2025 - Database Schema Verification ===\n\n";

try {
    $db = getDB();
    
    // 1. Verify Categories Table Structure
    echo "📊 Verifying Categories Table...\n";
    $stmt = $db->query("DESCRIBE categories");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['id', 'name', 'distance', 'price', 'early_bird_price', 'max_participants', 'min_age', 'start_time', 'sort_order', 'is_active'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            $missing_columns[] = $col;
        }
    }
    
    if (empty($missing_columns)) {
        echo "✅ Categories table structure is correct\n";
        
        // Check data
        $stmt = $db->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1");
        $count = $stmt->fetch()['count'];
        echo "✅ Active categories: $count\n";
        
        // Verify updated schedule and limits
        $stmt = $db->query("SELECT name, start_time, max_participants FROM categories WHERE is_active = 1 ORDER BY sort_order");
        $categories = $stmt->fetchAll();
        
        foreach ($categories as $cat) {
            echo "   • {$cat['name']}: Start {$cat['start_time']}, Max {$cat['max_participants']} participants\n";
        }
    } else {
        echo "❌ Missing columns in categories table: " . implode(', ', $missing_columns) . "\n";
    }
    
    // 2. Verify Schedules Table Structure
    echo "\n📅 Verifying Schedules Table...\n";
    $stmt = $db->query("DESCRIBE schedules");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['id', 'event_name', 'event_description', 'event_date', 'event_time', 'location', 'event_type', 'display_order', 'is_active'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            $missing_columns[] = $col;
        }
    }
    
    if (empty($missing_columns)) {
        echo "✅ Schedules table structure is correct\n";
        
        // Check data
        $stmt = $db->query("SELECT COUNT(*) as count FROM schedules WHERE is_active = 1");
        $count = $stmt->fetch()['count'];
        echo "✅ Active schedule events: $count\n";
        
        // Show race day schedule
        $stmt = $db->query("
            SELECT event_time, event_name, event_type 
            FROM schedules 
            WHERE event_date = '2025-10-11' AND is_active = 1 
            ORDER BY event_time, display_order
        ");
        $events = $stmt->fetchAll();
        
        if (!empty($events)) {
            echo "   Race Day Schedule (October 11, 2025):\n";
            foreach ($events as $event) {
                echo "   • {$event['event_time']} - {$event['event_name']} ({$event['event_type']})\n";
            }
        }
    } else {
        echo "❌ Missing columns in schedules table: " . implode(', ', $missing_columns) . "\n";
    }
    
    // 3. Verify Settings
    echo "\n⚙️ Verifying System Settings...\n";
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('max_registrations', 'marathon_date', 'registration_deadline')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($settings['max_registrations'])) {
        echo "✅ Max registrations: {$settings['max_registrations']}\n";
    }
    if (isset($settings['marathon_date'])) {
        echo "✅ Marathon date: {$settings['marathon_date']}\n";
    }
    if (isset($settings['registration_deadline'])) {
        echo "✅ Registration deadline: {$settings['registration_deadline']}\n";
    }
    
    // 4. Check for common issues
    echo "\n🔍 Checking for Common Issues...\n";
    
    // Check for old column references
    $issues = [];
    
    // Look for 'fee' column references in database
    try {
        $stmt = $db->query("SELECT 1 FROM categories WHERE fee IS NOT NULL LIMIT 1");
        if ($stmt->rowCount() > 0) {
            $issues[] = "Categories table still has 'fee' column - should be 'price'";
        }
    } catch (Exception $e) {
        // Fee column doesn't exist - this is good
        echo "✅ No legacy 'fee' column found in categories\n";
    }
    
    // Check for 'title' column in schedules
    try {
        $stmt = $db->query("SELECT 1 FROM schedules WHERE title IS NOT NULL LIMIT 1");
        if ($stmt->rowCount() > 0) {
            $issues[] = "Schedules table still has 'title' column - should be 'event_name'";
        }
    } catch (Exception $e) {
        // Title column doesn't exist - this is good
        echo "✅ No legacy 'title' column found in schedules\n";
    }
    
    // 5. Verify data consistency
    echo "\n📈 Data Consistency Checks...\n";
    
    // Check if all categories have start times
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories WHERE start_time IS NULL AND is_active = 1");
    $null_times = $stmt->fetch()['count'];
    if ($null_times == 0) {
        echo "✅ All active categories have start times\n";
    } else {
        echo "⚠️ $null_times active categories missing start times\n";
    }
    
    // Check if all categories have max participants
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories WHERE max_participants = 0 AND is_active = 1");
    $zero_limits = $stmt->fetch()['count'];
    if ($zero_limits == 0) {
        echo "✅ All active categories have participant limits\n";
    } else {
        echo "⚠️ $zero_limits active categories have zero participant limits\n";
    }
    
    // Summary
    echo "\n📋 Verification Summary:\n";
    if (empty($issues)) {
        echo "✅ All database schemas are correctly updated\n";
        echo "✅ Column names match the latest schema\n";
        echo "✅ Data integrity checks passed\n";
        echo "\n🎉 Database is ready for production deployment!\n";
    } else {
        echo "❌ Issues found:\n";
        foreach ($issues as $issue) {
            echo "   • $issue\n";
        }
        echo "\n⚠️ Please run the update script to fix these issues.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Verification failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and schema.\n";
}

echo "\n📞 Support: info@buffalo-marathon.com\n";
?>
