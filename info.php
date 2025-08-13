<?php
/**
 * Buffalo Marathon 2025 - Event Information
 * Production Ready - 2025-08-08 13:53:48 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

$days_until_marathon = getDaysUntilMarathon();
$days_until_deadline = getDaysUntilDeadline();
$registration_open = isRegistrationOpen();

$page_title = 'Event Information - Buffalo Marathon 2025';
$page_description = 'Complete event information for Buffalo Marathon 2025. Location, timing, what\'s included, and everything you need to know.';

// Include header
include 'includes/header.php';
?>
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .info-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 4rem 0;
        }
        
        .info-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
        }
        
        .section-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .timeline-item {
            border-left: 3px solid var(--army-green);
            padding-left: 2rem;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 13px;
            height: 13px;
            background: var(--gold);
            border-radius: 50%;
        }
        
        .text-army-green { color: var(--army-green) !important; }
        
        .highlight-box {
            background: rgba(75, 83, 32, 0.1);
            border-left: 4px solid var(--army-green);
            padding: 1.5rem;
            border-radius: 0 10px 10px 0;
            margin: 1.5rem 0;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .feature-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .feature-item i {
            color: var(--army-green);
            font-size: 2.5rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link" href="/categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/schedule.php">Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/info.php">Event Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/faq.php">FAQ</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(getCurrentUserEmail() ?: 'User'); ?>
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
    <section class="info-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Event Information</h1>
            <p class="lead mb-4">
                Everything you need to know about Buffalo Marathon 2025. 
                Complete details to help you prepare for an amazing experience.
            </p>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3">
                                <div class="h3 fw-bold text-warning mb-1"><?php echo $days_until_marathon; ?></div>
                                <div>Days Until Marathon</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3">
                                <div class="h3 fw-bold text-warning mb-1">Oct 11</div>
                                <div>Saturday, 2025</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3">
                                <div class="h3 fw-bold text-warning mb-1">7:00 AM</div>
                                <div>Race Start Time</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Overview -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="info-card text-center">
                        <div class="section-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="text-army-green mb-3">Location</h4>
                        <h5><?php echo EVENT_VENUE; ?></h5>
                        <p class="text-muted"><?php echo EVENT_ADDRESS; ?><br><?php echo EVENT_CITY; ?></p>
                        <a href="#location-details" class="btn btn-outline-army-green">View Details</a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="info-card text-center">
                        <div class="section-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4 class="text-army-green mb-3">Date & Time</h4>
                        <h5><?php echo formatDate(MARATHON_DATE, 'l, F j, Y'); ?></h5>
                        <p class="text-muted">Race starts at <?php echo date('g:i A', strtotime(MARATHON_TIME)); ?><br>
                        Registration from 5:30 AM</p>
                        <a href="/schedule.php" class="btn btn-outline-army-green">Full Schedule</a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="info-card text-center">
                        <div class="section-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <h4 class="text-army-green mb-3">Categories</h4>
                        <h5>6 Race Categories</h5>
                        <p class="text-muted">From 1K Kid Run to Full Marathon<br>
                        Something for everyone</p>
                        <a href="/categories.php" class="btn btn-outline-army-green">View Categories</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- What's Included -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold text-army-green">What's Included in Your Registration</h2>
                <p class="lead text-muted">Every participant receives amazing value and unforgettable experiences</p>
            </div>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-tshirt"></i>
                    <h5 class="fw-bold">Premium T-Shirt</h5>
                    <p class="text-muted mb-0">High-quality, moisture-wicking branded marathon t-shirt in your selected size</p>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-medal"></i>
                    <h5 class="fw-bold">Finisher's Medal</h5>
                    <p class="text-muted mb-0">Beautiful commemorative medal for all participants who complete their race</p>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-id-card"></i>
                    <h5 class="fw-bold">Race Number & Chip</h5>
                    <p class="text-muted mb-0">Official race bib with integrated timing chip for accurate results tracking</p>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-glass-water"></i>
                    <h5 class="fw-bold">Refreshments</h5>
                    <p class="text-muted mb-0">Complimentary drink voucher and access to post-race refreshment stations</p>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-dumbbell"></i>
                    <h5 class="fw-bold">Pre & Post-Race Aerobics</h5>
                    <p class="text-muted mb-0">Professional fitness sessions to warm up and cool down properly</p>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-music"></i>
                    <h5 class="fw-bold">Live Entertainment</h5>
                    <p class="text-muted mb-0">Zambia Army Pop Band performances and special guest artists</p>
                </div>
                
                <div class="feature-item">