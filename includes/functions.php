<?php
/**
 * Buffalo Marathon 2025 - Core Functions
 * Production Ready with Security & Performance
 * Generated: 2025-08-08 10:33:55 UTC
 */

// Security check
if (!defined('BUFFALO_CONFIG_LOADED')) {
    define('BUFFALO_SECURE_ACCESS', true);
    require_once __DIR__ . '/../config/config.php';
    require_once 'database.php';
require_once 'security.php';
require_once 'cache.php';
require_once 'monitoring.php';
require_once 'security_enhanced.php';

// Error reporting based on environment
}

// Start session with security
function initializeSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('BUFFALO_SESSION');
        session_start();
        
        // Security: Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            destroySession();
        }
        $_SESSION['last_activity'] = time();
    }
}

// Initialize session
initializeSession();

/**
 * AUTHENTICATION FUNCTIONS
 */

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['user_email']) && 
           !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && 
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, email, first_name, last_name, phone, role, 
                   email_verified, created_at 
            FROM users 
            WHERE id = ? AND role = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_role']]);
        
        $user = $stmt->fetch();
        return $user ?: null;
        
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $redirect_url = $_SERVER['REQUEST_URI'] ?? '/';
        setFlashMessage('warning', 'Please log in to access this page.');
        header('Location: /login.php?redirect=' . urlencode($redirect_url));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Administrator privileges required.');
        header('Location: /dashboard.php');
        exit;
    }
}

function loginUser(int $userId, string $email, string $role): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Log login activity
    logActivity('user_login', "User logged in: {$email}", $userId);
}

function logoutUser(): void {
    if (isLoggedIn()) {
        logActivity('user_logout', "User logged out: " . $_SESSION['user_email'], $_SESSION['user_id']);
    }
    destroySession();
}

function destroySession(): void {
    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

/**
 * SECURITY FUNCTIONS
 */

function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > 3600) { // 1 hour expiry
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && 
           !empty($token) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data): mixed {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if (is_string($data)) {
        // Remove null bytes
        $data = str_replace(["\0", "\x00"], '', $data);
        // Trim whitespace
        $data = trim($data);
        // Convert special characters
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    return $data;
}

function validateEmail(string $email): bool {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false &&
           strlen($email) <= 255;
}

function validatePhone(string $phone): bool {
    $phone = preg_replace('/[^\d+\-\(\)\s]/', '', $phone);
    return preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone);
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function generateSecureToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * RATE LIMITING
 */

function checkRateLimit(string $action, int $limit = 5, int $window = 900): bool {
    $ip = getRealIpAddress();
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    $now = time();
    
    // Remove old attempts outside the window
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Check if limit exceeded
    if (count($_SESSION[$key]) >= $limit) {
        logActivity('rate_limit_exceeded', "Rate limit exceeded for action: {$action}", null);
        return false;
    }
    
    // Add current attempt
    $_SESSION[$key][] = $now;
    return true;
}

function getRealIpAddress(): string {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * MARATHON SPECIFIC FUNCTIONS
 */

function getDaysUntilMarathon(): int {
    try {
        $marathon_datetime = new DateTime(MARATHON_DATE . ' ' . MARATHON_TIME);
        $now = new DateTime();
        
        if ($now >= $marathon_datetime) {
            return 0;
        }
        
        $diff = $now->diff($marathon_datetime);
        return (int)$diff->days;
        
    } catch (Exception $e) {
        error_log("Error calculating days until marathon: " . $e->getMessage());
        return 0;
    }
}

function getDaysUntilDeadline(): int {
    try {
        $deadline = new DateTime(REGISTRATION_DEADLINE);
        $now = new DateTime();
        
        if ($now >= $deadline) {
            return 0;
        }
        
        $diff = $now->diff($deadline);
        return (int)$diff->days;
        
    } catch (Exception $e) {
        error_log("Error calculating days until deadline: " . $e->getMessage());
        return 0;
    }
}

function getDaysUntilEarlyBird(): int {
    try {
        $early_bird = new DateTime(EARLY_BIRD_DEADLINE);
        $now = new DateTime();
        
        if ($now >= $early_bird) {
            return 0;
        }
        
        $diff = $now->diff($early_bird);
        return (int)$diff->days;
        
    } catch (Exception $e) {
        error_log("Error calculating days until early bird: " . $e->getMessage());
        return 0;
    }
}

function isRegistrationOpen(): bool {
    try {
        $deadline = new DateTime(REGISTRATION_DEADLINE);
        $now = new DateTime();
        
        // Check database setting
        $db_setting = getSetting('registration_open', '1');
        
        return ($now <= $deadline) && ($db_setting === '1');
        
    } catch (Exception $e) {
        error_log("Error checking registration status: " . $e->getMessage());
        return false;
    }
}

function isEarlyBirdActive(): bool {
    try {
        $early_bird = new DateTime(EARLY_BIRD_DEADLINE);
        $now = new DateTime();
        return $now <= $early_bird;
    } catch (Exception $e) {
        return false;
    }
}

function getMarathonStatus(): string {
    try {
        $now = new DateTime();
        $deadline = new DateTime(REGISTRATION_DEADLINE);
        $marathon = new DateTime(MARATHON_DATE . ' ' . MARATHON_TIME);
        
        if ($now >= $marathon) {
            return 'completed';
        } elseif ($now >= $deadline) {
            return 'registration_closed';
        } else {
            return 'registration_open';
        }
    } catch (Exception $e) {
        error_log("Error getting marathon status: " . $e->getMessage());
        return 'registration_open';
    }
}

function generateRegistrationNumber(): string {
    $year = date('Y');
    $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return "BM{$year}{$random}";
}

function formatCurrency(float $amount): string {
    return 'K' . number_format($amount, 2);
}

/**
 * FLASH MESSAGES
 */

function setFlashMessage(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type] = $message;
}

function getFlashMessage(string $type): ?string {
    if (isset($_SESSION['flash_messages'][$type])) {
        $message = $_SESSION['flash_messages'][$type];
        unset($_SESSION['flash_messages'][$type]);
        return $message;
    }
    return null;
}

function hasFlashMessage(string $type): bool {
    return isset($_SESSION['flash_messages'][$type]);
}

function getAllFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'] = [];
    return $messages;
}

