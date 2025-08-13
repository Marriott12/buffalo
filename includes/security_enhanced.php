<?php
/**
 * Buffalo Marathon 2025 - Enhanced Security Module
 * Advanced security features and monitoring
 */

if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access not permitted');
}

class SecurityEnhancer {
    
    /**
     * Enhanced rate limiting with IP tracking
     */
    public static function checkAdvancedRateLimit($action, $limit = 5, $window = 300) {
        $ip = self::getRealIpAddress();
        $key = $action . '_' . $ip;
        
        // Use database for persistent rate limiting
        $db = getDB();
        
        try {
            // Clean old attempts
            $db->prepare("DELETE FROM rate_limits WHERE created_at < ? AND action = ?")->execute([
                date('Y-m-d H:i:s', time() - $window), 
                $action
            ]);
            
            // Count current attempts
            $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND action = ?");
            $stmt->execute([$ip, $action]);
            $attempts = $stmt->fetchColumn();
            
            if ($attempts >= $limit) {
                // Log suspicious activity
                self::logSecurityEvent('rate_limit_exceeded', [
                    'ip' => $ip,
                    'action' => $action,
                    'attempts' => $attempts
                ]);
                return false;
            }
            
            // Record this attempt
            $db->prepare("INSERT INTO rate_limits (ip_address, action, created_at) VALUES (?, ?, NOW())")->execute([
                $ip, $action
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Rate limiting error: " . $e->getMessage());
            return true; // Fail open for availability
        }
    }
    
    /**
     * Detect suspicious activity patterns
     */
    public static function detectSuspiciousActivity() {
        $ip = self::getRealIpAddress();
        $db = getDB();
        
        $suspicious_indicators = [];
        
        // Multiple failed login attempts
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM activity_logs 
            WHERE action = 'login_failed' 
            AND ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        $failed_logins = $stmt->fetchColumn();
        
        if ($failed_logins > 10) {
            $suspicious_indicators[] = 'excessive_failed_logins';
        }
        
        // Rapid registration attempts
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM registrations 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            AND ip_address = ?
        ");
        $stmt->execute([$ip]);
        $rapid_registrations = $stmt->fetchColumn();
        
        if ($rapid_registrations > 3) {
            $suspicious_indicators[] = 'rapid_registrations';
        }
        
        // Access to admin areas without permission
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM activity_logs 
            WHERE action = 'unauthorized_access' 
            AND ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        $unauthorized_attempts = $stmt->fetchColumn();
        
        if ($unauthorized_attempts > 5) {
            $suspicious_indicators[] = 'unauthorized_access_attempts';
        }
        
        if (!empty($suspicious_indicators)) {
            self::logSecurityEvent('suspicious_activity_detected', [
                'ip' => $ip,
                'indicators' => $suspicious_indicators,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            // Consider temporary IP blocking for severe cases
            if (count($suspicious_indicators) >= 2) {
                self::temporaryIpBlock($ip, 1800); // 30 minutes
            }
        }
        
        return $suspicious_indicators;
    }
    
    /**
     * Implement Content Security Policy
     */
    public static function setSecurityHeaders() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'");
        
        // X-Frame-Options
        header('X-Frame-Options: DENY');
        
        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // Strict-Transport-Security (only on HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Enhanced CSRF protection with token rotation
     */
    public static function generateEnhancedCSRFToken() {
        // Rotate token every 30 minutes
        if (!isset($_SESSION['csrf_token_time']) || 
            (time() - $_SESSION['csrf_token_time']) > 1800) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate file uploads securely
     */
    public static function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf']) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_types)) {
            return false;
        }
        
        // Check MIME type
        $mime_type = mime_content_type($file['tmp_name']);
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        ];
        
        if (!isset($allowed_mimes[$extension]) || 
            $mime_type !== $allowed_mimes[$extension]) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get real IP address
     */
    private static function getRealIpAddress() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security events
     */
    private static function logSecurityEvent($event_type, $details = []) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO security_logs (event_type, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $event_type,
                self::getRealIpAddress(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                json_encode($details)
            ]);
            
        } catch (Exception $e) {
            error_log("Security logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Temporary IP blocking
     */
    private static function temporaryIpBlock($ip, $duration = 3600) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO ip_blocks (ip_address, blocked_until, reason, created_at)
                VALUES (?, ?, 'Suspicious activity detected', NOW())
                ON DUPLICATE KEY UPDATE blocked_until = VALUES(blocked_until)
            ");
            
            $stmt->execute([
                $ip,
                date('Y-m-d H:i:s', time() + $duration)
            ]);
            
        } catch (Exception $e) {
            error_log("IP blocking error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if IP is blocked
     */
    public static function isIpBlocked($ip = null) {
        $ip = $ip ?? self::getRealIpAddress();
        
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM ip_blocks 
                WHERE ip_address = ? AND blocked_until > NOW()
            ");
            $stmt->execute([$ip]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (Exception $e) {
            error_log("IP block check error: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize security headers on every request
SecurityEnhancer::setSecurityHeaders();

// Check for IP blocks
if (SecurityEnhancer::isIpBlocked()) {
    http_response_code(429);
    die('Access temporarily restricted due to suspicious activity.');
}
?>
