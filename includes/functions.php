<?php
if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access denied');
}

// Load configuration if not already loaded
if (!defined('BUFFALO_CONFIG_LOADED')) {
    $config_path = __DIR__ . '/../config/config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    } else {
        // Fallback constants for when config is missing
        if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
        if (!defined('DB_NAME')) define('DB_NAME', 'buffalo_marathon');
        if (!defined('DB_USER')) define('DB_USER', 'root');
        if (!defined('DB_PASS')) define('DB_PASS', '');
        if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
        if (!defined('MARATHON_DATE')) define('MARATHON_DATE', '2025-10-11');
        if (!defined('REGISTRATION_DEADLINE')) define('REGISTRATION_DEADLINE', '2025-09-30 23:59:59');
        if (!defined('EARLY_BIRD_DEADLINE')) define('EARLY_BIRD_DEADLINE', '2025-08-31 23:59:59');
        if (!defined('SITE_EMAIL')) define('SITE_EMAIL', 'info@buffalo-marathon.com');
        if (!defined('SITE_URL')) define('SITE_URL', 'https://buffalo-marathon.com');
        if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'admin@buffalo-marathon.com');
        if (!defined('NOREPLY_EMAIL')) define('NOREPLY_EMAIL', 'noreply@buffalo-marathon.com');
    }
}

// Database Connection Function
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // Check if constants are defined
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
                error_log("Database constants not defined");
                return null;
            }
            
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Add charset option if supported
            if (defined('DB_CHARSET')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . DB_CHARSET;
            }
            
            $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

// Alias function for compatibility
function getDatabase() {
    return getDatabaseConnection();
}

// Alias function for compatibility  
function getDB() {
    return getDatabaseConnection();
}

// Session Management Functions
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    startSecureSession();
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDatabaseConnection();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

function getCurrentUserEmail() {
    if (!isLoggedIn()) {
        return '';
    }
    
    // Check if email is already in session
    if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }
    
    // Get user data from database
    $user = getCurrentUser();
    if ($user && isset($user['email'])) {
        // Store in session for future use
        $_SESSION['user_email'] = $user['email'];
        return $user['email'];
    }
    
    return '';
}

function loginUser($user_id, $email, $role = 'user', $first_name = '') {
    startSecureSession();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_first_name'] = $first_name;
    $_SESSION['login_time'] = time();
    
    // Log the successful login (fallback if logActivity doesn't exist)
    if (function_exists('logActivity')) {
        logActivity('user_login', "User logged in: {$email}", $user_id);
    } else {
        error_log("User login: {$email} (ID: {$user_id})");
    }
    
    return true;
}

function logActivity($action, $details = '', $user_id = null) {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            error_log("Activity Log: {$action} - {$details}");
            return false;
        }
        
        $user_id = $user_id ?: ($_SESSION['user_id'] ?? null);
        $ip_address = getRealIpAddress();
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        return $stmt->execute([$user_id, $action, $details, $ip_address]);
        
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        error_log("Activity Log: {$action} - {$details}");
        return false;
    }
}

