<?php
/**
 * Buffalo Marathon 2025 - 404 Error Page
 * Production Ready - 2025-08-08 14:16:14 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Buffalo Marathon 2025</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root { --army-green: #4B5320; --army-green-dark: #222B1F; --gold: #FFD700; }
        .error-page { background: linear-gradient(135deg, var(--army-green), var(--army-green-dark)); color: white; min-height: 100vh; display: flex; align-items: center; }
        .error-content { text-align: center; }
        .error-number { font-size: 10rem; font-weight: 900; color: var(--gold); text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .btn-home { background: var(--gold); color: var(--army-green); border: none; padding: 1rem 2rem; font-weight: 600; text-transform: uppercase; border-radius: 50px; }
        .btn-home:hover { background: #DAA520; color: var(--army-green-dark); transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="error-content">
                        <div class="error-number">404</div>
                        <h1 class="display-4 fw-bold mb-4">Page Not Found</h1>
                        <p class="lead mb-5">
                            Sorry, the page you're looking for doesn't exist. 
                            Maybe you took a wrong turn on your marathon journey?
                        </p>
                        
                        <div class="mb-4">
                            <a href="/" class="btn btn-home btn-lg me-3">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                            <a href="/categories.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-running me-2"></i>View Categories
                            </a>
                        </div>
                        
                        <div class="mt-5">
                            <h5 class="mb-3">Quick Links</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="/register.php" class="text-white text-decoration-none">
                                        <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                        <small>Register</small>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/schedule.php" class="text-white text-decoration-none">
                                        <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i>
                                        <small>Schedule</small>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/faq.php" class="text-white text-decoration-none">
                                        <i class="fas fa-question-circle fa-2x mb-2 d-block"></i>
                                        <small>FAQ</small>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/contact.php" class="text-white text-decoration-none">
                                        <i class="fas fa-envelope fa-2x mb-2 d-block"></i>
                                        <small>Contact</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>