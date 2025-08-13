<?php
/**
 * Buffalo Marathon 2025 - Race Categories
 * Production Ready - 2025-08-08 13:19:16 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Get categories with registration counts
$categories = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT c.*, 
               COUNT(r.id) as registration_count
        FROM categories c
        LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status != 'cancelled'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY FIELD(c.name, 'Full Marathon', 'Half Marathon', 'Power Challenge', 'Family Fun Run', 'VIP Run', 'Kid Run')
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Categories fetch error: " . $e->getMessage());
}

$registration_open = isRegistrationOpen();
$early_bird_active = isEarlyBirdActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Categories - Buffalo Marathon 2025</title>
    <meta name="description" content="Choose from 6 exciting race categories at Buffalo Marathon 2025. Full Marathon, Half Marathon, 10K, 5K, VIP Run, and Kids Race.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .categories-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 4rem 0;
        }
        
        .category-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: var(--army-green);
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .category-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(45deg, var(--gold), #FFA500);
        }
        
        .category-price {
            background: var(--gold);
            color: var(--army-green);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 900;
            font-size: 1.2rem;
            display: inline-block;
            margin-top: 1rem;
        }
        
        .category-body {
            padding: 2rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            color: var(--army-green);
            width: 20px;
        }
        
        .availability-bar {
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            height: 8px;
        }
        
        .availability-fill {
            background: linear-gradient(45deg, var(--army-green), var(--army-green-dark));
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .btn-army-green {
            background-color: var(--army-green);
            border-color: var(--army-green);
            color: white;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-army-green:hover {
            background-color: var(--army-green-dark);
            border-color: var(--army-green-dark);
            color: white;
            transform: translateY(-2px);
        }
        
        .comparison-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .comparison-table th {
            background: var(--army-green);
            color: white;
            font-weight: 600;
            border: none;
        }
        
        .comparison-table td {
            border-color: #f1f3f4;
        }
        
        .text-army-green {
            color: var(--army-green) !important;
        }
        
        .bg-army-green {
            background-color: var(--army-green) !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-army-green">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon 2025
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/schedule.php">Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/info.php">Event Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/faq.php">FAQ</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_email']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light ms-2 px-3" href="/register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="categories-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Choose Your Challenge</h1>
            <p class="lead mb-4">
                Six exciting race categories designed for every fitness level and age group. 
                From competitive marathoners to families looking for fun, we have something for everyone.
            </p>
            
            <!-- Status Info -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3">
                                <div class="h3 fw-bold text-warning mb-1"><?php echo getDaysUntilMarathon(); ?></div>
                                <div>Days Until Marathon</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3">
                                <div class="h3 fw-bold text-warning mb-1"><?php echo getDaysUntilDeadline(); ?></div>
                                <div>Days to Register</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3">
                                <div class="h3 fw-bold text-warning mb-1"><?php echo count($categories); ?></div>
                                <div>Race Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Grid -->
    <section class="py-5">
        <div class="container">
            <?php if ($early_bird_active): ?>
                <div class="alert alert-warning text-center mb-5">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Early Bird Special!</strong> Register before August 31st, 2025 for current pricing. 
                    Only <?php echo getDaysUntilEarlyBird(); ?> days remaining!
                </div>
            <?php endif; ?>
            
            <div class="row g-4 mb-5">
                <?php foreach ($categories as $category): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="category-card">
                            <div class="category-header">
                                <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($category['name']); ?></h4>
                                <p class="mb-0 fs-5 opacity-75"><?php echo htmlspecialchars($category['distance']); ?></p>
                                <div class="category-price"><?php echo formatCurrency($category['price']); ?></div>
                            </div>
                            
                            <div class="category-body">
                                <p class="text-muted mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
                                
                                <!-- Features -->
                                <ul class="feature-list mb-4">
                                    <li><i class="fas fa-check me-2"></i>Premium branded t-shirt</li>
                                    <li><i class="fas fa-check me-2"></i>Finisher's medal</li>
                                    <li><i class="fas fa-check me-2"></i>Official race bib with timing chip</li>
                                    <li><i class="fas fa-check me-2"></i>Free refreshment voucher</li>
                                    <li><i class="fas fa-check me-2"></i>Access to all event activities</li>
                                    <?php if ($category['name'] === 'VIP Run'): ?>
                                        <li><i class="fas fa-star me-2"></i>VIP tent access</li>
                                        <li><i class="fas fa-star me-2"></i>Premium refreshments</li>
                                    <?php endif; ?>
                                </ul>
                                
                                <!-- Age Requirement -->
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        Age Requirement: <?php echo $category['min_age']; ?>-<?php echo $category['max_age']; ?> years
                                    </small>
                                </div>
                                
                                <!-- Availability -->
                                <?php if ($category['max_participants'] > 0): ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted">Availability</small>
                                            <small class="text-muted">
                                                <?php echo $category['registration_count']; ?>/<?php echo $category['max_participants']; ?>
                                            </small>
                                        </div>
                                        <div class="availability-bar">
                                            <div class="availability-fill" 
                                                 style="width: <?php echo min(($category['registration_count'] / $category['max_participants']) * 100, 100); ?>%"></div>
                                        </div>
                                        <?php if ($category['registration_count'] >= $category['max_participants']): ?>
                                            <small class="text-danger mt-1 d-block"><i class="fas fa-exclamation-triangle me-1"></i>Fully Booked</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Registration Button -->
                                <?php if ($registration_open && ($category['max_participants'] == 0 || $category['registration_count'] < $category['max_participants'])): ?>
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
                                <?php elseif (!$registration_open): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-times-circle me-2"></i>Registration Closed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-times-circle me-2"></i>Fully Booked
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Comparison Table -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold text-army-green">Category Comparison</h2>
                <p class="lead">Compare all race categories at a glance</p>
            </div>
            
            <div class="comparison-table table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Distance</th>
                            <th>Price</th>
                            <th>Age Requirement</th>
                            <th>Max Participants</th>
                            <th>Registered</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($category['distance']); ?></td>
                                <td><strong><?php echo formatCurrency($category['price']); ?></strong></td>
                                <td><?php echo $category['min_age']; ?>-<?php echo $category['max_age']; ?> years</td>
                                <td>
                                    <?php echo $category['max_participants'] > 0 ? $category['max_participants'] : 'Unlimited'; ?>
                                </td>
                                <td>
                                    <span class="badge bg-army-green"><?php echo $category['registration_count']; ?></span>
                                </td>
                                <td>
                                    <?php if (!$registration_open): ?>
                                        <span class="badge bg-secondary">Closed</span>
                                    <?php elseif ($category['max_participants'] > 0 && $category['registration_count'] >= $category['max_participants']): ?>
                                        <span class="badge bg-danger">Full</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- What's Included -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold text-army-green">What's Included</h2>
                <p class="lead">Every registration includes these amazing perks</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 text-center">
                    <i class="fas fa-tshirt fa-4x text-army-green mb-3"></i>
                    <h5 class="fw-bold">Premium T-Shirt</h5>
                    <p class="text-muted">High-quality, moisture-wicking branded marathon t-shirt in your selected size</p>
                </div>
                <div class="col-lg-3 col-md-6 text-center">
                    <i class="fas fa-medal fa-4x text-army-green mb-3"></i>
                    <h5 class="fw-bold">Finisher's Medal</h5>
                    <p class="text-muted">Beautiful commemorative medal for all participants who complete their race</p>
                </div>
                <div class="col-lg-3 col-md-6 text-center">
                    <i class="fas fa-id-card fa-4x text-army-green mb-3"></i>
                    <h5 class="fw-bold">Race Number & Chip</h5>
                    <p class="text-muted">Official race bib with integrated timing chip for accurate results tracking</p>
                </div>
                <div class="col-lg-3 col-md-6 text-center">
                    <i class="fas fa-glass-water fa-4x text-army-green mb-3"></i>
                    <h5 class="fw-bold">Refreshments</h5>
                    <p class="text-muted">Complimentary drink voucher and access to post-race refreshment stations</p>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-dumbbell text-success me-3 fa-3x"></i>
                            <div class="text-start">
                                <h6 class="mb-1 fw-bold">Pre & Post-Race Aerobics</h6>
                                <p class="mb-0 text-muted">Professional fitness sessions to warm up and cool down</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-music text-success me-3 fa-3x"></i>
                            <div class="text-start">
                                <h6 class="mb-1 fw-bold">Live Entertainment</h6>
                                <p class="mb-0 text-muted">Zambia Army Pop Band and special guest performances</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-utensils text-success me-3 fa-3x"></i>
                            <div class="text-start">
                                <h6 class="mb-1 fw-bold">Food & Braai Zones</h6>
                                <p class="mb-0 text-muted">Delicious local cuisine and traditional braai packs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-child text-success me-3 fa-3x"></i>
                            <div class="text-start">
                                <h6 class="mb-1 fw-bold">Family Activities</h6>
                                <p class="mb-0 text-muted">Kids zone, chill lounge, and family-friendly activities</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <?php if ($registration_open): ?>
        <section class="py-5 bg-army-green text-white">
            <div class="container text-center">
                <h2 class="display-6 fw-bold mb-3">Ready to Join the Challenge?</h2>
                <p class="lead mb-4">
                    Don't miss out on Buffalo Marathon 2025. Register now and secure your spot 
                    in Zambia's most exciting running event.
                </p>
                
                <div class="d-flex justify-content-center gap-3">
                    <?php if (isLoggedIn()): ?>
                        <a href="/register-marathon.php" class="btn btn-light btn-lg">
                            <i class="fas fa-running me-2"></i>Register for Marathon
                        </a>
                        <a href="/dashboard.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account & Register
                        </a>
                        <a href="/login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Register
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <small class="opacity-75">
                        Registration closes on <?php echo formatDate(REGISTRATION_DEADLINE, 'F j, Y'); ?> at 11:59 PM
                    </small>
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?>