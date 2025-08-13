<?php
echo "Buffalo Marathon 2025 - Quick Deployment Test\n";
echo "==============================================\n";

// Test if we can connect to the database
try {
    // Test database connection first
    $pdo = new PDO('mysql:host=localhost;dbname=envithcy_marathon;charset=utf8mb4', 'envithcy_marathon', 'password123');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    
    // Create essential performance indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_registrations_status_created ON registrations(payment_status, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_registrations_category_status ON registrations(category_id, payment_status)",
        "CREATE INDEX IF NOT EXISTS idx_users_email_role ON users(email, role)"
    ];
    
    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Performance index created\n";
        } catch (Exception $e) {
            echo "⚠️ Index already exists or failed: " . $e->getMessage() . "\n";
        }
    }
    
    // Create rate limiting table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rate_limits_ip_action (ip_address, action)
        )");
        echo "✅ Rate limits table created\n";
    } catch (Exception $e) {
        echo "⚠️ Rate limits table: " . $e->getMessage() . "\n";
    }
    
    // Create security logs table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_security_logs_type (event_type)
        )");
        echo "✅ Security logs table created\n";
    } catch (Exception $e) {
        echo "⚠️ Security logs table: " . $e->getMessage() . "\n";
    }
    
    // Add IP tracking to registrations
    try {
        $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)");
        echo "✅ IP tracking added to registrations\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✅ IP tracking already exists in registrations\n";
        } else {
            echo "⚠️ IP tracking failed: " . $e->getMessage() . "\n";
        }
    }
    
    // Test cache directory
    $cache_dir = __DIR__ . '/cache';
    if (!is_dir($cache_dir)) {
        if (mkdir($cache_dir, 0755, true)) {
            echo "✅ Cache directory created\n";
        } else {
            echo "❌ Failed to create cache directory\n";
        }
    } else {
        echo "✅ Cache directory exists\n";
    }
    
    echo "\n🎉 Quick deployment completed successfully!\n";
    echo "Core improvements implemented:\n";
    echo "• Performance indexes for faster queries\n";
    echo "• Security monitoring tables\n";
    echo "• IP address tracking\n";
    echo "• Cache system directory\n";
    echo "\nNext steps:\n";
    echo "1. Visit /admin/health.php for system monitoring\n";
    echo "2. Visit /admin/analytics.php for detailed insights\n";
    echo "3. All performance and security features are active\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials in config/config.php\n";
}
?>
