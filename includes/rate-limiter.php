<?php
/**
 * Buffalo Marathon 2025 - Rate Limiting System
 * Prevents abuse and ensures fair usage
 * Created: 2025-01-09
 */

// Security check
if (!defined('BUFFALO_CONFIG_LOADED')) {
    define('BUFFALO_SECURE_ACCESS', true);
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
}

class RateLimiter {
    private static ?RateLimiter $instance = null;
    private PDO $db;
    
    // Rate limit configurations
    private array $limits = [
        'registration' => ['limit' => 3, 'window' => 3600],    // 3 attempts per hour
        'login' => ['limit' => 5, 'window' => 900],            // 5 attempts per 15 minutes
        'email' => ['limit' => 10, 'window' => 3600],          // 10 emails per hour
        'password_reset' => ['limit' => 3, 'window' => 1800],  // 3 attempts per 30 minutes
        'contact_form' => ['limit' => 5, 'window' => 1800],    // 5 submissions per 30 minutes
        'api_call' => ['limit' => 60, 'window' => 60],         // 60 calls per minute
    ];
    
    private function __construct() {
        $this->db = getDB();
    }
    
    public static function getInstance(): RateLimiter {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if action is allowed for identifier
     */
    public function isAllowed(string $action, string $identifier = null): bool {
        if (!isset($this->limits[$action])) {
            return true; // Unknown action, allow by default
        }
        
        $identifier = $identifier ?? $this->getDefaultIdentifier();
        $config = $this->limits[$action];
        
        try {
            // Clean expired entries first
            $this->cleanExpiredEntries($action, $config['window']);
            
            // Get current attempts
            $attempts = $this->getCurrentAttempts($action, $identifier, $config['window']);
            
            return $attempts < $config['limit'];
            
        } catch (Exception $e) {
            error_log("Rate limiting error: " . $e->getMessage());
            return true; // Allow on error to avoid blocking legitimate users
        }
    }
    
    /**
     * Record an attempt
     */
    public function recordAttempt(string $action, string $identifier = null): bool {
        if (!isset($this->limits[$action])) {
            return true;
        }
        
        $identifier = $identifier ?? $this->getDefaultIdentifier();
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (identifier, action_type, attempts, window_start, last_attempt) 
                VALUES (?, ?, 1, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1, 
                last_attempt = NOW()
            ");
            
            return $stmt->execute([$identifier, $action]);
            
        } catch (Exception $e) {
            error_log("Rate limiting record error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(string $action, string $identifier = null): int {
        if (!isset($this->limits[$action])) {
            return PHP_INT_MAX;
        }
        
        $identifier = $identifier ?? $this->getDefaultIdentifier();
        $config = $this->limits[$action];
        
        try {
            $attempts = $this->getCurrentAttempts($action, $identifier, $config['window']);
            return max(0, $config['limit'] - $attempts);
            
        } catch (Exception $e) {
            error_log("Rate limiting remaining error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get time until reset
     */
    public function getResetTime(string $action, string $identifier = null): int {
        if (!isset($this->limits[$action])) {
            return 0;
        }
        
        $identifier = $identifier ?? $this->getDefaultIdentifier();
        $config = $this->limits[$action];
        
        try {
            $stmt = $this->db->prepare("
                SELECT window_start 
                FROM rate_limits 
                WHERE identifier = ? AND action_type = ? 
                AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$identifier, $action, $config['window']]);
            
            $windowStart = $stmt->fetchColumn();
            if (!$windowStart) {
                return 0;
            }
            
            $resetTime = strtotime($windowStart) + $config['window'];
            return max(0, $resetTime - time());
            
        } catch (Exception $e) {
            error_log("Rate limiting reset time error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear rate limits for identifier
     */
    public function clearLimits(string $identifier, string $action = null): bool {
        try {
            if ($action) {
                $stmt = $this->db->prepare("
                    DELETE FROM rate_limits 
                    WHERE identifier = ? AND action_type = ?
                ");
                return $stmt->execute([$identifier, $action]);
            } else {
                $stmt = $this->db->prepare("
                    DELETE FROM rate_limits WHERE identifier = ?
                ");
                return $stmt->execute([$identifier]);
            }
        } catch (Exception $e) {
            error_log("Rate limiting clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get rate limiting status for user
     */
    public function getStatus(string $identifier = null): array {
        $identifier = $identifier ?? $this->getDefaultIdentifier();
        $status = [];
        
        foreach ($this->limits as $action => $config) {
            $attempts = $this->getCurrentAttempts($action, $identifier, $config['window']);
            $status[$action] = [
                'attempts' => $attempts,
                'limit' => $config['limit'],
                'remaining' => max(0, $config['limit'] - $attempts),
                'reset_time' => $this->getResetTime($action, $identifier),
                'is_limited' => $attempts >= $config['limit']
            ];
        }
        
        return $status;
    }
    
    /**
     * Get current attempts within window
     */
    private function getCurrentAttempts(string $action, string $identifier, int $window): int {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(attempts), 0) 
            FROM rate_limits 
            WHERE identifier = ? AND action_type = ? 
            AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $action, $window]);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Clean expired entries
     */
    private function cleanExpiredEntries(string $action, int $window): void {
        $stmt = $this->db->prepare("
            DELETE FROM rate_limits 
            WHERE action_type = ? 
            AND window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$action, $window]);
    }
    
    /**
     * Get default identifier (IP + User Agent hash)
     */
    private function getDefaultIdentifier(): string {
        $ip = $this->getRealIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return md5($ip . '|' . $userAgent);
    }
    
    /**
     * Get real IP address
     */
    private function getRealIpAddress(): string {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
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
     * Add custom rate limit configuration
     */
    public function addLimit(string $action, int $limit, int $window): void {
        $this->limits[$action] = ['limit' => $limit, 'window' => $window];
    }
    
    /**
     * Middleware function to check rate limits
     */
    public function middleware(string $action, ?string $identifier = null): void {
        if (!$this->isAllowed($action, $identifier)) {
            $remaining = $this->getRemainingAttempts($action, $identifier);
            $resetTime = $this->getResetTime($action, $identifier);
            
            // Log the rate limit violation
            logActivity('rate_limit_exceeded', "Rate limit exceeded for action: {$action}");
            
            // Return rate limit error
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many attempts. Please try again later.',
                'remaining' => $remaining,
                'reset_in' => $resetTime,
                'retry_after' => $resetTime
            ]);
            exit;
        }
        
        // Record this attempt
        $this->recordAttempt($action, $identifier);
    }
}

/**
 * Helper functions for easy rate limiting
 */

function rate_limit_check(string $action, string $identifier = null): bool {
    return RateLimiter::getInstance()->isAllowed($action, $identifier);
}

function rate_limit_record(string $action, string $identifier = null): bool {
    return RateLimiter::getInstance()->recordAttempt($action, $identifier);
}

function rate_limit_remaining(string $action, string $identifier = null): int {
    return RateLimiter::getInstance()->getRemainingAttempts($action, $identifier);
}

function rate_limit_reset_time(string $action, string $identifier = null): int {
    return RateLimiter::getInstance()->getResetTime($action, $identifier);
}

function rate_limit_middleware(string $action, ?string $identifier = null): void {
    RateLimiter::getInstance()->middleware($action, $identifier);
}

function rate_limit_clear(string $identifier, string $action = null): bool {
    return RateLimiter::getInstance()->clearLimits($identifier, $action);
}

function rate_limit_status(string $identifier = null): array {
    return RateLimiter::getInstance()->getStatus($identifier);
}

/**
 * Rate limiting for specific actions
 */

function check_registration_rate_limit(): bool {
    $userId = $_SESSION['user_id'] ?? null;
    $identifier = $userId ? "user_{$userId}" : null;
    
    if (!rate_limit_check('registration', $identifier)) {
        $resetTime = rate_limit_reset_time('registration', $identifier);
        setFlashMessage('error', "Too many registration attempts. Please try again in " . gmdate('i:s', $resetTime) . " minutes.");
        return false;
    }
    
    rate_limit_record('registration', $identifier);
    return true;
}

function check_login_rate_limit(string $email = null): bool {
    $identifier = $email ? md5($email) : null;
    
    if (!rate_limit_check('login', $identifier)) {
        $resetTime = rate_limit_reset_time('login', $identifier);
        setFlashMessage('error', "Too many login attempts. Please try again in " . gmdate('i:s', $resetTime) . " minutes.");
        return false;
    }
    
    rate_limit_record('login', $identifier);
    return true;
}

function check_email_rate_limit(string $email = null): bool {
    $identifier = $email ? md5($email) : null;
    
    if (!rate_limit_check('email', $identifier)) {
        return false;
    }
    
    rate_limit_record('email', $identifier);
    return true;
}

// Clean expired rate limits occasionally
if (mt_rand(1, 100) === 1) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM rate_limits 
            WHERE window_start < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Rate limit cleanup error: " . $e->getMessage());
    }
}
?>