function getRealIpAddress() {
    // Check for various headers that might contain the real IP
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            
            // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR (even if it's private/reserved)
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function logout() {
    startSecureSession();
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// CSRF Protection Functions
function generateCSRFToken() {
    startSecureSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash Message Functions
function setFlash($type, $message) {
    startSecureSession();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function setFlashMessage($type, $message) {
    // Alias for setFlash for consistency with existing code
    setFlash($type, $message);
}

function getFlash() {
    startSecureSession();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function getAllFlashMessages() {
    startSecureSession();
    $messages = $_SESSION['flash'] ?? [];
    
    // Format messages for compatibility with existing template code
    $formatted = [];
    foreach ($messages as $flash) {
        $type = $flash['type'] ?? 'info';
        $message = $flash['message'] ?? '';
        
        // Return each message as a separate entry (not grouped by type)
        $formatted[] = ['type' => $type, 'message' => $message];
    }
    
    // Clear messages after retrieving them
    unset($_SESSION['flash']);
    
    return $formatted;
}

function hasFlash() {
    startSecureSession();
    return isset($_SESSION['flash']) && !empty($_SESSION['flash']);
}

// Registration and Statistics Functions
function getRegistrationStats() {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return [
            'total_registrations' => 0,
            'confirmed_payments' => 0,
            'pending_payments' => 0,
            'total_users' => 0,
            'active_categories' => 4,
            'total_revenue' => 0
        ];
    }
    
    try {
        // Get registration counts
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations");
        $total_registrations = $stmt->fetch()['total'];
        
        // Get payment statistics
        $stmt = $pdo->query("SELECT 
            COUNT(CASE WHEN payment_status = 'confirmed' THEN 1 END) as confirmed,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending,
            SUM(CASE WHEN payment_status = 'confirmed' THEN amount ELSE 0 END) as revenue
            FROM payments");
        $payments = $stmt->fetch();
        
        // Get user count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total_users = $stmt->fetch()['total'];
        
        // Get active categories
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
        $active_categories = $stmt->fetch()['total'];
        
        return [
            'total_registrations' => $total_registrations,
            'confirmed_payments' => $payments['confirmed'],
            'pending_payments' => $payments['pending'],
            'total_users' => $total_users,
            'active_categories' => $active_categories,
            'total_revenue' => $payments['revenue'] ?? 0
        ];
    } catch (PDOException $e) {
        error_log("Error getting registration stats: " . $e->getMessage());
        return [
            'total_registrations' => 0,
            'confirmed_payments' => 0,
            'pending_payments' => 0,
            'total_users' => 0,
            'active_categories' => 4,
            'total_revenue' => 0
        ];
    }
}

function getRaceCategories() {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY distance_km DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting race categories: " . $e->getMessage());
        return [];
    }
}

function getCategoryById($id) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting category: " . $e->getMessage());
        return null;
    }
}

function getRegistrationsByCategory() {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT c.name, c.max_participants, COUNT(r.id) as current_registrations,
                   ROUND((COUNT(r.id) / c.max_participants) * 100, 1) as percentage_full
            FROM categories c
            LEFT JOIN registrations r ON c.id = r.category_id
            WHERE c.is_active = 1
            GROUP BY c.id, c.name, c.max_participants
            ORDER BY percentage_full DESC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting registrations by category: " . $e->getMessage());
        return [];
    }
}

// Payment Functions
function createPayment($registration_id, $amount, $method, $reference = null) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payments (registration_id, amount, payment_method, payment_reference, payment_status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([$registration_id, $amount, $method, $reference]);
    } catch (PDOException $e) {
        error_log("Error creating payment: " . $e->getMessage());
        return false;
    }
}

function updatePaymentStatus($payment_id, $status, $transaction_id = null) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET payment_status = ?, transaction_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $transaction_id, $payment_id]);
    } catch (PDOException $e) {
        error_log("Error updating payment status: " . $e->getMessage());
        return false;
    }
}

// Email Functions
function sendEmail($to, $subject, $body, $from = null) {
    $from = $from ?? SITE_EMAIL;
    
    // Basic email headers
    $headers = [
        'From: ' . SITE_NAME . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    try {
        return mail($to, $subject, $body, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
        return false;
    }
}

function sendRegistrationConfirmation($user_email, $registration_data) {
    $subject = "Registration Confirmation - " . SITE_NAME;
    $body = "
    <h2>Registration Confirmation</h2>
    <p>Dear " . htmlspecialchars($registration_data['first_name']) . ",</p>
    <p>Thank you for registering for the Buffalo Marathon 2025!</p>
    
    <h3>Registration Details:</h3>
    <ul>
        <li><strong>Category:</strong> " . htmlspecialchars($registration_data['category_name']) . "</li>
        <li><strong>Registration Date:</strong> " . date('F j, Y') . "</li>
        <li><strong>Event Date:</strong> " . date('F j, Y', strtotime(MARATHON_DATE)) . "</li>
    </ul>
    
    <h3>Payment Information:</h3>
    <p>Please complete your payment to confirm your registration.</p>
    
    <p>For any questions, please contact us at " . CONTACT_PHONE_PRIMARY . "</p>
    
    <p>Best regards,<br>Buffalo Marathon Team</p>
    ";
    
    return sendEmail($user_email, $subject, $body);
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Zambian phone number validation
    $pattern = '/^(\+260|0)([0-9]{9})$/';
    return preg_match($pattern, $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

// Utility Functions
function formatCurrency($amount, $currency = 'ZMW') {
    return $currency . ' ' . number_format($amount, 2);
}

function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

function getTimeUntilEvent() {
    $now = new DateTime();
    $marathon_date = new DateTime(MARATHON_DATE);
    $diff = $now->diff($marathon_date);
    
    if ($marathon_date < $now) {
        return 'Event has passed';
    }
    
    return $diff->days . ' days, ' . $diff->h . ' hours';
}

function isRegistrationOpen() {
    $now = new DateTime();
    $deadline = new DateTime(REGISTRATION_DEADLINE);
    return $now < $deadline;
}

function isEarlyBirdActive() {
    $now = new DateTime();
    $early_bird_deadline = new DateTime(EARLY_BIRD_DEADLINE);
    return $now < $early_bird_deadline;
}

// Security Functions
function checkRateLimit($action, $identifier, $limit, $timeframe = 3600) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return true; // Allow if database unavailable
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM rate_limits 
            WHERE action = ? AND identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$action, $identifier, $timeframe]);
        $result = $stmt->fetch();
        
        return ($result['attempts'] ?? 0) < $limit;
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true; // Allow if check fails
    }
}

