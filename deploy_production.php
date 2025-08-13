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
        ['Full Marathon', '42.2 KM', 200.00, 150.00, 'The ultimate challenge for serious runners. Experience the full Buffalo Marathon distance.', 1200, 18, '05:30:00'],
        ['Half Marathon', '21.1 KM', 150.00, 120.00, 'Perfect for intermediate runners looking for a challenging but achievable distance.', 1200, 16, '05:30:00'],
        ['Power Challenge', '10 KM', 100.00, 80.00, 'High-intensity 10K race for competitive runners and fitness enthusiasts.', 1200, 14, '06:00:00'],
        ['Family Fun Run', '5 KM', 75.00, 60.00, 'A fun run perfect for families, beginners, and those looking for a social running experience.', 1200, 8, '06:00:00'],
        ['VIP Run', '5 KM', 250.00, 200.00, 'Premium race experience with exclusive amenities and special treatment.', 1200, 18, '06:30:00'],
        ['Kid Run', '1 KM', 25.00, 20.00, 'Special race designed for children to experience the joy of running.', 1200, 5, '06:00:00']
    ];
    
    foreach ($categories as $index => $cat) {
        $stmt = $db->prepare("
            INSERT INTO categories (name, distance, price, early_bird_price, description, max_participants, min_age, start_time, sort_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
            price = VALUES(price), 
            early_bird_price = VALUES(early_bird_price),
            description = VALUES(description),
            max_participants = VALUES(max_participants),
            min_age = VALUES(min_age),
            start_time = VALUES(start_time)
        ");
        $stmt->execute([$cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $cat[5], $cat[6], $cat[7], $index + 1]);
        echo "✅ {$cat[0]} - K{$cat[2]} (Early: K{$cat[3]}, Max: {$cat[5]} participants, Start: {$cat[7]})\n";
    }
    
    // 2. Create default settings
    echo "\n⚙️ Configuring system settings...\n";
    
    $settings = [
        'site_maintenance' => '0',
        'registration_open' => '1',
        'max_participants_total' => '7200',
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
        ['2025-10-01', '08:30:00', 'Race Pack Collection Day 1', 'Collect your race materials and bib number', 1],
        ['2025-10-02', '08:30:00', 'Race Pack Collection Day 2', 'Collect your race materials and bib number', 2],
        ['2025-10-03', '08:30:00', 'Race Pack Collection Day 3', 'Collect your race materials and bib number', 3],
        ['2025-10-04', '08:30:00', 'Race Pack Collection Day 4', 'Collect your race materials and bib number', 4],
        ['2025-10-05', '08:30:00', 'Race Pack Collection Day 5', 'Collect your race materials and bib number', 5],
        ['2025-10-11', '05:30:00', 'Marathon Start', 'Full Marathon and Half Marathon start', 6],
        ['2025-10-11', '06:00:00', 'Power Challenge Start', '10KM Power Challenge begins', 7],
        ['2025-10-11', '06:00:00', 'Family Fun Run Start', '5KM Family Fun Run begins', 8],
        ['2025-10-11', '06:00:00', 'Kid Run Start', '1KM Kid Run begins', 9],
        ['2025-10-11', '06:30:00', 'VIP Run Start', '5KM VIP Run begins', 10],
        ['2025-10-11', '11:00:00', 'Post-Race Celebration', 'Food, drinks, and celebration', 11],
        ['2025-10-11', '11:30:00', 'Awards Ceremony', 'Prize giving and medal ceremony', 12],
        ['2025-10-11', '13:00:00', 'Live Entertainment', 'Zambia Army Pop Band performance', 13]
    ];
    
    // Check if schedules table exists
    $stmt = $db->query("SHOW TABLES LIKE 'schedules'");
    if ($stmt->rowCount() == 0) {
        $create_schedule = "
        CREATE TABLE schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_name VARCHAR(255) NOT NULL,
            event_description TEXT,
            event_date DATE NOT NULL,
            event_time TIME NOT NULL,
            end_time TIME,
            location VARCHAR(255),
            category_id INT,
            event_type ENUM('race', 'activity', 'ceremony', 'registration', 'other') DEFAULT 'other',
            is_active BOOLEAN DEFAULT TRUE,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date (event_date),
            INDEX idx_time (event_time),
            INDEX idx_active (is_active),
            INDEX idx_type (event_type),
            INDEX idx_order (display_order)
        )";
        $db->exec($create_schedule);
        echo "✅ Created schedules table\n";
    }
    
    foreach ($schedule_events as $event) {
        $stmt = $db->prepare("
            INSERT INTO schedules (event_date, event_time, event_name, event_description, display_order, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
            event_description = VALUES(event_description)
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
