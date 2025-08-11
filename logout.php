<?php
require_once 'includes/functions.php';

// Log the logout activity if user is logged in
if (isLoggedIn()) {
    logActivity('user_logout', 'User logged out');
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Destroy session
session_destroy();

// Start a new session for flash message
session_start();
setFlash('info', 'You have been successfully logged out.');

// Redirect to home page
header('Location: index.php');
exit();
?>