function recordRateLimit($action, $identifier) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO rate_limits (action, identifier, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$action, $identifier]);
    } catch (PDOException $e) {
        error_log("Rate limit recording failed: " . $e->getMessage());
    }
}

function logSecurityEvent($event_type, $description, $ip_address = null) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return;
    
    $ip_address = $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_events (event_type, description, ip_address, user_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt->execute([$event_type, $description, $ip_address, $user_id]);
    } catch (PDOException $e) {
        error_log("Security event logging failed: " . $e->getMessage());
    }
}

// Cache Functions
function getCachedData($key) {
    if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) {
        return null;
    }
    
    $cache_file = __DIR__ . '/../cache/' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return null;
    }
    
    $cache_data = file_get_contents($cache_file);
    $cache_array = unserialize($cache_data);
    
    if ($cache_array['expires'] < time()) {
        unlink($cache_file);
        return null;
    }
    
    return $cache_array['data'];
}

function setCachedData($key, $data, $lifetime = null) {
    if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) {
        return false;
    }
    
    $lifetime = $lifetime ?? CACHE_LIFETIME;
    $cache_dir = __DIR__ . '/../cache';
    
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . '/' . md5($key) . '.cache';
    $cache_array = [
        'data' => $data,
        'expires' => time() + $lifetime
    ];
    
    return file_put_contents($cache_file, serialize($cache_array)) !== false;
}

function clearCache($pattern = '*') {
    $cache_dir = __DIR__ . '/../cache';
    
    if (!is_dir($cache_dir)) {
        return true;
    }
    
    $files = glob($cache_dir . '/' . $pattern . '.cache');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    return true;
}

// System Health Functions
function getSystemHealth() {
    $health = [
        'database' => 'error',
        'files' => 'error',
        'cache' => 'error',
        'overall' => 'error'
    ];
    
    // Check database
    $pdo = getDatabaseConnection();
    $health['database'] = $pdo ? 'ok' : 'error';
    
    // Check essential files
    $essential_files = [
        'includes/header.php',
        'includes/footer.php',
        'admin/dashboard.php'
    ];
    
    $files_ok = true;
    foreach ($essential_files as $file) {
        if (!file_exists(__DIR__ . '/../' . $file)) {
            $files_ok = false;
            break;
        }
    }
    $health['files'] = $files_ok ? 'ok' : 'error';
    
    // Check cache directory
    $cache_dir = __DIR__ . '/../cache';
    $health['cache'] = (is_dir($cache_dir) && is_writable($cache_dir)) ? 'ok' : 'warning';
    
    // Overall health
    $health['overall'] = ($health['database'] === 'ok' && $health['files'] === 'ok') ? 'ok' : 'error';
    
    return $health;
}

// Contact Information Functions
function getContactPhone($type = 'primary') {
    switch ($type) {
        case 'primary':
            return CONTACT_PHONE_PRIMARY;
        case 'secondary':
            return CONTACT_PHONE_SECONDARY;
        case 'tertiary':
            return CONTACT_PHONE_TERTIARY;
        case 'all':
            return CONTACT_PHONES_ALL;
        default:
            return CONTACT_PHONE_PRIMARY;
    }
}

function getContactEmail($type = 'info') {
    switch ($type) {
        case 'info':
            return SITE_EMAIL;
        case 'admin':
            return ADMIN_EMAIL;
        case 'noreply':
            return NOREPLY_EMAIL;
        default:
            return SITE_EMAIL;
    }
}

