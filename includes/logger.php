<?php
/**
 * Buffalo Marathon 2025 - Advanced Logging System
 * Comprehensive logging with multiple levels and outputs
 * Created: 2025-01-09
 */

// Security check
if (!defined('BUFFALO_CONFIG_LOADED')) {
    define('BUFFALO_SECURE_ACCESS', true);
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
}

class BuffaloLogger {
    private static ?BuffaloLogger $instance = null;
    private PDO $db;
    private string $logDir;
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    // Log types
    const TYPE_SYSTEM = 'system';
    const TYPE_USER = 'user';
    const TYPE_SECURITY = 'security';
    const TYPE_PAYMENT = 'payment';
    const TYPE_EMAIL = 'email';
    const TYPE_API = 'api';
    const TYPE_DATABASE = 'database';
    const TYPE_PERFORMANCE = 'performance';
    
    private array $logLevels = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::WARNING => 2,
        self::ERROR => 3,
        self::CRITICAL => 4
    ];
    
    private int $minLogLevel;
    private bool $logToDatabase;
    private bool $logToFile;
    
    private function __construct() {
        $this->db = getDB();
        $this->logDir = __DIR__ . '/../logs/';
        $this->minLogLevel = ENVIRONMENT === 'production' ? 1 : 0; // INFO+ in production, DEBUG+ in dev
        $this->logToDatabase = true;
        $this->logToFile = true;
        
        // Ensure log directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance(): BuffaloLogger {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log a message
     */
    public function log(string $level, string $message, string $type = self::TYPE_SYSTEM, array $context = []): bool {
        if ($this->logLevels[$level] < $this->minLogLevel) {
            return true; // Skip logging below minimum level
        }
        
        $logEntry = $this->createLogEntry($level, $message, $type, $context);
        
        $success = true;
        
        // Log to database
        if ($this->logToDatabase) {
            $success &= $this->logToDatabase($logEntry);
        }
        
        // Log to file
        if ($this->logToFile) {
            $success &= $this->logToFile($logEntry);
        }
        
        // Log critical errors to system error log as well
        if ($level === self::CRITICAL) {
            error_log("CRITICAL: {$message} | Context: " . json_encode($context));
        }
        
        return $success;
    }
    
    /**
     * Debug logging
     */
    public function debug(string $message, string $type = self::TYPE_SYSTEM, array $context = []): bool {
        return $this->log(self::DEBUG, $message, $type, $context);
    }
    
    /**
     * Info logging
     */
    public function info(string $message, string $type = self::TYPE_SYSTEM, array $context = []): bool {
        return $this->log(self::INFO, $message, $type, $context);
    }
    
    /**
     * Warning logging
     */
    public function warning(string $message, string $type = self::TYPE_SYSTEM, array $context = []): bool {
        return $this->log(self::WARNING, $message, $type, $context);
    }
    
    /**
     * Error logging
     */
    public function error(string $message, string $type = self::TYPE_SYSTEM, array $context = []): bool {
        return $this->log(self::ERROR, $message, $type, $context);
    }
    
    /**
     * Critical logging
     */
    public function critical(string $message, string $type = self::TYPE_SYSTEM, array $context = []): bool {
        return $this->log(self::CRITICAL, $message, $type, $context);
    }
    
    /**
     * Log user activity
     */
    public function userActivity(string $action, string $description = '', array $data = []): bool {
        $userId = $_SESSION['user_id'] ?? null;
        $email = $_SESSION['user_email'] ?? 'anonymous';
        
        return $this->info("User {$email} performed action: {$action} - {$description}", self::TYPE_USER, [
            'user_id' => $userId,
            'action' => $action,
            'data' => $data
        ]);
    }
    
    /**
     * Log security event
     */
    public function security(string $event, string $description = '', array $context = []): bool {
        $context['ip_address'] = $this->getRealIpAddress();
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $this->warning("Security event: {$event} - {$description}", self::TYPE_SECURITY, $context);
    }
    
    /**
     * Log payment activity
     */
    public function payment(string $action, string $description = '', array $data = []): bool {
        return $this->info("Payment action: {$action} - {$description}", self::TYPE_PAYMENT, $data);
    }
    
    /**
     * Log email activity
     */
    public function email(string $action, string $to = '', string $subject = '', array $context = []): bool {
        return $this->info("Email {$action}: To {$to}, Subject: {$subject}", self::TYPE_EMAIL, $context);
    }
    
    /**
     * Log database performance
     */
    public function dbPerformance(string $query, float $executionTime, array $context = []): bool {
        if ($executionTime > 1.0) { // Log slow queries (>1 second)
            return $this->warning("Slow database query ({$executionTime}s): {$query}", self::TYPE_PERFORMANCE, $context);
        } elseif ($executionTime > 0.5) { // Log medium queries (>0.5 second)
            return $this->info("Medium database query ({$executionTime}s): {$query}", self::TYPE_PERFORMANCE, $context);
        }
        
        return true;
    }
    
    /**
     * Log API request
     */
    public function apiRequest(string $endpoint, string $method, int $responseCode, float $responseTime, array $context = []): bool {
        $level = $responseCode >= 400 ? self::WARNING : self::INFO;
        return $this->log($level, "API {$method} {$endpoint} - {$responseCode} ({$responseTime}s)", self::TYPE_API, $context);
    }
    
    /**
     * Create log entry array
     */
    private function createLogEntry(string $level, string $message, string $type, array $context): array {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $this->getRealIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }
    
    /**
     * Log to database
     */
    private function logToDatabase(array $logEntry): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (
                    user_id, action, description, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $action = "{$logEntry['type']}_{$logEntry['level']}";
            $description = $logEntry['message'] . (empty($logEntry['context']) ? '' : ' | Context: ' . json_encode($logEntry['context']));
            
            return $stmt->execute([
                $logEntry['user_id'],
                $action,
                $description,
                $logEntry['ip_address'],
                $logEntry['user_agent'],
                $logEntry['timestamp']
            ]);
            
        } catch (Exception $e) {
            error_log("Database logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log to file
     */
    private function logToFile(array $logEntry): bool {
        try {
            $filename = $this->logDir . date('Y-m-d') . '.log';
            
            $logLine = sprintf(
                "[%s] %s.%s: %s | User: %s | IP: %s | URI: %s %s | Memory: %s | Context: %s\n",
                $logEntry['timestamp'],
                $logEntry['level'],
                $logEntry['type'],
                $logEntry['message'],
                $logEntry['user_id'] ?? 'anonymous',
                $logEntry['ip_address'],
                $logEntry['http_method'],
                $logEntry['request_uri'],
                $this->formatBytes($logEntry['memory_usage']),
                json_encode($logEntry['context'])
            );
            
            return file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX) !== false;
            
        } catch (Exception $e) {
            error_log("File logging error: " . $e->getMessage());
            return false;
        }
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
     * Format bytes for human reading
     */
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs(int $limit = 100, string $level = null, string $type = null): array {
        try {
            $sql = "SELECT * FROM activity_logs WHERE 1=1";
            $params = [];
            
            if ($level) {
                $sql .= " AND action LIKE ?";
                $params[] = "%{$level}%";
            }
            
            if ($type) {
                $sql .= " AND action LIKE ?";
                $params[] = "{$type}%";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting recent logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old logs
     */
    public function cleanOldLogs(int $daysToKeep = 30): int {
        try {
            // Clean database logs
            $stmt = $this->db->prepare("
                DELETE FROM activity_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            $deletedRows = $stmt->rowCount();
            
            // Clean log files
            $files = glob($this->logDir . '*.log');
            $cutoffDate = strtotime("-{$daysToKeep} days");
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffDate) {
                    unlink($file);
                }
            }
            
            return $deletedRows;
            
        } catch (Exception $e) {
            error_log("Error cleaning old logs: " . $e->getMessage());
            return 0;
        }
    }
}

/**
 * Helper functions for easy logging
 */

function logger(): BuffaloLogger {
    return BuffaloLogger::getInstance();
}

function log_debug(string $message, string $type = BuffaloLogger::TYPE_SYSTEM, array $context = []): bool {
    return logger()->debug($message, $type, $context);
}

function log_info(string $message, string $type = BuffaloLogger::TYPE_SYSTEM, array $context = []): bool {
    return logger()->info($message, $type, $context);
}

function log_warning(string $message, string $type = BuffaloLogger::TYPE_SYSTEM, array $context = []): bool {
    return logger()->warning($message, $type, $context);
}

function log_error(string $message, string $type = BuffaloLogger::TYPE_SYSTEM, array $context = []): bool {
    return logger()->error($message, $type, $context);
}

function log_critical(string $message, string $type = BuffaloLogger::TYPE_SYSTEM, array $context = []): bool {
    return logger()->critical($message, $type, $context);
}

function log_user_activity(string $action, string $description = '', array $data = []): bool {
    return logger()->userActivity($action, $description, $data);
}

function log_security(string $event, string $description = '', array $context = []): bool {
    return logger()->security($event, $description, $context);
}

function log_payment(string $action, string $description = '', array $data = []): bool {
    return logger()->payment($action, $description, $data);
}

function log_email(string $action, string $to = '', string $subject = '', array $context = []): bool {
    return logger()->email($action, $to, $subject, $context);
}

// Auto-cleanup old logs occasionally
if (mt_rand(1, 1000) === 1) {
    logger()->cleanOldLogs();
}
?>