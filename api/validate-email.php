<?php
/**
 * Buffalo Marathon 2025 - Email Validation API
 * Real-time email validation endpoint
 * Created: 2025-01-09
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Rate limiting for API calls
rate_limit_middleware('api_call');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    
    if (!$email) {
        echo json_encode(['error' => 'Email required']);
        exit;
    }
    
    if (!validateEmail($email)) {
        echo json_encode(['available' => false, 'reason' => 'Invalid email format']);
        exit;
    }
    
    $available = isEmailAvailable($email);
    
    echo json_encode([
        'available' => $available,
        'email' => $email,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    log_error("Email validation API error: " . $e->getMessage(), 'api');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function isEmailAvailable($email) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        return $stmt->fetchColumn() == 0;
        
    } catch (Exception $e) {
        log_error("Error checking email availability: " . $e->getMessage(), 'api');
        return false;
    }
}
?>