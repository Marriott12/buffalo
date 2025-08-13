<?php
/**
 * Buffalo Marathon 2025 - Database Setup Script
 * Executes optimization and security table creation
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Check if we're running this from command line or as admin
if (!isAdmin() && php_sapi_name() !== 'cli') {
    die('Admin access required');
}

echo "Buffalo Marathon 2025 - Database Setup\n";
echo "=====================================\n\n";

try {
    $db = getDB();
    
    echo "1. Creating performance indexes...\n";
    
    // Performance indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_registrations_status_created ON registrations(payment_status, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_registrations_category_status ON registrations(category_id, payment_status)",
        "CREATE INDEX IF NOT EXISTS idx_users_email_role ON users(email, role)",
        "CREATE INDEX IF NOT EXISTS idx_users_created ON users(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs(created_at)"
    ];
    
    foreach ($indexes as $sql) {
        $db->exec($sql);
        echo "   ✓ Index created\n";
    }
    
    echo "\n2. Creating security tables...\n";
    
    // Rate limiting table
    $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        action VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_rate_limits_ip_action (ip_address, action),
        INDEX idx_rate_limits_created (created_at)
    )");
    echo "   ✓ Rate limits table created\n";
    
    // Security logs table
    $db->exec("CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        details JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_security_logs_type (event_type),
        INDEX idx_security_logs_ip (ip_address),
        INDEX idx_security_logs_created (created_at)
    )");
    echo "   ✓ Security logs table created\n";
    
    // IP blocks table
    $db->exec("CREATE TABLE IF NOT EXISTS ip_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        blocked_until TIMESTAMP NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_blocks_until (blocked_until),
        INDEX idx_ip_blocks_ip (ip_address)
    )");
    echo "   ✓ IP blocks table created\n";
    
    // Login attempts table
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255),
        ip_address VARCHAR(45) NOT NULL,
        success BOOLEAN DEFAULT FALSE,
        user_agent TEXT,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_attempts_email (email),
        INDEX idx_login_attempts_ip (ip_address),
        INDEX idx_login_attempts_time (attempted_at)
    )");
    echo "   ✓ Login attempts table created\n";
    
    // User sessions table
    $db->exec("CREATE TABLE IF NOT EXISTS user_sessions (
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
    )");
    echo "   ✓ User sessions table created\n";
    
    echo "\n3. Adding IP address tracking to existing tables...\n";
    
    // Add IP tracking columns
    try {
        $db->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)");
        echo "   ✓ IP tracking added to registrations\n";
    } catch (Exception $e) {
        echo "   ✓ IP tracking already exists in registrations\n";
    }
    
    try {
        $db->exec("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)");
        echo "   ✓ IP tracking added to activity_logs\n";
    } catch (Exception $e) {
        echo "   ✓ IP tracking already exists in activity_logs\n";
    }
    
    echo "\n4. Creating dashboard views...\n";
    
    // Registration summary view
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
    echo "   ✓ Registration summary view created\n";
    
    // Dashboard stats view
    $db->exec("CREATE OR REPLACE VIEW v_dashboard_stats AS
    SELECT 
        (SELECT COUNT(*) FROM registrations WHERE payment_status = 'confirmed') as total_confirmed,
        (SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending') as total_pending,
        (SELECT SUM(amount) FROM registrations WHERE payment_status = 'confirmed') as total_revenue,
        (SELECT COUNT(*) FROM users WHERE role = 'participant') as total_users,
        (SELECT COUNT(*) FROM registrations WHERE created_at >= CURDATE()) as registrations_today,
        (SELECT COUNT(*) FROM registrations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as registrations_week");
    echo "   ✓ Dashboard stats view created\n";
    
    echo "\n5. Optimizing tables...\n";
    
    $tables = ['users', 'registrations', 'categories', 'activity_logs'];
    foreach ($tables as $table) {
        $db->exec("OPTIMIZE TABLE $table");
        echo "   ✓ Optimized $table\n";
    }
    
    echo "\n✅ Database setup completed successfully!\n";
    echo "\nPerformance improvements:\n";
    echo "- Added performance indexes for faster queries\n";
    echo "- Created security monitoring tables\n";
    echo "- Added IP address tracking\n";
    echo "- Created dashboard views for better performance\n";
    echo "- Optimized existing tables\n";
    
} catch (Exception $e) {
    echo "\n❌ Error during setup: " . $e->getMessage() . "\n";
    exit(1);
}
?>
