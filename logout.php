<?php
/**
 * Buffalo Marathon 2025 - Logout Page
 * Handles user logout and session cleanup
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Start secure session for proper cleanup
startSecureSession();

try {
    // Log the logout activity if user is logged in
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? 'Unknown';
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (function_exists('logActivity')) {
            logActivity('user_logout', "User logged out: {$user_email}", $user_id);
        } else {
            error_log("User logout: {$user_email}" . ($user_id ? " (ID: {$user_id})" : ""));
        }
    }
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }
    
    // Clear any other authentication cookies
    if (isset($_COOKIE['auth_token'])) {
        setcookie('auth_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }
    
    // Clear session data
    $_SESSION = [];
    
    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Start a new clean session for flash message
    session_start();
    session_regenerate_id(true);
    
    // Set success message using the correct flash function
    if (function_exists('setFlashMessage')) {
        setFlashMessage('success', 'You have been successfully logged out. Thank you for visiting Buffalo Marathon 2025!');
    } elseif (function_exists('setFlash')) {
        setFlash('success', 'You have been successfully logged out. Thank you for visiting Buffalo Marathon 2025!');
    } else {
        // Fallback flash message
        $_SESSION['flash'][] = [
            'type' => 'success',
            'message' => 'You have been successfully logged out. Thank you for visiting Buffalo Marathon 2025!'
        ];
    }
    
} catch (Exception $e) {
    // Log error but continue with logout
    error_log("Logout error: " . $e->getMessage());
    
    // Still try to clear session
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Start new session for error message
    session_start();
    session_regenerate_id(true);
    
    if (function_exists('setFlashMessage')) {
        setFlashMessage('info', 'You have been logged out.');
    } else {
        $_SESSION['flash'][] = [
            'type' => 'info',
            'message' => 'You have been logged out.'
        ];
    }
}

// Redirect to home page
if (function_exists('redirectTo')) {
    redirectTo('/index.php');
} else {
    header('Location: index.php');
    exit();
}
?>