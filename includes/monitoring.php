<?php
/**
 * Buffalo Marathon 2025 - System Health Monitor
 * Real-time system monitoring and alerting
 */

if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access not permitted');
}

class SystemHealthMonitor {
    
    /**
     * Get comprehensive system health status
     */
    public static function getSystemHealth() {
        $health = [
            'overall_status' => 'healthy',
            'checks' => [],
            'alerts' => [],
            'performance' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Database connectivity
        $health['checks']['database'] = self::checkDatabase();
        
        // Registration capacity
        $health['checks']['capacity'] = self::checkCapacity();
        
        // Payment processing
        $health['checks']['payments'] = self::checkPayments();
        
        // Email system
        $health['checks']['email'] = self::checkEmailSystem();
        
        // Security status
        $health['checks']['security'] = self::checkSecurity();
        
        // Performance metrics
        $health['performance'] = self::getPerformanceMetrics();
        
        // Generate alerts
        $health['alerts'] = self::generateAlerts($health['checks']);
        
        // Determine overall status
        $health['overall_status'] = self::determineOverallStatus($health['checks']);
        
        return $health;
    }
    
    /**
     * Check database connectivity and performance
     */
    private static function checkDatabase() {
        try {
            $start_time = microtime(true);
            $db = getDB();
            
            // Test query
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $user_count = $stmt->fetchColumn();
            
            $response_time = (microtime(true) - $start_time) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($response_time, 2),
                'user_count' => $user_count,
                'last_check' => time()
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'last_check' => time()
            ];
        }
    }
    
    /**
     * Check registration capacity across categories
     */
    private static function checkCapacity() {
        try {
            $db = getDB();
            $stmt = $db->query("
                SELECT c.name, c.max_participants,
                       COUNT(r.id) as registered,
                       ROUND((COUNT(r.id) / c.max_participants) * 100, 1) as utilization
                FROM categories c
                LEFT JOIN registrations r ON c.id = r.category_id 
                    AND r.payment_status = 'confirmed'
                GROUP BY c.id, c.name, c.max_participants
            ");
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $max_utilization = 0;
            $critical_categories = [];
            
            foreach ($categories as $category) {
                $utilization = $category['utilization'];
                $max_utilization = max($max_utilization, $utilization);
                
                if ($utilization > 90) {
                    $critical_categories[] = $category['name'];
                }
            }
            
            $status = 'healthy';
            if ($max_utilization > 95) {
                $status = 'critical';
            } elseif ($max_utilization > 85) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'max_utilization' => $max_utilization,
                'critical_categories' => $critical_categories,
                'categories' => $categories
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check payment processing status
     */
    private static function checkPayments() {
        try {
            $db = getDB();
            
            // Recent payment activity
            $stmt = $db->query("
                SELECT COUNT(*) as count
                FROM registrations 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $recent_registrations = $stmt->fetchColumn();
            
            // Pending payments
            $stmt = $db->query("
                SELECT COUNT(*) as count
                FROM registrations 
                WHERE payment_status = 'pending'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $pending_payments = $stmt->fetchColumn();
            
            $status = 'healthy';
            if ($pending_payments > 50) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'recent_registrations' => $recent_registrations,
                'pending_payments' => $pending_payments
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check email system status
     */
    private static function checkEmailSystem() {
        try {
            $db = getDB();
            
            // Check email queue
            $stmt = $db->query("
                SELECT COUNT(*) as failed_count
                FROM email_queue 
                WHERE status = 'failed'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $failed_emails = $stmt->fetchColumn();
            
            $status = $failed_emails > 10 ? 'warning' : 'healthy';
            
            return [
                'status' => $status,
                'failed_emails_last_hour' => $failed_emails
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check security status
     */
    private static function checkSecurity() {
        $security_checks = [
            'https_enabled' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'sessions_secure' => ini_get('session.cookie_secure') == 1,
            'display_errors_off' => ini_get('display_errors') == 0,
            'error_reporting_production' => error_reporting() === (E_ERROR | E_WARNING | E_PARSE)
        ];
        
        $passed_checks = array_sum($security_checks);
        $total_checks = count($security_checks);
        
        $status = 'healthy';
        if ($passed_checks < $total_checks) {
            $status = $passed_checks < ($total_checks * 0.5) ? 'critical' : 'warning';
        }
        
        return [
            'status' => $status,
            'checks_passed' => $passed_checks,
            'total_checks' => $total_checks,
            'details' => $security_checks
        ];
    }
    
    /**
     * Get performance metrics
     */
    private static function getPerformanceMetrics() {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'php_version' => PHP_VERSION,
            'server_load' => sys_getloadavg()[0] ?? 'N/A'
        ];
    }
    
    /**
     * Generate alerts based on health checks
     */
    private static function generateAlerts($checks) {
        $alerts = [];
        
        foreach ($checks as $check_name => $check_data) {
            if ($check_data['status'] === 'critical') {
                $alerts[] = [
                    'level' => 'critical',
                    'component' => $check_name,
                    'message' => "Critical issue detected in {$check_name}",
                    'timestamp' => time()
                ];
            } elseif ($check_data['status'] === 'warning') {
                $alerts[] = [
                    'level' => 'warning',
                    'component' => $check_name,
                    'message' => "Warning condition in {$check_name}",
                    'timestamp' => time()
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Determine overall system status
     */
    private static function determineOverallStatus($checks) {
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                return 'critical';
            }
        }
        
        foreach ($checks as $check) {
            if ($check['status'] === 'warning') {
                return 'warning';
            }
        }
        
        return 'healthy';
    }
}
?>