function formatPhoneForDisplay($phone) {
    return preg_replace('/(\+260)(\d{3})(\d{3})(\d{3})/', '$1 $2 $3 $4', $phone);
}

function getSiteUrl() {
    return SITE_URL;
}

// Additional functions needed by index.php
function getDBStats() {
    return getRegistrationStats();
}

function getDaysUntilMarathon() {
    try {
        $now = new DateTime();
        $marathon_date = new DateTime(defined('MARATHON_DATE') ? MARATHON_DATE : '2025-10-11');
        $diff = $now->diff($marathon_date);
        
        if ($marathon_date < $now) {
            return 0;
        }
        
        return $diff->days;
    } catch (Exception $e) {
        error_log("Error calculating days until marathon: " . $e->getMessage());
        return 60; // Fallback value
    }
}

function getDaysUntilDeadline() {
    try {
        $now = new DateTime();
        $deadline = new DateTime(defined('REGISTRATION_DEADLINE') ? REGISTRATION_DEADLINE : '2025-09-30 23:59:59');
        $diff = $now->diff($deadline);
        
        if ($deadline < $now) {
            return 0;
        }
        
        return $diff->days;
    } catch (Exception $e) {
        error_log("Error calculating days until deadline: " . $e->getMessage());
        return 49; // Fallback value
    }
}

function getDaysUntilEarlyBird() {
    try {
        $now = new DateTime();
        $early_bird = new DateTime(defined('EARLY_BIRD_DEADLINE') ? EARLY_BIRD_DEADLINE : '2025-08-31 23:59:59');
        $diff = $now->diff($early_bird);
        
        if ($early_bird < $now) {
            return 0;
        }
        
        return $diff->days;
    } catch (Exception $e) {
        error_log("Error calculating days until early bird: " . $e->getMessage());
        return 19; // Fallback value
    }
}

function getMarathonStatus() {
    $now = new DateTime();
    $marathon_date = new DateTime(MARATHON_DATE);
    $registration_deadline = new DateTime(REGISTRATION_DEADLINE);
    
    if ($now > $marathon_date) {
        return 'completed';
    } elseif ($now > $registration_deadline) {
        return 'registration_closed';
    } else {
        return 'open';
    }
}

// Required login/admin functions
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

// Date/Time formatting functions
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    if (empty($datetime)) {
        return '';
    }
    
    try {
        if (is_string($datetime)) {
            $dt = new DateTime($datetime);
        } elseif ($datetime instanceof DateTime) {
            $dt = $datetime;
        } else {
            return '';
        }
        
        return $dt->format($format);
    } catch (Exception $e) {
        error_log("Error formatting datetime: " . $e->getMessage());
        return $datetime; // Return original value as fallback
    }
}

function formatTime($time, $format = 'g:i A') {
    return formatDateTime($time, $format);
}

