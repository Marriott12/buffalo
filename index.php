<?php
/**
 * Buffalo Marathon 2025 - Homepage
 * Production Ready - Updated: 2025-08-13
 */

define('BUFFALO_SECURE_ACCESS', true);

// Error handling for production
error_reporting(0);
ini_set('display_errors', 0);

// Initialize default values first
$page_title = 'Buffalo Marathon 2025 - October 11, 2025';
$page_description = 'Join Buffalo Marathon 2025 at Buffalo Park Recreation Centre. Multiple race categories, amazing prizes, and unforgettable experience. Register now!';
$stats = ['total_registrations' => 0, 'confirmed_payments' => 0, 'pending_payments' => 0, 'total_users' => 0, 'active_categories' => 4];
$categories = [];
$announcements = [];
$days_until_marathon = 58;
$days_until_deadline = 47;
$days_until_early_bird = 17;
$registration_open = true;
$early_bird_active = true;
$marathon_status = 'open';

// Try to load functions safely
try {
    if (file_exists('includes/functions.php')) {
        require_once 'includes/functions.php';
    }
} catch (Exception $e) {
    error_log("Functions load error: " . $e->getMessage());
}

// Try to get real data if functions are available
try {
    if (function_exists('getDBStats')) {
        $stats = getDBStats();
    }
    if (function_exists('getDaysUntilMarathon')) {
        $days_until_marathon = getDaysUntilMarathon();
    }
    if (function_exists('getDaysUntilDeadline')) {
        $days_until_deadline = getDaysUntilDeadline();
    }
    if (function_exists('getDaysUntilEarlyBird')) {
        $days_until_early_bird = getDaysUntilEarlyBird();
    }
    if (function_exists('isRegistrationOpen')) {
        $registration_open = isRegistrationOpen();
    }
    if (function_exists('isEarlyBirdActive')) {
        $early_bird_active = isEarlyBirdActive();
    }
    if (function_exists('getMarathonStatus')) {
        $marathon_status = getMarathonStatus();
    }
} catch (Exception $e) {
    error_log("Error getting data: " . $e->getMessage());
}

