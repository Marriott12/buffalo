<?php
/**
 * Buffalo Marathon 2025 - Backup Script
 * Create full system backup before launch
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'config/config.php';

$backup_date = date('Y-m-d_H-i-s');
$backup_dir = __DIR__ . '/backups';

echo "=== Buffalo Marathon 2025 - System Backup ===\n";
echo "Backup Date: " . date('Y-m-d H:i:s') . "\n";
echo "Backup Directory: $backup_dir\n\n";

// Create backup directory if it doesn't exist
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    echo "✅ Created backup directory\n";
}

try {
    // 1. Database Backup
    echo "📊 Creating database backup...\n";
    
    $db_backup_file = "$backup_dir/database_backup_$backup_date.sql";
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_host = DB_HOST;
    
    // Create mysqldump command
    $mysqldump_cmd = "mysqldump -h $db_host -u $db_user";
    if (!empty($db_pass)) {
        $mysqldump_cmd .= " -p$db_pass";
    }
    $mysqldump_cmd .= " $db_name > $db_backup_file 2>&1";
    
    // Try to run mysqldump
    $output = [];
    $return_code = 0;
    exec($mysqldump_cmd, $output, $return_code);
    
    if ($return_code === 0 && file_exists($db_backup_file)) {
        $db_size = round(filesize($db_backup_file) / 1024, 2);
        echo "✅ Database backup created: $db_backup_file ($db_size KB)\n";
    } else {
        echo "⚠️ mysqldump not available, using PHP backup method...\n";
        
        // PHP-based backup
        $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $backup_content = "-- Buffalo Marathon Database Backup\n";
        $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $backup_content .= "\n-- Table: $table\n";
            
            // Get CREATE TABLE statement
            $create_stmt = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $backup_content .= $create_stmt['Create Table'] . ";\n\n";
            
            // Get data
            $data = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($data)) {
                foreach ($data as $row) {
                    $values = array_map(function($v) use ($db) {
                        return $v === null ? 'NULL' : $db->quote($v);
                    }, $row);
                    $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            $backup_content .= "\n";
        }
        
        file_put_contents($db_backup_file, $backup_content);
        $db_size = round(filesize($db_backup_file) / 1024, 2);
        echo "✅ PHP Database backup created: $db_backup_file ($db_size KB)\n";
    }
    
    // 2. File System Backup
    echo "\n📁 Creating file system backup...\n";
    
    $files_backup_file = "$backup_dir/files_backup_$backup_date.tar.gz";
    
    // List of directories/files to backup
    $backup_items = [
        'config',
        'includes',
        'assets',
        'admin',
        '*.php',
        '.htaccess',
        'robots.txt',
        'manifest.json',
        'sitemap.xml'
    ];
    
    $tar_cmd = "tar -czf $files_backup_file " . implode(' ', $backup_items) . " 2>/dev/null";
    exec($tar_cmd, $output, $return_code);
    
    if (file_exists($files_backup_file)) {
        $files_size = round(filesize($files_backup_file) / 1024 / 1024, 2);
        echo "✅ Files backup created: $files_backup_file ({$files_size} MB)\n";
    } else {
        echo "⚠️ tar command not available, creating ZIP backup...\n";
        
        // PHP-based ZIP backup
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zip_file = "$backup_dir/files_backup_$backup_date.zip";
            
            if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator('.'),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $file) {
                    if (!$file->isDir() && !strpos($file->getPathname(), '/backups/')) {
                        $relativePath = substr($file->getPathname(), 2);
                        $zip->addFile($file->getPathname(), $relativePath);
                    }
                }
                
                $zip->close();
                $zip_size = round(filesize($zip_file) / 1024 / 1024, 2);
                echo "✅ ZIP backup created: $zip_file ({$zip_size} MB)\n";
            }
        } else {
            echo "❌ No backup method available for files\n";
        }
    }
    
    // 3. Configuration Backup
    echo "\n⚙️ Creating configuration backup...\n";
    
    $config_backup = [
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'database' => [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'charset' => DB_CHARSET
        ],
        'site' => [
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'email' => SITE_EMAIL,
            'environment' => ENVIRONMENT
        ],
        'marathon' => [
            'date' => MARATHON_DATE,
            'time' => MARATHON_TIME,
            'venue' => EVENT_VENUE,
            'address' => EVENT_ADDRESS
        ],
        'contact' => [
            'primary_phone' => CONTACT_PHONE_PRIMARY ?? '',
            'secondary_phone' => CONTACT_PHONE_SECONDARY ?? '',
            'tertiary_phone' => CONTACT_PHONE_TERTIARY ?? ''
        ]
    ];
    
    $config_file = "$backup_dir/config_backup_$backup_date.json";
    file_put_contents($config_file, json_encode($config_backup, JSON_PRETTY_PRINT));
    echo "✅ Configuration backup created: $config_file\n";
    
    // 4. Create restore instructions
    echo "\n📋 Creating restore instructions...\n";
    
    $restore_instructions = "
# Buffalo Marathon 2025 - Restore Instructions
# Generated: " . date('Y-m-d H:i:s') . "

## Database Restore:
mysql -h " . DB_HOST . " -u " . DB_USER . " -p " . DB_NAME . " < database_backup_$backup_date.sql

## Files Restore:
tar -xzf files_backup_$backup_date.tar.gz

## Configuration:
Review config_backup_$backup_date.json for settings

## Post-Restore Checklist:
1. Update config/config.php with correct database credentials
2. Set proper file permissions (755 for directories, 644 for files)
3. Test database connection
4. Verify email configuration
5. Check all contact information
6. Test registration process
7. Verify admin panel access

## Support:
Phone: +260 972 545 658 / +260 770 809 062 / +260 771 470 868
Email: info@buffalo-marathon.com
";
    
    $restore_file = "$backup_dir/RESTORE_INSTRUCTIONS_$backup_date.txt";
    file_put_contents($restore_file, $restore_instructions);
    echo "✅ Restore instructions created: $restore_file\n";
    
    echo "\n🎉 Backup completed successfully!\n";
    echo "\n📊 Backup Summary:\n";
    echo "- Database: " . (file_exists($db_backup_file) ? "✅ Created" : "❌ Failed") . "\n";
    echo "- Files: " . (file_exists($files_backup_file) ? "✅ Created" : "❌ Failed") . "\n";
    echo "- Configuration: ✅ Created\n";
    echo "- Instructions: ✅ Created\n";
    
    echo "\n💾 Backup Location: $backup_dir\n";
    echo "🔒 Keep backups secure and test restore process!\n";
    
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
}

echo "\n🗑️ You can safely delete this script after backup!\n";
?>
