<?php
/**
 * Buffalo Marathon 2025 - Homepage
 * Production Ready - Updated: 2025-08-12
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

<style>
/* Homepage specific styles */
.hero-section {
    position: relative;
    height: 100vh;
    min-height: 600px;
    background: linear-gradient(135deg, #4B5320 0%, #8F9779 100%);
    display: flex;
    align-items: center;
    overflow: hidden;
}

.hero-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 1;
    opacity: 0.3;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        135deg,
        rgba(75, 83, 32, 0.8) 0%,
        rgba(143, 151, 121, 0.6) 50%,
        rgba(75, 83, 32, 0.9) 100%
    );
    z-index: 2;
}

.hero-content {
    position: relative;
    z-index: 3;
    color: white;
    text-align: center;
}

.countdown-container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin: 30px 0;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.countdown-item {
    text-align: center;
    margin: 0 10px;
}

.countdown-number {
    font-size: 3rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.countdown-label {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.cta-buttons .btn {
    margin: 10px;
    padding: 15px 30px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-primary-custom {
    background: #fff;
    color: #4B5320;
    border: 2px solid #fff;
}

.btn-primary-custom:hover {
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
    transform: translateY(-2px);
}

.btn-outline-light-custom {
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.7);
}

.btn-outline-light-custom:hover {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid #fff;
    transform: translateY(-2px);
}

.stats-section {
    background: #f8f9fa;
    padding: 80px 0;
}

.stat-item {
    text-align: center;
    padding: 30px 20px;
    border-radius: 15px;
    background: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin: 15px 0;
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: #4B5320;
    margin-bottom: 10px;
}

.stat-label {
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.categories-section {
    padding: 80px 0;
    background: white;
}

.category-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.15);
}

.category-header {
    background: linear-gradient(135deg, #4B5320, #8F9779);
    color: white;
    padding: 30px 20px;
    text-align: center;
}

.category-distance {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.category-name {
    font-size: 1.2rem;
    margin-bottom: 0;
}

.category-body {
    padding: 30px 20px;
}

.category-price {
    font-size: 2rem;
    font-weight: 700;
    color: #4B5320;
    margin-bottom: 20px;
}

.category-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}

.category-features li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.category-features li:last-child {
    border-bottom: none;
}

.progress-container {
    background: #f1f1f1;
    border-radius: 10px;
    height: 10px;
    margin: 15px 0;
    overflow: hidden;
}

.progress-bar-custom {
    background: linear-gradient(90deg, #4B5320, #8F9779);
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease;
}

@media (max-width: 768px) {
    .hero-section {
        height: 80vh;
        min-height: 500px;
    }
    
    .countdown-number {
        font-size: 2rem;
    }
    
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .category-card {
        margin-bottom: 30px;
    }
}
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="display-3 fw-bold mb-4">
                    Buffalo Marathon 2025
                </h1>
                <p class="lead fs-4 mb-4">
                    Join us at Buffalo Park Recreation Centre<br>
                    <strong>October 11, 2025</strong> | Multiple Categories | Amazing Prizes
                </p>
                
                <?php if ($marathon_status === 'upcoming'): ?>
                    <div class="countdown-container">
                        <h3 class="h4 mb-4">Event Countdown</h3>
                        <div class="row" id="countdown">
                            <div class="col-3 countdown-item">
                                <div class="countdown-number" id="days"><?php echo $days_until_marathon; ?></div>
                                <div class="countdown-label">Days</div>
                            </div>
                            <div class="col-3 countdown-item">
                                <div class="countdown-number" id="hours">00</div>
                                <div class="countdown-label">Hours</div>
                            </div>
                            <div class="col-3 countdown-item">
                                <div class="countdown-number" id="minutes">00</div>
                                <div class="countdown-label">Minutes</div>
                            </div>
                            <div class="col-3 countdown-item">
                                <div class="countdown-number" id="seconds">00</div>
                                <div class="countdown-label">Seconds</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="cta-buttons">
                    <?php if ($registration_open): ?>
                        <a href="register-marathon.php" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-running me-2"></i>Register Now
                        </a>
                        <?php if ($early_bird_active): ?>
                            <div class="alert alert-warning d-inline-block ms-3">
                                <i class="fas fa-clock me-2"></i>
                                Early Bird pricing ends in <?php echo $days_until_early_bird; ?> days!
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Registration <?php echo $marathon_status === 'completed' ? 'has ended' : 'opens soon'; ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="categories.php" class="btn btn-outline-light-custom btn-lg">
                        <i class="fas fa-list me-2"></i>View Categories
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Current Statistics -->
<section class="stats-section">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="display-5 fw-bold mb-3">Live Event Statistics</h2>
                <p class="lead">Real-time registration numbers and event updates</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($current_registrations); ?></div>
                    <div class="stat-label">Total Registrations</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($confirmed_payments); ?></div>
                    <div class="stat-label">Confirmed Payments</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $days_until_marathon; ?></div>
                    <div class="stat-label">Days Until Marathon</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($categories); ?></div>
                    <div class="stat-label">Race Categories</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Race Categories -->
<section class="categories-section">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="display-5 fw-bold mb-3">Race Categories</h2>
                <p class="lead">Choose your challenge and join the adventure</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($categories as $index => $category): 
                $registration_percentage = 0;
                if ($category['max_participants'] > 0) {
                    // Get category-specific registration count
                    try {
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM registrations WHERE category_id = ? AND status != 'cancelled'");
                        $stmt->execute([$category['id']]);
                        $category_registrations = $stmt->fetch()['count'];
                        $registration_percentage = ($category_registrations / $category['max_participants']) * 100;
                    } catch (Exception $e) {
                        $category_registrations = 0;
                    }
                }
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card category-card">
                        <div class="category-header">
                            <div class="category-distance"><?php echo htmlspecialchars($category['distance']); ?></div>
                            <h5 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h5>
                        </div>
                        <div class="category-body">
                            <div class="category-price">K<?php echo number_format($category['price']); ?></div>
                            
                            <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                            
                            <?php if ($category['max_participants'] > 0): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Spaces Available</small>
                                        <small><?php echo $category_registrations ?? 0; ?> / <?php echo $category['max_participants']; ?></small>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar-custom" style="width: <?php echo min($registration_percentage, 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($registration_open): ?>
                                <a href="register-marathon.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-running me-2"></i>Register for <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    Registration Closed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row">
            <div class="col-lg-12 text-center">
                <a href="categories.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-list me-2"></i>View All Categories & Details
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Latest Announcements -->
<?php if (!empty($announcements)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="display-6 fw-bold mb-3">Latest Updates</h2>
                <p class="lead">Stay informed with the latest marathon news and announcements</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($announcements as $announcement): ?>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php
                                $badge_class = match($announcement['type']) {
                                    'urgent' => 'bg-danger',
                                    'important' => 'bg-warning text-dark',
                                    'info' => 'bg-info',
                                    default => 'bg-primary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?> me-2">
                                    <?php echo ucfirst($announcement['type']); ?>
                                </span>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                </small>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 150))); ?><?php echo strlen($announcement['content']) > 150 ? '...' : ''; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Event Information -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h3 class="h2 fw-bold mb-4">Event Details</h3>
                <ul class="list-unstyled fs-5">
                    <li class="mb-3"><i class="fas fa-calendar-alt text-primary me-3"></i><strong>Date:</strong> October 11, 2025</li>
                    <li class="mb-3"><i class="fas fa-clock text-primary me-3"></i><strong>Start Time:</strong> 06:00 AM</li>
                    <li class="mb-3"><i class="fas fa-map-marker-alt text-primary me-3"></i><strong>Location:</strong> Buffalo Park Recreation Centre</li>
                    <li class="mb-3"><i class="fas fa-trophy text-primary me-3"></i><strong>Categories:</strong> <?php echo count($categories); ?> Different Distances</li>
                    <li class="mb-3"><i class="fas fa-users text-primary me-3"></i><strong>Expected Participants:</strong> 2000+</li>
                </ul>
                
                <div class="mt-4">
                    <a href="info.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-info-circle me-2"></i>Detailed Event Info
                    </a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-envelope me-2"></i>Contact Us
                    </a>
                </div>
            </div>
            
            <div class="col-lg-6">
                <h3 class="h2 fw-bold mb-4">Quick Contact</h3>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-phone text-primary me-2"></i>Call Us</h6>
                                <p class="card-text">
                                    <a href="tel:+260972545658" class="text-decoration-none">+260 972 545 658</a><br>
                                    <a href="tel:+260770809062" class="text-decoration-none">+260 770 809 062</a><br>
                                    <a href="tel:+260771470868" class="text-decoration-none">+260 771 470 868</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-envelope text-primary me-2"></i>Email Us</h6>
                                <p class="card-text">
                                    <a href="mailto:info@buffalo-marathon.com" class="text-decoration-none">info@buffalo-marathon.com</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Countdown Timer for Marathon Date
<?php if ($marathon_status === 'upcoming'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const marathonDate = new Date('2025-10-11T06:00:00').getTime();
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = marathonDate - now;
        
        if (distance > 0) {
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        }
    }
    
    // Update immediately and then every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
});
<?php endif; ?>

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add animation to stats when they come into view
function animateStats() {
    const stats = document.querySelectorAll('.stat-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const finalValue = parseInt(target.textContent.replace(/,/g, ''));
                let current = 0;
                const increment = finalValue / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= finalValue) {
                        target.textContent = finalValue.toLocaleString();
                        clearInterval(timer);
                    } else {
                        target.textContent = Math.floor(current).toLocaleString();
                    }
                }, 40);
                observer.unobserve(target);
            }
        });
    });
    
    stats.forEach(stat => observer.observe(stat));
}

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', animateStats);
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