// Get categories for display
try {
    if (function_exists('getDatabase')) {
        $db = getDatabase();
        $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY price ASC");
        $categories = $stmt->fetchAll();
    } else {
        // Fallback static categories
        $categories = [
            ['id' => 1, 'name' => 'Full Marathon (42.2km)', 'distance' => '42.2km', 'price' => 150.00, 'early_bird_price' => 120.00, 'description' => 'The ultimate challenge - 42.2km of pure determination'],
            ['id' => 2, 'name' => 'Half Marathon (21.1km)', 'distance' => '21.1km', 'price' => 100.00, 'early_bird_price' => 80.00, 'description' => 'Perfect for experienced runners - 21.1km of scenic route'],
            ['id' => 3, 'name' => '10K Run', 'distance' => '10km', 'price' => 50.00, 'early_bird_price' => 40.00, 'description' => 'Great for fitness enthusiasts - 10km of energy'],
            ['id' => 4, 'name' => '5K Fun Run', 'distance' => '5km', 'price' => 30.00, 'early_bird_price' => 25.00, 'description' => 'Perfect for beginners and families - 5km of fun']
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    // Use fallback categories
    $categories = [
        ['id' => 1, 'name' => 'Full Marathon (42.2km)', 'distance' => '42.2km', 'price' => 150.00, 'early_bird_price' => 120.00, 'description' => 'The ultimate challenge'],
        ['id' => 2, 'name' => 'Half Marathon (21.1km)', 'distance' => '21.1km', 'price' => 100.00, 'early_bird_price' => 80.00, 'description' => 'Perfect for experienced runners'],
        ['id' => 3, 'name' => '10K Run', 'distance' => '10km', 'price' => 50.00, 'early_bird_price' => 40.00, 'description' => 'Great for fitness enthusiasts'],
        ['id' => 4, 'name' => '5K Fun Run', 'distance' => '5km', 'price' => 30.00, 'early_bird_price' => 25.00, 'description' => 'Perfect for beginners and families']
    ];
}

// Get recent announcements safely
try {
    if (function_exists('getDatabase')) {
        $db = getDatabase();
        $stmt = $db->query("
            SELECT title, content, type, created_at 
            FROM announcements 
            WHERE is_active = 1 AND target_audience IN ('all', 'unregistered') 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $announcements = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log("Error fetching announcements: " . $e->getMessage());
    $announcements = [];
}

// Safety functions
function safe_number_format($number, $decimals = 0) {
    return number_format($number ?? 0, $decimals);
}

function safe_money_format($amount) {
    return 'ZMW ' . number_format($amount ?? 0, 2);
}

// Include header safely
try {
    if (file_exists('includes/header.php')) {
        include 'includes/header.php';
    } else {
        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($page_title) . '</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>';
    }
} catch (Exception $e) {
    echo '<!DOCTYPE html><html><head><title>Buffalo Marathon 2025</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>';
}
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">🏃‍♂️ Buffalo Marathon 2025</h1>
                <p class="lead mb-4">Join us for an unforgettable running experience at Buffalo Park Recreation Centre on October 11, 2025. Multiple race categories for all fitness levels!</p>
                <div class="row mb-4">
                    <div class="col-6 col-md-3 text-center">
                        <h3 class="fw-bold"><?= $days_until_marathon ?></h3>
                        <small>Days to Race</small>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <h3 class="fw-bold"><?= safe_number_format($stats['total_registrations']) ?></h3>
                        <small>Registered</small>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <h3 class="fw-bold"><?= count($categories) ?></h3>
                        <small>Categories</small>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <h3 class="fw-bold">ZMW</h3>
                        <small>Prizes</small>
                    </div>
                </div>
                <?php if ($registration_open): ?>
                <a href="register.php" class="btn btn-warning btn-lg me-3">
                    <i class="fas fa-running"></i> Register Now
                </a>
                <?php endif; ?>
                <a href="#categories" class="btn btn-outline-light btn-lg">View Categories</a>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/images/marathon-hero.jpg" alt="Buffalo Marathon" class="img-fluid rounded shadow" 
                     style="max-height: 400px;" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</section>

<!-- Early Bird Alert -->
<?php if ($early_bird_active): ?>
<section class="alert-section bg-warning text-dark py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h5 class="mb-0">🎉 Early Bird Special! Save up to ZMW 30 on registration fees</h5>
                <small>Only <?= $days_until_early_bird ?> days left to get early bird pricing!</small>
            </div>
            <div class="col-lg-4 text-end">
                <a href="register.php" class="btn btn-dark">Register with Discount</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Race Categories -->
<section id="categories" class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">🏆 Race Categories</h2>
                <p class="lead text-muted">Choose your challenge - from fun runs to full marathons</p>
            </div>
        </div>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body text-center">
                        <div class="category-icon mb-3">
                            <?php
                            $icons = [
                                'Full Marathon' => '🏃‍♂️',
                                'Half Marathon' => '🏃‍♀️', 
                                '10K' => '🚀',
                                '5K' => '⭐'
                            ];
                            $icon = '🏃';
                            foreach ($icons as $key => $value) {
                                if (strpos($category['name'], $key) !== false) {
                                    $icon = $value;
                                    break;
                                }
                            }
                            echo '<span style="font-size: 3rem;">' . $icon . '</span>';
                            ?>
                        </div>
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($category['name']) ?></h5>
                        <p class="text-muted mb-3"><?= htmlspecialchars($category['description'] ?? 'Join this exciting race category') ?></p>
                        <div class="price-section mb-3">
                            <?php if ($early_bird_active && isset($category['early_bird_price'])): ?>
                                <span class="text-decoration-line-through text-muted">ZMW <?= number_format($category['price'], 2) ?></span><br>
                                <span class="h4 text-success">ZMW <?= number_format($category['early_bird_price'], 2) ?></span>
                                <small class="text-success d-block">Early Bird Price!</small>
                            <?php else: ?>
                                <span class="h4 text-primary">ZMW <?= number_format($category['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="distance-badge mb-3">
                            <span class="badge bg-primary"><?= htmlspecialchars($category['distance']) ?></span>
                        </div>
                        <?php if ($registration_open): ?>
                        <a href="register-marathon.php?category=<?= $category['id'] ?>" class="btn btn-primary w-100">
                            Register for this Race
                        </a>
                        <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>Registration Closed</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Event Details -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <h3 class="fw-bold mb-4">📅 Event Details</h3>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <i class="fas fa-calendar text-primary me-2"></i>
                        <strong>Date:</strong> October 11, 2025 (Saturday)
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-clock text-primary me-2"></i>
                        <strong>Start Time:</strong> 07:00 AM
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <strong>Venue:</strong> Buffalo Park Recreation Centre, Lusaka
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-trophy text-primary me-2"></i>
                        <strong>Prizes:</strong> Cash prizes and medals for winners
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-tshirt text-primary me-2"></i>
                        <strong>Includes:</strong> Race T-shirt, finisher's medal, refreshments
                    </li>
                </ul>
            </div>
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4">🎯 Why Join Buffalo Marathon?</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <h5>🏆 Professional Event</h5>
                            <p class="text-muted">Certified course with timing chips and official results</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <h5>🎁 Amazing Prizes</h5>
                            <p class="text-muted">Cash prizes, trophies, and medals for top finishers</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <h5>🍎 Health & Wellness</h5>
                            <p class="text-muted">Promote fitness and healthy lifestyle in our community</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <h5>🤝 Community Spirit</h5>
                            <p class="text-muted">Bring together runners from all backgrounds and abilities</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Registration Timeline -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="fw-bold">⏰ Registration Timeline</h2>
                <p class="lead text-muted">Important dates you need to know</p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="timeline">
                    <div class="timeline-item mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-md-end">
                                <h5 class="text-success">Early Bird Period</h5>
                                <p class="text-muted">Save up to ZMW 30</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="timeline-badge bg-success">
                                    <?= $days_until_early_bird ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Days remaining for early bird pricing</small>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-md-end">
                                <h5 class="text-warning">Registration Deadline</h5>
                                <p class="text-muted">Last day to register</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="timeline-badge bg-warning">
                                    <?= $days_until_deadline ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Days until registration closes</small>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-md-end">
                                <h5 class="text-primary">Race Day</h5>
                                <p class="text-muted">Buffalo Marathon 2025</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="timeline-badge bg-primary">
                                    <?= $days_until_marathon ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Days until the big day!</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Announcements -->
<?php if (!empty($announcements)): ?>
<section class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="fw-bold">📢 Latest Announcements</h2>
            </div>
        </div>
        <div class="row">
            <?php foreach ($announcements as $announcement): ?>
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($announcement['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars(substr($announcement['content'], 0, 150)) ?>...</p>
                        <small class="text-muted"><?= date('M j, Y', strtotime($announcement['created_at'])) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Ready to Run with Us?</h2>
        <p class="lead mb-4">Join hundreds of runners in Buffalo Marathon 2025. Register now and be part of something amazing!</p>
        <?php if ($registration_open): ?>
        <a href="register.php" class="btn btn-warning btn-lg me-3">
            <i class="fas fa-user-plus"></i> Register Now
        </a>
        <a href="login.php" class="btn btn-outline-light btn-lg">
            <i class="fas fa-sign-in-alt"></i> Already Registered? Login
        </a>
        <?php else: ?>
        <p class="alert alert-warning">Registration is currently closed.</p>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer safely
try {
    if (file_exists('includes/footer.php')) {
        include 'includes/footer.php';
    } else {
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    }
} catch (Exception $e) {
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
}
?>
