<?php
/**
 * Update Contact Information in Database Settings
 * Run this once to update the contact information in the database
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== Updating Contact Information ===\n";
    
    // Update contact phone settings
    $contact_updates = [
        'contact_phone' => '+260 972 545 658 / +260 770 809 062 / +260 771 470 868',
        'contact_phone_primary' => '+260 972 545 658',
        'contact_phone_secondary' => '+260 770 809 062',
        'contact_phone_tertiary' => '+260 771 470 868',
        'site_url' => 'https://buffalo-marathon.com',
        'contact_email' => 'info@buffalo-marathon.com',
        'admin_email' => 'admin@buffalo-marathon.com',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Check if settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        // Create settings table
        $create_settings = "
        CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($create_settings);
        echo "✅ Settings table created\n";
    }
    
    foreach ($contact_updates as $key => $value) {
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $description = match($key) {
            'contact_phone' => 'All contact phone numbers',
            'contact_phone_primary' => 'Primary contact phone',
            'contact_phone_secondary' => 'Secondary contact phone',
            'contact_phone_tertiary' => 'Tertiary contact phone',
            'site_url' => 'Official website URL',
            'contact_email' => 'Contact email address',
            'admin_email' => 'Administrator email address',
            'last_updated' => 'Last configuration update',
            default => "System setting: $key"
        };
        
        $stmt->execute([$key, $value, $description]);
        echo "✅ Updated $key: $value\n";
    }
    
    echo "\n=== Contact Information Updated Successfully ===\n";
    echo "Primary Phone: +260 972 545 658\n";
    echo "Secondary Phone: +260 770 809 062\n";
    echo "Tertiary Phone: +260 771 470 868\n";
    echo "Website: https://buffalo-marathon.com\n";
    echo "Email: info@buffalo-marathon.com\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🗑️ Delete this file after running for security!\n";
?>
