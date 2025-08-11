<?php
require_once '../includes/functions.php';
$page_title = 'Page Not Found';
http_response_code(404);
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle fa-5x text-warning"></i>
            </div>
            <h1 class="display-1 fw-bold text-army-green">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            <p class="lead mb-4">The page you're looking for doesn't exist or has been moved.</p>
            
            <div class="mb-4">
                <a href="/" class="btn btn-army-green btn-lg me-3">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
                <a href="/categories.php" class="btn btn-outline-army-green btn-lg">
                    <i class="fas fa-running me-2"></i>View Categories
                </a>
            </div>
            
            <p><a href="/contact.php">Contact us</a> if you believe this is an error.</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>