// Array helper functions
function getArrayValue($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

function safeArrayGet($array, $key, $default = '') {
    return $array[$key] ?? $default;
}

// Settings functions
function getSetting($key, $default = null) {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return $default;
        }
        
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        error_log("Error getting setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

function setSetting($key, $value) {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        error_log("Error setting '{$key}': " . $e->getMessage());
        return false;
    }
}

function getMultipleSettings($keys) {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo || empty($keys)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $stmt = $pdo->prepare("SELECT setting_key, value FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['value'];
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log("Error getting multiple settings: " . $e->getMessage());
        return [];
    }
}

// Navigation and RBAC helper functions
function getNavigationItems($userRole = 'guest') {
    $baseItems = [
        'home' => ['url' => 'index.php', 'title' => 'Home', 'icon' => 'fas fa-home'],
        'categories' => ['url' => 'categories.php', 'title' => 'Categories', 'icon' => 'fas fa-list'],
        'schedule' => ['url' => 'schedule.php', 'title' => 'Schedule', 'icon' => 'fas fa-calendar-alt'],
        'info' => ['url' => 'info.php', 'title' => 'Info', 'icon' => 'fas fa-info-circle'],
        'contact' => ['url' => 'contact.php', 'title' => 'Contact', 'icon' => 'fas fa-envelope'],
        'faq' => ['url' => 'faq.php', 'title' => 'FAQ', 'icon' => 'fas fa-question-circle']
    ];
    
    $userItems = [
        'dashboard' => ['url' => 'dashboard.php', 'title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
        'profile' => ['url' => 'profile.php', 'title' => 'My Profile', 'icon' => 'fas fa-user'],
        'my-registration' => ['url' => 'my-registration.php', 'title' => 'My Registration', 'icon' => 'fas fa-running']
    ];
    
    $adminItems = [
        'admin-dashboard' => ['url' => 'admin/dashboard.php', 'title' => 'Admin Dashboard', 'icon' => 'fas fa-tachometer-alt'],
        'participants' => ['url' => 'admin/participants.php', 'title' => 'Participants', 'icon' => 'fas fa-users'],
        'payments' => ['url' => 'admin/payments.php', 'title' => 'Payments', 'icon' => 'fas fa-credit-card'],
        'announcements' => ['url' => 'admin/announcements.php', 'title' => 'Announcements', 'icon' => 'fas fa-bullhorn'],
        'reports' => ['url' => 'admin/reports.php', 'title' => 'Reports', 'icon' => 'fas fa-chart-bar'],
        'settings' => ['url' => 'admin/settings.php', 'title' => 'Settings', 'icon' => 'fas fa-cog']
    ];
    
    switch ($userRole) {
        case 'admin':
            return array_merge($baseItems, $userItems, $adminItems);
        case 'user':
            return array_merge($baseItems, $userItems);
        default:
            return $baseItems;
    }
}

function getUserRole() {
    if (!isLoggedIn()) {
        return 'guest';
    }
    
    return $_SESSION['user_role'] ?? 'user';
}

function canAccessPage($page, $userRole = null) {
    if ($userRole === null) {
        $userRole = getUserRole();
    }
    
    $adminPages = ['admin/', 'participants.php', 'payments.php', 'announcements.php', 'reports.php', 'settings.php'];
    $userPages = ['dashboard.php', 'profile.php', 'my-registration.php', 'register-marathon.php'];
    
    // Admin can access everything
    if ($userRole === 'admin') {
        return true;
    }
    
    // Check if trying to access admin page
    foreach ($adminPages as $adminPage) {
        if (strpos($page, $adminPage) !== false) {
            return false;
        }
    }
    
    // Check if logged in user trying to access user page
    if (in_array(basename($page), $userPages)) {
        return $userRole !== 'guest';
    }
    
    // Public pages accessible to all
    return true;
}

function isCurrentPage($url) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $targetPage = basename($url);
    
    return $currentPage === $targetPage;
}

function getActiveClass($url) {
    return isCurrentPage($url) ? 'active' : '';
}

function renderNavigationItem($item, $isInAdmin = false) {
    $url = $item['url'];
    $title = $item['title'];
    $icon = $item['icon'];
    $activeClass = getActiveClass($url);
    
    // Adjust URL for admin context
    if ($isInAdmin && !str_contains($url, 'admin/')) {
        $url = '../' . $url;
    } elseif (!$isInAdmin && str_contains($url, 'admin/')) {
        // Don't show admin URLs in regular navigation
        return '';
    }
    
    return sprintf(
        '<li class="nav-item"><a class="nav-link %s" href="%s"><i class="%s me-1"></i>%s</a></li>',
        $activeClass,
        htmlspecialchars($url),
        htmlspecialchars($icon),
        htmlspecialchars($title)
    );
}

// Navigation security middleware
function checkPageAccess($requiredRole = 'user') {
    $currentRole = getUserRole();
    
    // Define role hierarchy
    $roleHierarchy = [
        'guest' => 0,
        'user' => 1,
        'admin' => 2
    ];
    
    $currentLevel = $roleHierarchy[$currentRole] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 1;
    
    if ($currentLevel < $requiredLevel) {
        // Redirect based on requirement
        if ($requiredRole === 'admin') {
            setFlashMessage('error', 'Admin access required.');
            header('Location: ../login.php');
        } else {
            setFlashMessage('error', 'Please login to access this page.');
            header('Location: login.php');
        }
        exit;
    }
    
    return true;
}

function protectAdminPage() {
    return checkPageAccess('admin');
}

function protectUserPage() {
    return checkPageAccess('user');
}

function addNavigationSecurity($pageType = 'public') {
    // Add security headers
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    
    // Check access based on page type
    switch ($pageType) {
        case 'admin':
            protectAdminPage();
            break;
        case 'user':
            protectUserPage();
            break;
        case 'public':
        default:
            // Public page, no additional restrictions
            break;
    }
}
?>