/**
 * DATABASE UTILITIES
 */

function getSetting(string $key, mixed $default = null): mixed {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
        
    } catch (Exception $e) {
        error_log("Error getting setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

function updateSetting(string $key, mixed $value, ?int $userId = null): bool {
    try {
        $db = getDB();
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            updated_by = VALUES(updated_by), 
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([$key, $value, $userId]);
        
    } catch (Exception $e) {
        error_log("Error updating setting '{$key}': " . $e->getMessage());
        return false;
    }
}

function logActivity(string $action, string $description = '', ?int $userId = null): void {
    try {
        $db = getDB();
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $ipAddress = getRealIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $action, $description, $ipAddress, $userAgent]);
        
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * EMAIL FUNCTIONS
 */

function queueEmail(string $to, string $subject, string $body): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO email_queue (to_email, subject, body) 
            VALUES (?, ?, ?)
        ");
        
        return $stmt->execute([$to, $subject, $body]);
        
    } catch (Exception $e) {
        error_log("Error queuing email: " . $e->getMessage());
        return false;
    }
}

function sendWelcomeEmail(string $email, string $firstName): bool {
    $subject = "Welcome to Buffalo Marathon 2025!";
    $body = getEmailTemplate('welcome', [
        'first_name' => $firstName,
        'marathon_date' => date('F j, Y', strtotime(MARATHON_DATE)),
        'registration_deadline' => date('F j, Y', strtotime(REGISTRATION_DEADLINE))
    ]);
    
    return queueEmail($email, $subject, $body);
}

function sendRegistrationConfirmation(string $email, string $firstName, array $registrationData): bool {
    $subject = "Registration Confirmed - Buffalo Marathon 2025";
    $body = getEmailTemplate('registration_confirmation', array_merge([
        'first_name' => $firstName,
        'marathon_date' => date('F j, Y', strtotime(MARATHON_DATE))
    ], $registrationData));
    
    return queueEmail($email, $subject, $body);
}

function getEmailTemplate(string $template, array $variables = []): string {
    $template_file = __DIR__ . "/../templates/email/{$template}.html";
    
    if (!file_exists($template_file)) {
        return "Email template not found: {$template}";
    }
    
    $content = file_get_contents($template_file);
    
    // Replace variables
    foreach ($variables as $key => $value) {
        $content = str_replace("{{$key}}", htmlspecialchars($value), $content);
    }
    
    return $content;
}

/**
 * UTILITY FUNCTIONS
 */

function formatDateTime(string $datetime, string $format = 'M j, Y g:i A'): string {
    try {
        return date($format, strtotime($datetime));
    } catch (Exception $e) {
        return $datetime;
    }
}

