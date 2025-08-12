<?php
/**
 * Buffalo Marathon 2025 - Statistics API
 * Real-time statistics endpoint
 * Created: 2025-01-09
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Rate limiting for API calls
rate_limit_middleware('api_call');

try {
    $stats = getLiveStatistics();
    echo json_encode($stats);
    
} catch (Exception $e) {
    log_error("Statistics API error: " . $e->getMessage(), 'api');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function getLiveStatistics() {
    $cacheKey = 'live_statistics';
    $stats = cache_get($cacheKey);
    
    if ($stats === null) {
        try {
            $db = getDB();
            $stats = [];
            
            // Total registrations
            $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status != 'cancelled'");
            $stats['total_registrations'] = (int)$stmt->fetchColumn();
            
            // Confirmed registrations
            $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid'");
            $stats['confirmed_registrations'] = (int)$stmt->fetchColumn();
            
            // Total revenue
            $stmt = $db->query("
                SELECT SUM(c.price) 
                FROM registrations r 
                JOIN categories c ON r.category_id = c.id 
                WHERE r.payment_status = 'paid'
            ");
            $stats['total_revenue'] = (float)$stmt->fetchColumn() ?: 0;
            
            // Today's registrations
            $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE DATE(registered_at) = CURDATE()");
            $stats['today_registrations'] = (int)$stmt->fetchColumn();
            
            // Spots remaining across all categories
            $stmt = $db->query("
                SELECT SUM(c.max_participants - COALESCE(rs.total_registrations, 0)) as spots_remaining
                FROM categories c
                LEFT JOIN registration_stats rs ON c.id = rs.category_id
                WHERE c.is_active = 1
            ");
            $stats['spots_remaining'] = (int)$stmt->fetchColumn();
            
            // Cache for 1 minute
            cache_set($cacheKey, $stats, 60);
            
        } catch (Exception $e) {
            log_error("Error getting live statistics: " . $e->getMessage(), 'api');
            throw $e;
        }
    }
    
    $stats['timestamp'] = time();
    return $stats;
}
?>