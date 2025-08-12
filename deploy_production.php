<?php
/**
 * Buffalo Marathon 2025 - Production Deployment Script
 * Run this on your production server to finalize setup
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== Buffalo Marathon 2025 - Production Deployment ===\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Create/Update default categories
    echo "📊 Setting up marathon categories...\n";
    
    $categories = [
        ['Full Marathon', '42 KM', 150.00, 'The ultimate challenge - 42.195km race through Lusaka', 500],
        ['Half Marathon', '21 KM', 100.00, 'Perfect for experienced runners - 21km scenic route', 800],
        ['Power Challenge', '10 KM', 75.00, 'Great for fitness enthusiasts - 10km energetic run', 1000],
        ['Family Fun Run', '5 KM', 50.00, 'Perfect for families and beginners - 5km easy pace', 1500],
        ['VIP Run', '5 KM', 200.00, 'Exclusive VIP experience with premium amenities', 100],
        ['Kid Run', '1 KM', 25.00, 'Special run for children under 12 years', 300]
    ];
    
    foreach ($categories as $index => $cat) {
        $stmt = $db->prepare("
            INSERT INTO categories (name, distance, fee, description, max_participants, sort_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
            fee = VALUES(fee), 
            description = VALUES(description),
            max_participants = VALUES(max_participants)
        ");
        $stmt->execute([$cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $index + 1]);
        echo "✅ {$cat[0]} - K{$cat[2]} (Max: {$cat[4]} participants)\n";
    }
    
    // 2. Create default settings
    echo "\n⚙️ Configuring system settings...\n";
    
    $settings = [
        'site_maintenance' => '0',
        'registration_open' => '1',
        'max_participants_total' => '4200',
        'contact_email' => 'info@buffalo-marathon.com',
        'contact_phone' => '+260 972 545 658 / +260 770 809 062 / +260 771 470 868',
        'event_status' => 'registration_open',
        'early_bird_active' => isEarlyBirdActive() ? '1' : '0',
        'social_facebook' => 'https://facebook.com/buffalomarathon2025',
        'social_twitter' => 'https://twitter.com/buffalomarathon',
        'social_instagram' => 'https://instagram.com/buffalomarathon2025',
        'payment_methods' => 'mobile_money,bank_transfer,cash',
        'terms_version' => '1.0',
        'privacy_version' => '1.0',
        'last_backup' => date('Y-m-d H:i:s')
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value)
        ");
        
        $description = match($key) {
            'site_maintenance' => 'Site maintenance mode (0=off, 1=on)',
            'registration_open' => 'Marathon registration status',
            'max_participants_total' => 'Maximum total participants allowed',
            'contact_email' => 'Primary contact email address',
            'contact_phone' => 'Contact phone numbers',
            'event_status' => 'Current event status',
            'early_bird_active' => 'Early bird pricing status',
            default => "System setting: $key"
        };
        
        $stmt->execute([$key, $value, $description]);
        echo "✅ $key: $value\n";
    }
    
    // 3. Create default schedule events
    echo "\n📅 Setting up event schedule...\n";
    
    $schedule_events = [
        ['2025-10-10', '08:00:00', 'Registration Opens', 'On-site registration and race pack collection begins', 1],
        ['2025-10-10', '18:00:00', 'Registration Closes', 'Last chance for on-site registration', 2],
        ['2025-10-11', '05:30:00', 'Gates Open', 'Buffalo Park Recreation Centre gates open to participants', 3],
        ['2025-10-11', '06:30:00', 'Warm-up Session', 'Group warm-up and final instructions', 4],
        ['2025-10-11', '07:00:00', 'Marathon Start', 'Full Marathon and Half Marathon race start', 5],
        ['2025-10-11', '07:15:00', '10KM Start', 'Power Challenge 10KM race start', 6],
        ['2025-10-11', '07:30:00', '5KM & VIP Start', 'Family Fun Run and VIP Run start', 7],
        ['2025-10-11', '08:00:00', 'Kids Run Start', 'Special run for children', 8],
        ['2025-10-11', '12:00:00', 'Awards Ceremony', 'Prize giving and closing ceremony', 9]
    ];
    
    // Check if schedules table exists
    $stmt = $db->query("SHOW TABLES LIKE 'schedules'");
    if ($stmt->rowCount() == 0) {
        $create_schedule = "
        CREATE TABLE schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_date DATE NOT NULL,
            event_time TIME NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            display_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($create_schedule);
        echo "✅ Created schedules table\n";
    }
    
    foreach ($schedule_events as $event) {
        $stmt = $db->prepare("
            INSERT INTO schedules (event_date, event_time, title, description, display_order) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            description = VALUES(description)
        ");
        $stmt->execute($event);
        echo "✅ {$event[2]} - {$event[0]} {$event[1]}\n";
    }
    
    // 4. Security setup
    echo "\n🔒 Finalizing security configuration...\n";
    
    // Check file permissions
    $secure_dirs = ['config', 'logs', 'cache', 'backups'];
    foreach ($secure_dirs as $dir) {
        if (is_dir($dir)) {
            chmod($dir, 0755);
            echo "✅ Secured directory: $dir\n";
        }
    }
    
    // Create .htaccess for sensitive directories
    $htaccess_content = "Require all denied\n";
    foreach (['logs', 'cache', 'backups', 'config'] as $dir) {
        if (is_dir($dir)) {
            file_put_contents("$dir/.htaccess", $htaccess_content);
            echo "✅ Protected directory: $dir\n";
        }
    }
    
    echo "\n🎉 Production deployment completed successfully!\n";
    echo "\n📋 Next Steps:\n";
    echo "1. Test the system using pre_launch_check.php\n";
    echo "2. Configure SSL certificate for HTTPS\n";
    echo "3. Set up automated backups\n";
    echo "4. Test email functionality\n";
    echo "5. Verify all contact information\n";
    echo "6. Delete this deployment script\n";
    
    echo "\n📞 Support Contact:\n";
    echo "Phone: +260 972 545 658 / +260 770 809 062 / +260 771 470 868\n";
    echo "Email: info@buffalo-marathon.com\n";
    
} catch (Exception $e) {
    echo "❌ Deployment failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}

echo "\n🗑️ Delete this file after successful deployment!\n";
?>