function formatDate(string $date, string $format = 'M j, Y'): string {
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

function truncateText(string $text, int $length = 100, string $suffix = '...'): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

function generateSlug(string $text): string {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function isValidDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * CACHE FUNCTIONS
 */

function getCacheKey(string $key): string {
    return 'buffalo_' . md5($key);
}

function getFromCache(string $key): mixed {
    if (!CACHE_ENABLED) {
        return null;
    }
    
    $cache_file = __DIR__ . '/../cache/' . getCacheKey($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return null;
    }
    
    $cache_data = unserialize(file_get_contents($cache_file));
    
    if ($cache_data['expires'] < time()) {
        unlink($cache_file);
        return null;
    }
    
    return $cache_data['data'];
}

function setCache(string $key, mixed $data, int $lifetime = null): bool {
    if (!CACHE_ENABLED) {
        return false;
    }
    
    $lifetime = $lifetime ?? CACHE_LIFETIME;
    $cache_file = __DIR__ . '/../cache/' . getCacheKey($key) . '.cache';
    
    $cache_data = [
        'data' => $data,
        'expires' => time() + $lifetime,
        'created' => time()
    ];
    
    return file_put_contents($cache_file, serialize($cache_data)) !== false;
}

function clearCache(string $key = null): bool {
    $cache_dir = __DIR__ . '/../cache/';
    
    if ($key !== null) {
        $cache_file = $cache_dir . getCacheKey($key) . '.cache';
        return file_exists($cache_file) ? unlink($cache_file) : true;
    }
    
    // Clear all cache files
    $files = glob($cache_dir . '*.cache');
    foreach ($files as $file) {
        unlink($file);
    }
    
    return true;
}

/**
 * CONTACT INFORMATION FUNCTIONS
 */

function getContactPhone(string $type = 'all'): string {
    return match($type) {
        'primary' => defined('CONTACT_PHONE_PRIMARY') ? CONTACT_PHONE_PRIMARY : '+260 972 545 658',
        'secondary' => defined('CONTACT_PHONE_SECONDARY') ? CONTACT_PHONE_SECONDARY : '+260 770 809 062',
        'tertiary' => defined('CONTACT_PHONE_TERTIARY') ? CONTACT_PHONE_TERTIARY : '+260 771 470 868',
        'all' => defined('CONTACT_PHONES_ALL') ? CONTACT_PHONES_ALL : '+260 972 545 658 / +260 770 809 062 / +260 771 470 868',
        default => getSetting("contact_phone_{$type}", '+260 972 545 658')
    };
}

function getContactEmail(string $type = 'general'): string {
    return match($type) {
        'admin' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@buffalo-marathon.com',
        'noreply' => defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@buffalo-marathon.com',
        'general' => defined('SITE_EMAIL') ? SITE_EMAIL : 'info@buffalo-marathon.com',
        default => getSetting("contact_email_{$type}", 'info@buffalo-marathon.com')
    };
}

function getSiteUrl(): string {
    return defined('SITE_URL') ? SITE_URL : 'https://buffalo-marathon.com';
}

function formatPhoneForDisplay(string $phone): string {
    // Clean the phone number
    $clean = preg_replace('/[^\d+]/', '', $phone);
    
    // Format Zambian numbers
    if (str_starts_with($clean, '+260')) {
        return '+260 ' . substr($clean, 4, 3) . ' ' . substr($clean, 7, 3) . ' ' . substr($clean, 10);
    }
    
    return $phone;
}

/**
 * ERROR HANDLING
 */

function handleError(string $message, string $type = 'error', bool $log = true): void {
    if ($log) {
        error_log("Buffalo Marathon Error [{$type}]: {$message}");
    }
    
    if (DEBUG_MODE) {
        echo "<div class='alert alert-danger'><strong>Error:</strong> {$message}</div>";
    }
}

function redirectTo(string $url, int $status_code = 302): void {
    if (!headers_sent()) {
        header("Location: {$url}", true, $status_code);
        exit;
    }
}

function jsonResponse(array $data, int $status_code = 200): void {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Initialize error handlers
if (ENVIRONMENT === 'production') {
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        error_log("PHP Error: {$message} in {$file} on line {$line}");
        return true;
    });
    
    set_exception_handler(function($exception) {
        error_log("Uncaught Exception: " . $exception->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
            include __DIR__ . '/../public/error-500.html';
        }
        exit;
    });
}

/**
 * EMAIL FUNCTIONS
 */

/**
 * Send email using PHPMailer with SMTP configuration
 */
function sendEmail($to, $subject, $body, $recipientName = '', $isHTML = true) {
    // Check if we're in a test environment
    if (defined('TESTING_MODE') && TESTING_MODE) {
        error_log("Test Mode: Email to $to - Subject: $subject");
        return true;
    }
    
    try {
        // Use simple mail() function as fallback if PHPMailer not available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $headers = [
                'From: ' . SITE_EMAIL,
                'Reply-To: ' . SITE_EMAIL,
                'X-Mailer: Buffalo Marathon System',
                'MIME-Version: 1.0'
            ];
            
            if ($isHTML) {
                $headers[] = 'Content-type: text/html; charset=UTF-8';
            } else {
                $headers[] = 'Content-type: text/plain; charset=UTF-8';
            }
            
            return mail($to, $subject, $body, implode("\r\n", $headers));
        }
        
        // Use PHPMailer if available (for production)
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SITE_EMAIL, SITE_NAME);
            $mail->addAddress($to, $recipientName);
            $mail->addReplyTo(SITE_EMAIL, SITE_NAME);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if ($isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            return $mail->send();
        } else {
            // Fallback to basic mail function
            $headers = [
                'From: ' . SITE_EMAIL,
                'Reply-To: ' . SITE_EMAIL,
                'X-Mailer: Buffalo Marathon System',
                'MIME-Version: 1.0'
            ];
            
            if ($isHTML) {
                $headers[] = 'Content-type: text/html; charset=UTF-8';
            } else {
                $headers[] = 'Content-type: text/plain; charset=UTF-8';
            }
            
            return mail($to, $subject, $body, implode("\r\n", $headers));
        }
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
?>
```