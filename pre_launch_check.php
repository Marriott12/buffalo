<?php
/**
 * Buffalo Marathon 2025 - Pre-Launch System Check
 * Run this before going live to verify everything is working
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🚀 Buffalo Marathon 2025 - Pre-Launch System Check</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #17a2b8; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

$checks_passed = 0;
$total_checks = 0;

function runCheck($test_name, $condition, $success_msg, $error_msg) {
    global $checks_passed, $total_checks;
    $total_checks++;
    
    echo "<div class='section'>";
    echo "<strong>$test_name:</strong> ";
    
    if ($condition) {
        echo "<span class='success'>✅ $success_msg</span>";
        $checks_passed++;
    } else {
        echo "<span class='error'>❌ $error_msg</span>";
    }
    echo "</div>";
}

// 1. Database Connection Test
echo "<h2>📊 Database Tests</h2>";
try {
    $db = Database::getInstance()->getConnection();
    runCheck(
        "Database Connection",
        true,
        "Connected successfully to " . DB_NAME,
        "Failed to connect to database"
    );
    
    // Test users table
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    runCheck(
        "Users Table",
        $user_count >= 1,
        "Found $user_count users in database",
        "No users found - database may not be properly set up"
    );
    
    // Test admin user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetch()['count'];
    runCheck(
        "Admin User",
        $admin_count >= 1,
        "Found $admin_count admin user(s)",
        "No admin users found - cannot access admin panel"
    );
    
    // Test categories table
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1");
    $category_count = $stmt->fetch()['count'];
    runCheck(
        "Marathon Categories",
        $category_count >= 1,
        "Found $category_count active race categories",
        "No active race categories found"
    );
    
} catch (Exception $e) {
    runCheck(
        "Database Connection",
        false,
        "",
        "Database connection failed: " . $e->getMessage()
    );
}

// 2. File System Tests
echo "<h2>📁 File System Tests</h2>";

$required_dirs = [
    'logs' => __DIR__ . '/logs',
    'cache' => __DIR__ . '/cache',
    'uploads' => __DIR__ . '/uploads',
    'backups' => __DIR__ . '/backups'
];

foreach ($required_dirs as $name => $path) {
    runCheck(
        "Directory: $name",
        is_dir($path) && is_writable($path),
        "Directory exists and is writable: $path",
        "Directory missing or not writable: $path"
    );
}

// 3. Configuration Tests
echo "<h2>⚙️ Configuration Tests</h2>";

runCheck(
    "Site URL Configuration",
    defined('SITE_URL') && SITE_URL !== 'https://buffalo-marathon.com',
    "Site URL configured: " . SITE_URL,
    "Site URL needs to be updated from default"
);

runCheck(
    "Email Configuration",
    defined('SMTP_USERNAME') && SMTP_USERNAME !== 'your-email@gmail.com',
    "SMTP configured with: " . SMTP_USERNAME,
    "SMTP email not configured"
);

runCheck(
    "Contact Phone Configuration",
    defined('CONTACT_PHONE_PRIMARY') && CONTACT_PHONE_PRIMARY !== '+260 XXX XXXXXX',
    "Contact phones configured: " . CONTACT_PHONE_PRIMARY,
    "Contact phone numbers not configured"
);

runCheck(
    "Production Environment",
    ENVIRONMENT === 'production' && !DEBUG_MODE,
    "Environment set to production with debug disabled",
    "Environment not set to production or debug still enabled"
);

runCheck(
    "Timezone Configuration",
    date_default_timezone_get() === 'Africa/Lusaka',
    "Timezone set to: " . date_default_timezone_get(),
    "Timezone not set to Africa/Lusaka"
);

// 4. Security Tests
echo "<h2>🔒 Security Tests</h2>";

runCheck(
    "Error Reporting",
    !ini_get('display_errors'),
    "Error display disabled for production",
    "Error display still enabled - security risk"
);

runCheck(
    "Session Security",
    ini_get('session.cookie_httponly') && ini_get('session.use_strict_mode'),
    "Session security properly configured",
    "Session security settings need attention"
);

runCheck(
    "File Permissions",
    !is_readable(__DIR__ . '/config/config.php') || !is_executable(__DIR__ . '/config/config.php'),
    "Config file permissions secure",
    "Config file may be too permissive"
);

// 5. Email Test
echo "<h2>📧 Email System Test</h2>";

if (defined('SMTP_USERNAME') && SMTP_USERNAME !== 'your-email@gmail.com') {
    try {
        // Simulate email queue test
        $test_email = queueEmail(
            'test@example.com', 
            'Buffalo Marathon Test Email', 
            'This is a test email from Buffalo Marathon system.'
        );
        
        runCheck(
            "Email Queue System",
            $test_email,
            "Email queue system working",
            "Email queue system failed"
        );
    } catch (Exception $e) {
        runCheck(
            "Email Queue System",
            false,
            "",
            "Email system error: " . $e->getMessage()
        );
    }
} else {
    runCheck(
        "Email Configuration",
        false,
        "",
        "Email configuration incomplete"
    );
}

// 6. Marathon-Specific Tests
echo "<h2>🏃‍♂️ Marathon Configuration Tests</h2>";

$marathon_date = new DateTime(MARATHON_DATE);
$now = new DateTime();
$days_until = $marathon_date->diff($now)->days;

runCheck(
    "Marathon Date",
    $marathon_date > $now,
    "Marathon date is " . $days_until . " days from now (" . MARATHON_DATE . ")",
    "Marathon date may be in the past or incorrectly configured"
);

runCheck(
    "Registration Status",
    isRegistrationOpen(),
    "Registration is currently open",
    "Registration is currently closed"
);

runCheck(
    "Early Bird Status",
    isEarlyBirdActive(),
    "Early bird pricing is active",
    "Early bird period has ended"
);

// 7. Performance Tests
echo "<h2>⚡ Performance Tests</h2>";

$start_time = microtime(true);
$db->query("SELECT 1");
$db_time = microtime(true) - $start_time;

runCheck(
    "Database Performance",
    $db_time < 0.1,
    "Database query time: " . round($db_time * 1000, 2) . "ms",
    "Database queries are slow (" . round($db_time * 1000, 2) . "ms)"
);

runCheck(
    "Cache System",
    CACHE_ENABLED,
    "Cache system is enabled",
    "Cache system is disabled - may impact performance"
);

// Final Summary
echo "<h2>📊 Summary</h2>";
$success_rate = round(($checks_passed / $total_checks) * 100, 1);

echo "<div class='section'>";
echo "<h3>Overall System Health: ";

if ($success_rate >= 90) {
    echo "<span class='success'>🎉 EXCELLENT ($success_rate%)</span>";
    echo "<p class='success'>Your system is ready for production launch!</p>";
} elseif ($success_rate >= 75) {
    echo "<span class='warning'>⚠️ GOOD ($success_rate%)</span>";
    echo "<p class='warning'>Address the issues above before launching.</p>";
} else {
    echo "<span class='error'>🚨 NEEDS ATTENTION ($success_rate%)</span>";
    echo "<p class='error'>Multiple issues found. Do not launch until resolved.</p>";
}

echo "<p><strong>Checks Passed:</strong> $checks_passed / $total_checks</p>";
echo "</div>";

// Recommendations
echo "<h2>💡 Pre-Launch Recommendations</h2>";
echo "<div class='section'>";
echo "<ul>";
echo "<li><strong>Backup:</strong> Create full database and file backup</li>";
echo "<li><strong>SSL:</strong> Ensure HTTPS certificate is installed and working</li>";
echo "<li><strong>Domain:</strong> Verify domain DNS is pointing to your server</li>";
echo "<li><strong>Monitoring:</strong> Set up error monitoring and logging</li>";
echo "<li><strong>Testing:</strong> Test all forms and registration process</li>";
echo "<li><strong>Contact:</strong> Verify all contact information is correct</li>";
echo "<li><strong>Payment:</strong> Test payment processing if applicable</li>";
echo "<li><strong>Mobile:</strong> Test on mobile devices</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>🗑️ Delete this file after running for security!</strong></p>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s T') . "</p>";
?>
