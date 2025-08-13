<?php
/**
 * Buffalo Marathon 2025 - Cache Clear AJAX Handler
 * Handles cache clearing requests from admin dashboard
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Clear all cache
    CacheManager::clear();
    
    // Log the action
    logActivity('cache_cleared', 'Admin cleared system cache', $_SESSION['user_id'] ?? null);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cache cleared successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Cache clear error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error clearing cache: ' . $e->getMessage()
    ]);
}
?>
