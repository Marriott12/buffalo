<?php
/**
 * Buffalo Marathon 2025 - Availability API
 * Real-time category availability endpoint
 * Created: 2025-01-09
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Rate limiting for API calls
rate_limit_middleware('api_call');

try {
    $categoryId = $_GET['category'] ?? null;
    
    if ($categoryId) {
        // Single category
        $data = getSingleCategoryAvailability($categoryId);
    } else {
        // All categories
        $data = getAllCategoriesAvailability();
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    log_error("Availability API error: " . $e->getMessage(), 'api');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function getSingleCategoryAvailability($categoryId) {
    $categories = cache_categories();
    $category = array_filter($categories, function($c) use ($categoryId) {
        return $c['id'] == $categoryId;
    });
    
    $category = reset($category);
    if (!$category) {
        throw new Exception('Category not found');
    }
    
    $category['is_full'] = $category['registration_count'] >= $category['max_participants'];
    
    return [
        'category' => $category,
        'timestamp' => time()
    ];
}

function getAllCategoriesAvailability() {
    $categories = cache_categories();
    
    foreach ($categories as &$category) {
        $category['is_full'] = $category['registration_count'] >= $category['max_participants'];
    }
    
    return [
        'categories' => $categories,
        'timestamp' => time()
    ];
}
?>