<?php
/**
 * Buffalo Marathon 2025 - Homepage
 * Production Ready - Generated: 2025-08-08 10:33:55 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

$page_title = 'Buffalo Marathon 2025 - October 11, 2025';
$page_description = 'Join Buffalo Marathon 2025 at Buffalo Park Recreation Centre. Multiple race categories, amazing prizes, and unforgettable experience. Register now!';

// Get current statistics
$stats = getDBStats();
$days_until_marathon = getDaysUntilMarathon();
$days_until_deadline = getDaysUntilDeadline();
$days_until_early_bird = getDaysUntilEarlyBird();
$registration_open = isRegistrationOpen();
$early_bird_active = isEarlyBirdActive();
$marathon_status = getMarathonStatus();

// Get categories for display
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT id, name, distance, description, price, max_participants 
        FROM categories 
        WHERE is_active = 1 
        ORDER BY FIELD(name, 'Full Marathon', 'Half Marathon', 'Power Challenge', 'Family Fun Run', 'VIP Run', 'Kid Run')
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get recent announcements
try {
    $stmt = $db->query("
        SELECT title, content, type, created_at 
        FROM announcements 
        WHERE is_active = 1 AND target_audience IN ('all', 'unregistered') 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    $announcements = [];
}

// Current registration count
$current_registrations = $stats['total_registrations'];
$confirmed_payments = $stats['confirmed_payments'];

// Include header
include 'includes/header.php';
?>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Event",
        "name": "Buffalo Marathon 2025",
        "startDate": "2025-10-11T07:00:00+02:00",
        "endDate": "2025-10-11T15:00:00+02:00",
        "location": {
            "@type": "Place",
            "name": "Buffalo Park Recreation Centre",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Chalala-Along Joe Chibangu Road",
                "addressLocality": "Lusaka",
                "addressCountry": "ZM"
            }
        },
        "description": "Buffalo Marathon 2025 featuring multiple race categories including Full Marathon (42K), Half Marathon (21K), Power Challenge (10K), Family Fun Run (5K), VIP Run, and Kid Run.",
        "organizer": {
            "@type": "Organization",
            "name": "Buffalo Marathon Organization"
        },
        "offers": {
            "@type": "Offer",
            "price": "450-600",
            "priceCurrency": "ZMW",
            "availability": "<?php echo $registration_open ? 'InStock' : 'SoldOut'; ?>",
            "validFrom": "2025-08-08T00:00:00+02:00",
            "validThrough": "2025-09-30T23:59:59+02:00"
        }
    }
    </script>
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-light: #8F9779;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
            --white: #FFFFFF;
        }
        
        /* Hero Section with Video Background */
        .hero-section {
            position: relative;
            height: 100vh;
            min-height: 600px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: 1;
            transform: translateX(-50%) translateY(-50%);
            filter: brightness(0.7);
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(75, 83, 32, 0.8) 0%, 
                rgba(34, 43, 31, 0.9) 50%,
                rgba(75, 83, 32, 0.8) 100%);
            z-index: 2;
        }
        
        .hero-content {
            position: relative;
            z-index: 3;
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 1200px;
        }
        
        .countdown-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 3rem 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .countdown-box {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 1.5rem 1rem;
            margin: 0.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .countdown-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .countdown-number {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .countdown-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 0.5rem;
            font-weight: 600;
        }
        
        .hero-buttons .btn {
            margin: 0.5rem;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-hero-primary {
            background: linear-gradient(45deg, var(--gold), #FFA500);
            border: none;
            color: var(--army-green);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 215, 0, 0.6);
            color: var(--army-green);
        }
        
        .btn-hero-secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-hero-secondary:hover {
            background: white;
            color: var(--army-green);
            transform: translateY(-2px);
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0.25rem;
        }
        
        .status-open {
            background: #28a745;
            color: white;
        }
        
        .status-early-bird {
            background: var(--gold);
            color: var(--army-green);
        }
        
        .status-closing {
            background: #ffc107;
            color: var(--army-green);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 3rem;
            }
            
            .countdown-number {
                font-size: 2.5rem;
            }
            
            .countdown-container {
                padding: 1.5rem;
                margin: 2rem 0;
            }
            
            .hero-buttons .btn {
                display: block;
                width: 80%;
                margin: 0.5rem auto;
            }
        }
        
        /* Sections styling */
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-header h2 {
            color: var(--army-green);
            font-weight: 900;
            margin-bottom: 1rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        
        .feature-icon {
            font-size: 3.5rem;
            color: var(--army-green);
            margin-bottom: 1.5rem;
        }
        
        .category-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .category-price {
            background: var(--gold);
            color: var(--army-green);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: inline-block;
            font-weight: 900;
            font-size: 1.1rem;
            margin-top: 1rem;
        }
        
        .stats-container {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 4rem 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            color: var(--gold);
            display: block;
        }
        
        .stat-label {
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        /* Animation for numbers */
        .animate-number {
            animation: countUp 2s ease-out;
        }
        
        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <!-- Flash Messages -->
    <?php
    $flash_messages = getAllFlashMessages();
    foreach ($flash_messages as $type => $message):
        $alert_class = match($type) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-info'
        };
        $icon = match($type) {
            'success' => 'fas fa-check-circle',
            'error' => 'fas fa-exclamation-triangle',
            'warning' => 'fas fa-exclamation-circle',
            'info' => 'fas fa-info-circle',
            default => 'fas fa-info-circle'
        };
    ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show m-0" style="position: fixed; top: 76px; left: 0; right: 0; z-index: 1050; border-radius: 0;">
            <div class="container">
                <i class="<?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Hero Section with Buffalo Video Background -->
    <section class="hero-section">
        <!-- Video Background -->
        <video autoplay muted loop class="hero-video" poster="/assets/images/buffalo-poster.jpg">
            <source src="/assets/videos/buffalo-running.mp4" type="video/mp4">
            <source src="/assets/videos/buffalo-running.webm" type="video/webm">
            <!-- Fallback for browsers that don't support video -->
            Your browser does not support the video tag.
        </video>
        
        <!-- Overlay -->
        <div class="hero-overlay"></div>
        
        <!-- Content -->
        <div class="hero-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <!-- Status Badges -->
                        <div class="mb-4">
                            <?php if ($registration_open): ?>
                                <span class="status-badge status-open">
                                    <i class="fas fa-check-circle me-1"></i>Registration Open
                                </span>
                                <?php if ($early_bird_active): ?>
                                    <span class="status-badge status-early-bird">
                                        <i class="fas fa-clock me-1"></i>Early Bird Active
                                    </span>
                                <?php endif; ?>
                                <?php if ($days_until_deadline <= 10): ?>
                                    <span class="status-badge status-closing">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Registration Closing Soon
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-badge" style="background: #dc3545; color: white;">
                                    <i class="fas fa-times-circle me-1"></i>Registration Closed
                                </span>
                            <?php endif; ?>
                        </div>

                        <h1 class="display-2 fw-bold mb-4 animate__animated animate__fadeInUp">
                            Buffalo Marathon 2025
                        </h1>
                        
                        <p class="lead mb-4 fs-3 animate__animated animate__fadeInUp animate__delay-1s">
                            <strong>Saturday, October 11, 2025</strong><br>
                            Buffalo Park Recreation Centre, Lusaka
                        </p>
                        
                        <p class="lead mb-5 animate__animated animate__fadeInUp animate__delay-2s">
                            Join Zambia's premier running event featuring 6 race categories, 
                            world-class entertainment, and an unforgettable experience for all fitness levels.
                        </p>
                        
                        <!-- Live Countdown -->
                        <div class="countdown-container animate__animated animate__fadeInUp animate__delay-3s">
                            <h3 class="mb-4 fw-bold">Event Countdown</h3>
                            <div class="row justify-content-center">
                                <div class="col-6 col-md-3">
                                    <div class="countdown-box">
                                        <div class="countdown-number animate-number" id="days-marathon"><?php echo $days_until_marathon; ?></div>
                                        <div class="countdown-label">Days Until Race</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="countdown-box">
                                        <div class="countdown-number animate-number" id="days-deadline"><?php echo $days_until_deadline; ?></div>
                                        <div class="countdown-label">Days to Register</div>
                                    </div>
                                </div>
                                <?php if ($early_bird_active): ?>
                                <div class="col-6 col-md-3">
                                    <div class="countdown-box">
                                        <div class="countdown-number animate-number" id="days-early-bird"><?php echo $days_until_early_bird; ?></div>
                                        <div class="countdown-label">Early Bird Days</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="col-6 col-md-3">
                                    <div class="countdown-box">
                                        <div class="countdown-number animate-number" id="total-registered"><?php echo number_format($current_registrations); ?></div>
                                        <div class="countdown-label">Registered</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="hero-buttons animate__animated animate__fadeInUp animate__delay-4s">
                            <?php if ($registration_open): ?>
                                <?php if (isLoggedIn()): ?>
                                    <a href="/register-marathon.php" class="btn btn-hero-primary btn-lg">
                                        <i class="fas fa-running me-2"></i>Register for Marathon
                                    </a>
                                    <a href="/dashboard.php" class="btn btn-hero-secondary btn-lg">
                                        <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                                    </a>
                                <?php else: ?>
                                    <a href="/register.php" class="btn btn-hero-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Create Account & Register
                                    </a>
                                    <a href="/login.php" class="btn btn-hero-secondary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login to Register
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="/categories.php" class="btn btn-hero-secondary btn-lg">
                                    <i class="fas fa-info-circle me-2"></i>View Event Details
                                </a>
                                <a href="/results.php" class="btn btn-hero-primary btn-lg">
                                    <i class="fas fa-trophy me-2"></i>View Results
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Statistics Section -->
    <section class="stats-container">
        <div class="container">
            <div class="row text-center">
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number animate-number"><?php echo number_format($current_registrations); ?></span>
                        <div class="stat-label">Total Registered</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number animate-number"><?php echo number_format($confirmed_payments); ?></span>
                        <div class="stat-label">Confirmed Payments</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number animate-number"><?php echo formatCurrency($stats['total_revenue']); ?></span>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number animate-number"><?php echo count($categories); ?></span>
                        <div class="stat-label">Race Categories</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- What's Included Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="section-header">
                <h2 class="display-5 fw-bold">What's Included in Every Registration</h2>
                <p class="lead">Every participant receives amazing perks and unforgettable experiences</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Premium T-Shirt</h5>
                        <p class="text-muted">High-quality moisture-wicking fabric with official Buffalo Marathon 2025 design</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Finisher's Medal</h5>
                        <p class="text-muted">Beautiful commemorative medal for all finishers across all race categories</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Race Number & Timing</h5>
                        <p class="text-muted">Official race bib with integrated timing chip for accurate race results</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-glass-water"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Refreshment Voucher</h5>
                        <p class="text-muted">Complimentary drink voucher for post-race refreshments and hydration</p>
                    </div>
                </div>
            </div>
            
            <!-- Additional Features -->
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h4 class="text-army-green mb-4 fw-bold">Plus Amazing Event Activities!</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-dumbbell text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Pre & Post-Race Aerobics</h6>
                                    <small class="text-muted">Professional fitness sessions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-music text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Zambia Army Pop Band</h6>
                                    <small class="text-muted">Live entertainment throughout</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-microphone text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Special Guest Artists</h6>
                                    <small class="text-muted">Surprise performances</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-utensils text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Food Zones & Braai Packs</h6>
                                    <small class="text-muted">Delicious local cuisine</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-wine-glass text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Chill Lounge</h6>
                                    <small class="text-muted">Relaxation area for families</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-child text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Kids Zone Activities</h6>
                                    <small class="text-muted">Fun activities for children</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Race Categories Section -->
    <section class="py-5">
        <div class="container">
            <div class="section-header">
                <h2 class="display-5 fw-bold">Choose Your Challenge</h2>
                <p class="lead">Six exciting race categories designed for every fitness level and age group</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="category-card">
                        <div class="category-header">
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($category['distance']); ?></p>
                            <div class="category-price"><?php echo formatCurrency($category['price'] ?? 0); ?></div>
                        </div>
                        <div class="p-4">
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
                            
                            <?php if ($category['max_participants'] > 0): ?>
                                <?php
                                // Get current registrations for this category
                                try {
                                    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE category_id = ? AND payment_status != 'cancelled'");
                                    $stmt->execute([$category['id']]);
                                    $current_count = $stmt->fetchColumn();
                                    $percentage = ($current_count / $category['max_participants']) * 100;
                                } catch (Exception $e) {
                                    $current_count = 0;
                                    $percentage = 0;
                                }
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Availability</small>
                                        <small class="text-muted"><?php echo $current_count; ?>/<?php echo $category['max_participants']; ?></small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-army-green" 
                                             style="width: <?php echo min($percentage, 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($registration_open): ?>
                                <?php if (isLoggedIn()): ?>
                                    <a href="/register-marathon.php?category=<?php echo $category['id']; ?>" 
                                       class="btn btn-army-green w-100">
                                        <i class="fas fa-running me-2"></i>Register Now
                                    </a>
                                <?php else: ?>
                                    <a href="/register.php?category=<?php echo $category['id']; ?>" 
                                       class="btn btn-army-green w-100">
                                        <i class="fas fa-user-plus me-2"></i>Sign Up to Register
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-times-circle me-2"></i>Registration Closed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <?php if (!empty($announcements)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="section-header">
                <h2 class="display-6 fw-bold">Latest Updates</h2>
                <p class="lead">Stay informed with the latest news and announcements</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($announcements as $announcement): ?>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php
                                $icon = match($announcement['type']) {
                                    'urgent' => 'fas fa-exclamation-triangle text-danger',
                                    'update' => 'fas fa-info-circle text-primary',
                                    'weather' => 'fas fa-cloud text-info',
                                    default => 'fas fa-bullhorn text-success'
                                };
                                ?>
                                <i class="<?php echo $icon; ?> me-2"></i>
                                <small class="text-muted"><?php echo formatDateTime($announcement['created_at']); ?></small>
                            </div>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo truncateText(htmlspecialchars($announcement['content']), 150); ?>
                            </p>
                            <a href="/announcements.php#<?php echo $announcement['id']; ?>" class="btn btn-outline-army-green btn-sm">
                                Read More <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact & Location Section -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-6 fw-bold text-army-green mb-4">Event Location</h2>
                    <div class="mb-4">
                        <h5 class="fw-bold mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <?php echo EVENT_VENUE; ?>
                        </h5>
                        <p class="text-muted mb-3"><?php echo EVENT_ADDRESS; ?><br><?php echo EVENT_CITY; ?></p>
                        
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar text-army-green me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Event Date</small>
                                        <strong><?php echo formatDate(MARATHON_DATE); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-army-green me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Start Time</small>
                                        <strong><?php echo date('g:i A', strtotime(MARATHON_TIME)); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <a href="/schedule.php" class="btn btn-army-green">
                            <i class="fas fa-calendar-alt me-2"></i>View Full Schedule
                        </a>
                        <a href="/contact.php" class="btn btn-outline-army-green">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ratio ratio-16x9">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3847.123456789!2d28.123456!3d-15.123456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sBuffalo%20Park%20Recreation%20Centre!5e0!3m2!1sen!2szm!4v1234567890123"
                            style="border:0; border-radius: 15px;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// Include footer
include 'includes/footer.php';
?>
