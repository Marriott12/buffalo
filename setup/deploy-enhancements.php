<?php
/**
 * Buffalo Marathon 2025 - Complete System Enhancement Deployment
 * Executes all recommended improvements in the correct order
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/functions.php';

// Check if we're running this from command line or as admin
if (!isAdmin() && php_sapi_name() !== 'cli') {
    die('Admin access required');
}

echo "🏃‍♂️ Buffalo Marathon 2025 - System Enhancement Deployment\n";
echo "============================================================\n\n";

$start_time = microtime(true);
$errors = [];
$successes = [];

try {
    $db = getDB();
    
    echo "1️⃣  PERFORMANCE OPTIMIZATIONS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Create performance indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_registrations_status_created ON registrations(payment_status, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_registrations_category_status ON registrations(category_id, payment_status)",
        "CREATE INDEX IF NOT EXISTS idx_users_email_role ON users(email, role)",
        "CREATE INDEX IF NOT EXISTS idx_users_created ON users(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs(created_at)"
    ];
    
    foreach ($indexes as $sql) {
        try {
            $db->exec($sql);
            echo "   ✅ Performance index created\n";
            $successes[] = "Performance index created";
        } catch (Exception $e) {
            echo "   ❌ Index creation failed: " . $e->getMessage() . "\n";
            $errors[] = "Index creation failed: " . $e->getMessage();
        }
    }
    
    echo "\n2️⃣  SECURITY ENHANCEMENTS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Create security tables
    $security_tables = [
        "rate_limits" => "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rate_limits_ip_action (ip_address, action),
            INDEX idx_rate_limits_created (created_at)
        )",
        "security_logs" => "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_security_logs_type (event_type),
            INDEX idx_security_logs_ip (ip_address),
            INDEX idx_security_logs_created (created_at)
        )",
        "ip_blocks" => "CREATE TABLE IF NOT EXISTS ip_blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            blocked_until TIMESTAMP NOT NULL,
            reason VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_blocks_until (blocked_until),
            INDEX idx_ip_blocks_ip (ip_address)
        )",
        "login_attempts" => "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255),
            ip_address VARCHAR(45) NOT NULL,
            success BOOLEAN DEFAULT FALSE,
            user_agent TEXT,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_login_attempts_email (email),
            INDEX idx_login_attempts_ip (ip_address),
            INDEX idx_login_attempts_time (attempted_at)
        )",
        "user_sessions" => "CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_sessions_user (user_id),
            INDEX idx_user_sessions_activity (last_activity)
        )"
    ];
    
    foreach ($security_tables as $table_name => $sql) {
        try {
            $db->exec($sql);
            echo "   ✅ Security table '$table_name' created\n";
            $successes[] = "Security table '$table_name' created";
        } catch (Exception $e) {
            echo "   ❌ Security table '$table_name' failed: " . $e->getMessage() . "\n";
            $errors[] = "Security table '$table_name' failed: " . $e->getMessage();
        }
    }
    
    echo "\n3️⃣  DATABASE STRUCTURE UPDATES\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Add IP tracking columns
    try {
        $db->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)");
        echo "   ✅ IP tracking added to registrations\n";
        $successes[] = "IP tracking added to registrations";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ IP tracking already exists in registrations\n";
            $successes[] = "IP tracking already exists in registrations";
        } else {
            echo "   ❌ IP tracking failed for registrations: " . $e->getMessage() . "\n";
            $errors[] = "IP tracking failed for registrations: " . $e->getMessage();
        }
    }
    
    try {
        $db->exec("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)");
        echo "   ✅ IP tracking added to activity_logs\n";
        $successes[] = "IP tracking added to activity_logs";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ✅ IP tracking already exists in activity_logs\n";
            $successes[] = "IP tracking already exists in activity_logs";
        } else {
            echo "   ❌ IP tracking failed for activity_logs: " . $e->getMessage() . "\n";
            $errors[] = "IP tracking failed for activity_logs: " . $e->getMessage();
        }
    }
    
    echo "\n4️⃣  PERFORMANCE VIEWS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Create dashboard views
    try {
        $db->exec("CREATE OR REPLACE VIEW v_registration_summary AS
        SELECT 
            c.name as category_name,
            c.max_participants,
            COUNT(r.id) as registered_count,
            SUM(CASE WHEN r.payment_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN r.payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN r.payment_status = 'confirmed' THEN r.amount ELSE 0 END) as revenue,
            (c.max_participants - COUNT(CASE WHEN r.payment_status = 'confirmed' THEN r.id END)) as spots_remaining
        FROM categories c
        LEFT JOIN registrations r ON c.id = r.category_id
        GROUP BY c.id, c.name, c.max_participants");
        echo "   ✅ Registration summary view created\n";
        $successes[] = "Registration summary view created";
    } catch (Exception $e) {
        echo "   ❌ Registration summary view failed: " . $e->getMessage() . "\n";
        $errors[] = "Registration summary view failed: " . $e->getMessage();
    }
    
    try {
        $db->exec("CREATE OR REPLACE VIEW v_dashboard_stats AS
        SELECT 
            (SELECT COUNT(*) FROM registrations WHERE payment_status = 'confirmed') as total_confirmed,
            (SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending') as total_pending,
            (SELECT SUM(amount) FROM registrations WHERE payment_status = 'confirmed') as total_revenue,
            (SELECT COUNT(*) FROM users WHERE role = 'participant') as total_users,
            (SELECT COUNT(*) FROM registrations WHERE created_at >= CURDATE()) as registrations_today,
            (SELECT COUNT(*) FROM registrations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as registrations_week");
        echo "   ✅ Dashboard stats view created\n";
        $successes[] = "Dashboard stats view created";
    } catch (Exception $e) {
        echo "   ❌ Dashboard stats view failed: " . $e->getMessage() . "\n";
        $errors[] = "Dashboard stats view failed: " . $e->getMessage();
    }
    
    echo "\n5️⃣  TABLE OPTIMIZATION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $tables = ['users', 'registrations', 'categories', 'activity_logs'];
    foreach ($tables as $table) {
        try {
            $db->exec("OPTIMIZE TABLE $table");
            echo "   ✅ Optimized table '$table'\n";
            $successes[] = "Optimized table '$table'";
        } catch (Exception $e) {
            echo "   ❌ Optimization failed for '$table': " . $e->getMessage() . "\n";
            $errors[] = "Optimization failed for '$table': " . $e->getMessage();
        }
    }
    
    echo "\n6️⃣  CACHE SYSTEM SETUP\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Ensure cache directory exists and is writable
    $cache_dir = dirname(__DIR__) . '/cache';
    if (!is_dir($cache_dir)) {
        if (mkdir($cache_dir, 0755, true)) {
            echo "   ✅ Cache directory created\n";
            $successes[] = "Cache directory created";
        } else {
            echo "   ❌ Failed to create cache directory\n";
            $errors[] = "Failed to create cache directory";
        }
    } else {
        echo "   ✅ Cache directory already exists\n";
        $successes[] = "Cache directory already exists";
    }
    
    // Test cache functionality
    try {
        require_once __DIR__ . '/../includes/cache.php';
        CacheManager::set('test_key', 'test_value', 60);
        $test_value = CacheManager::get('test_key');
        if ($test_value === 'test_value') {
            echo "   ✅ Cache system functional\n";
            $successes[] = "Cache system functional";
            CacheManager::clear('test_key');
        } else {
            throw new Exception("Cache test failed");
        }
    } catch (Exception $e) {
        echo "   ❌ Cache system test failed: " . $e->getMessage() . "\n";
        $errors[] = "Cache system test failed: " . $e->getMessage();
    }
    
    echo "\n7️⃣  MONITORING SYSTEM\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Test monitoring system
    try {
        require_once __DIR__ . '/../includes/monitoring.php';
        $health = SystemHealthMonitor::getSystemHealth();
        if ($health && isset($health['overall_status'])) {
            echo "   ✅ System health monitoring active\n";
            echo "   📊 System status: " . strtoupper($health['overall_status']) . "\n";
            $successes[] = "System health monitoring active";
        } else {
            throw new Exception("Health monitoring test failed");
        }
    } catch (Exception $e) {
        echo "   ❌ Monitoring system failed: " . $e->getMessage() . "\n";
        $errors[] = "Monitoring system failed: " . $e->getMessage();
    }
    
    echo "\n8️⃣  SECURITY SYSTEM TEST\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Test enhanced security
    try {
        require_once __DIR__ . '/../includes/security_enhanced.php';
        $rate_limit_test = SecurityEnhancer::checkAdvancedRateLimit('test_action', 10, 300);
        if ($rate_limit_test) {
            echo "   ✅ Enhanced security system active\n";
            $successes[] = "Enhanced security system active";
        } else {
            throw new Exception("Security system test failed");
        }
    } catch (Exception $e) {
        echo "   ❌ Security system failed: " . $e->getMessage() . "\n";
        $errors[] = "Security system failed: " . $e->getMessage();
    }
    
    echo "\n9️⃣  FINAL SYSTEM VALIDATION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Test database connectivity
    try {
        $test_query = $db->query("SELECT COUNT(*) FROM users");
        $user_count = $test_query->fetchColumn();
        echo "   ✅ Database connectivity verified ($user_count users)\n";
        $successes[] = "Database connectivity verified";
    } catch (Exception $e) {
        echo "   ❌ Database test failed: " . $e->getMessage() . "\n";
        $errors[] = "Database test failed: " . $e->getMessage();
    }
    
    // Clear any existing cache to ensure fresh start
    try {
        CacheManager::clear();
        echo "   ✅ Cache cleared for fresh start\n";
        $successes[] = "Cache cleared for fresh start";
    } catch (Exception $e) {
        echo "   ⚠️  Cache clear warning: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    $errors[] = "Critical error: " . $e->getMessage();
}

$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

echo "\n🎯 DEPLOYMENT SUMMARY\n";
echo "══════════════════════\n";
echo "⏱️  Execution Time: {$execution_time} seconds\n";
echo "✅ Successes: " . count($successes) . "\n";
echo "❌ Errors: " . count($errors) . "\n";

if (!empty($successes)) {
    echo "\n✅ SUCCESSFUL OPERATIONS:\n";
    foreach ($successes as $success) {
        echo "   • $success\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ FAILED OPERATIONS:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

$success_rate = round((count($successes) / (count($successes) + count($errors))) * 100, 1);
echo "\n📊 Success Rate: {$success_rate}%\n";

if ($success_rate >= 90) {
    echo "\n🎉 DEPLOYMENT SUCCESSFUL!\n";
    echo "Your Buffalo Marathon 2025 system has been enhanced with:\n";
    echo "• Performance optimizations (caching, indexes)\n";
    echo "• Advanced security monitoring\n";
    echo "• Real-time system health dashboard\n";
    echo "• Enhanced analytics and reporting\n";
    echo "• Rate limiting and IP tracking\n";
    echo "\n🌟 System is production-ready and optimized!\n";
} else {
    echo "\n⚠️  DEPLOYMENT COMPLETED WITH ISSUES\n";
    echo "Please review the errors above and run the deployment again if needed.\n";
}

echo "\n🔗 NEXT STEPS:\n";
echo "1. Visit /admin/health.php to monitor system health\n";
echo "2. Visit /admin/analytics.php for detailed insights\n";
echo "3. Set up automated cache clearing cron job\n";
echo "4. Review security logs regularly\n";
echo "5. Monitor registration capacity alerts\n";

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🏃‍♂️ Buffalo Marathon 2025 - Enhancement Deployment Complete!\n";
echo "═══════════════════════════════════════════════════════════\n";
?>
