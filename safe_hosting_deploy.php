<?php
/**
 * Buffalo Marathon 2025 - Safe Hosting Deployment Script
 * This script safely updates the hosting server without disrupting existing data
 */

define('BUFFALO_SECURE_ACCESS', true);

echo "=== Buffalo Marathon 2025 - Safe Hosting Deployment ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "WARNING: This will update code files only, preserving data and settings\n\n";

// Configuration
$backup_dir = __DIR__ . '/deployment_backups';
$temp_dir = __DIR__ . '/temp_deployment';
$deployment_date = date('Y-m-d_H-i-s');

try {
    // 1. Create backup directory
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
        echo "✅ Created backup directory: $backup_dir\n";
    }
    
    // 2. Backup current files (code only, not data)
    echo "📦 Creating backup of current files...\n";
    
    $files_to_backup = [
        'config/config.php',
        'includes/',
        'admin/',
        'assets/',
        '*.php',
        '.htaccess'
    ];
    
    $backup_file = "$backup_dir/pre_deployment_backup_$deployment_date.tar.gz";
    $tar_cmd = "tar -czf $backup_file " . implode(' ', $files_to_backup) . " 2>/dev/null";
    exec($tar_cmd, $output, $return_code);
    
    if (file_exists($backup_file)) {
        echo "✅ Backup created: $backup_file\n";
    } else {
        throw new Exception("Failed to create backup");
    }
    
    // 3. Check if this is a fresh installation or update
    $is_fresh_install = !file_exists('config/config.php') || !is_dir('admin');
    
    if ($is_fresh_install) {
        echo "🆕 Fresh installation detected\n";
    } else {
        echo "🔄 Existing installation detected - will preserve data\n";
        
        // Backup current database configuration
        if (file_exists('config/config.php')) {
            $current_config = file_get_contents('config/config.php');
            file_put_contents("$backup_dir/current_config_$deployment_date.php", $current_config);
            echo "✅ Current configuration backed up\n";
        }
    }
    
    // 4. Download latest from GitHub (simulated - in real deployment, you'd git pull or download)
    echo "📥 Preparing to update files from repository...\n";
    
    // In real deployment, you would run:
    // git pull origin main
    // Or download and extract files from GitHub
    
    echo "⚠️  DEPLOYMENT INSTRUCTIONS:\n";
    echo "1. Run 'git pull origin main' to get latest code\n";
    echo "2. OR download latest files from GitHub and extract\n";
    echo "3. Preserve these files/directories:\n";
    echo "   - Database (don't recreate)\n";
    echo "   - uploads/ directory (user uploads)\n";
    echo "   - logs/ directory (system logs)\n";
    echo "   - Any custom configuration in config.php\n\n";
    
    // 5. Post-deployment checks
    echo "🔍 Post-deployment verification checklist:\n";
    echo "- [ ] Website loads correctly\n";
    echo "- [ ] Database connection works\n";
    echo "- [ ] Admin panel accessible\n";
    echo "- [ ] User registration works\n";
    echo "- [ ] Email system functional\n";
    echo "- [ ] Contact information correct\n";
    echo "- [ ] SSL certificate working\n\n";
    
    // 6. Create rollback script
    $rollback_script = "#!/bin/bash\n";
    $rollback_script .= "# Buffalo Marathon Rollback Script\n";
    $rollback_script .= "# Generated: $deployment_date\n\n";
    $rollback_script .= "echo 'Rolling back Buffalo Marathon deployment...'\n";
    $rollback_script .= "tar -xzf $backup_file\n";
    $rollback_script .= "echo 'Rollback completed. Please test the website.'\n";
    
    file_put_contents("$backup_dir/rollback_$deployment_date.sh", $rollback_script);
    chmod("$backup_dir/rollback_$deployment_date.sh", 0755);
    echo "✅ Rollback script created: $backup_dir/rollback_$deployment_date.sh\n";
    
    echo "\n🎉 Deployment preparation completed!\n";
    echo "📋 Next steps:\n";
    echo "1. Test the website thoroughly\n";
    echo "2. If issues occur, run the rollback script\n";
    echo "3. Monitor error logs after deployment\n";
    echo "4. Delete this deployment script after successful deployment\n";
    
} catch (Exception $e) {
    echo "❌ Deployment preparation failed: " . $e->getMessage() . "\n";
    echo "No changes have been made to your system.\n";
}

echo "\n📞 Support: +260 972 545 658 / +260 770 809 062 / +260 771 470 868\n";
?>
