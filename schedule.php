<?php
/**
 * Buffalo Marathon 2025 - Event Schedule
 * Production Ready - 2025-08-08 13:38:30 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Get schedule events
$schedule_events = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT * FROM schedules 
        WHERE is_active = 1 
        ORDER BY event_date, event_time, display_order
    ");
    $schedule_events = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Schedule fetch error: " . $e->getMessage());
}

$days_until_marathon = getDaysUntilMarathon();

$page_title = 'Event Schedule - Buffalo Marathon 2025';
$page_description = 'Complete event schedule for Buffalo Marathon 2025. Race times, activities, entertainment, and important event information.';

// Include header
include 'includes/header.php';
?>
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .schedule-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 4rem 0;
        }
        
        .timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, var(--army-green), var(--gold), var(--army-green));
            transform: translateX(-50%);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            margin-right: calc(50% + 2rem);
            text-align: right;
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            margin-left: calc(50% + 2rem);
            text-align: left;
        }
        
        .timeline-marker {
            position: absolute;
            left: 50%;
            top: 20px;
            width: 20px;
            height: 20px;
            background: var(--gold);
            border: 4px solid white;
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 10;
            box-shadow: 0 0 0 4px var(--army-green);
        }
        
        .timeline-marker.main-event {
            width: 30px;
            height: 30px;
            background: var(--army-green);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translateX(-50%) scale(1); }
            50% { transform: translateX(-50%) scale(1.1); }
        }
        
        .timeline-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--army-green);
            transition: transform 0.3s ease;
        }
        
        .timeline-content:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .timeline-time {
            background: var(--army-green);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .main-event .timeline-time {
            background: var(--gold);
            color: var(--army-green);
        }
        
        .schedule-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--army-green);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .schedule-card:hover {
            transform: translateX(5px);
        }
        
        .schedule-card.main-event {
            border-left-color: var(--gold);
            background: linear-gradient(135deg, #fff9e6, white);
        }
        
        .countdown-widget {
            background: linear-gradient(135deg, var(--gold), #FFA500);
            color: var(--army-green);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
        }
        
        .text-army-green { color: var(--army-green) !important; }
        .bg-army-green { background-color: var(--army-green) !important; }
        
        @media (max-width: 768px) {
            .timeline::before {
                left: 30px;
            }
            
            .timeline-item:nth-child(odd) .timeline-content,
            .timeline-item:nth-child(even) .timeline-content {
                margin-left: 60px;
                margin-right: 0;
                text-align: left;
            }
            
            .timeline-marker {
                left: 30px;
            }
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
                        <a class="nav-link active" href="/schedule.php">Schedule</a>
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
    <section class="schedule-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Event Schedule</h1>
            <p class="lead mb-4">
                Complete timeline for Buffalo Marathon 2025. Mark your calendars and plan your day 
                for an unforgettable marathon experience.
            </p>
            
            <!-- Event Info -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="countdown-widget">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="h2 fw-bold mb-1"><?php echo $days_until_marathon; ?></div>
                                <div class="fw-semibold">Days Until Marathon</div>
                            </div>
                            <div class="col-md-4">
                                <div class="h2 fw-bold mb-1">Oct 11</div>
                                <div class="fw-semibold">Saturday, 2025</div>
                            </div>
                            <div class="col-md-4">
                                <div class="h2 fw-bold mb-1">5:30 AM</div>
                                <div class="fw-semibold">Marathon Start Time</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Timeline -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Timeline View (Desktop) -->
                <div class="col-12 d-none d-lg-block">
                    <h2 class="text-center text-army-green mb-5">Marathon Day Timeline</h2>
                    
                    <div class="timeline">
                        <?php foreach ($schedule_events as $event): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?php echo $event['is_main_event'] ? 'main-event' : ''; ?>"></div>
                                <div class="timeline-content <?php echo $event['is_main_event'] ? 'main-event' : ''; ?>">
                                    <div class="timeline-time">
                                        <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                    </div>
                                    <h5 class="text-army-green fw-bold mb-2">
                                        <?php if ($event['is_main_event']): ?>
                                            <i class="fas fa-star me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($event['event_name']); ?>
                                    </h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php if ($event['location']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Card View (Mobile) -->
                <div class="col-12 d-lg-none">
                    <h2 class="text-center text-army-green mb-4">Marathon Day Schedule</h2>
                    
                    <?php foreach ($schedule_events as $event): ?>
                        <div class="schedule-card <?php echo $event['is_main_event'] ? 'main-event' : ''; ?>">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <div class="timeline-time">
                                        <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-army-green fw-bold mb-2">
                                        <?php if ($event['is_main_event']): ?>
                                            <i class="fas fa-star me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($event['event_name']); ?>
                                    </h6>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php if ($event['location']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Important Information -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center text-army-green mb-5">Important Information</h2>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-army-green text-white">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Race Pack Collection</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-army-green">Friday, October 10, 2025</h6>
                            <p class="mb-3">2:00 PM - 8:00 PM<br>Buffalo Park Recreation Centre</p>
                            
                            <h6 class="text-army-green">Saturday, October 11, 2025</h6>
                            <p class="mb-3">5:30 AM - 6:30 AM<br>Main Registration Tent</p>
                            
                            <div class="alert alert-warning">
                                <small><i class="fas fa-exclamation-triangle me-1"></i>
                                Bring your registration confirmation and valid ID</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-army-green text-white">
                            <h5 class="mb-0"><i class="fas fa-parking me-2"></i>Parking & Transport</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-army-green">Free Parking Available</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Buffalo Park Main Parking</li>
                                <li><i class="fas fa-check text-success me-2"></i>Adjacent School Grounds</li>
                                <li><i class="fas fa-check text-success me-2"></i>Street Parking (Limited)</li>
                            </ul>
                            
                            <h6 class="text-army-green mt-3">Public Transport</h6>
                            <p class="mb-0">Regular bus services available from city center. 
                            Special shuttle services arranged for early morning participants.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-army-green text-white">
                            <h5 class="mb-0"><i class="fas fa-utensils me-2"></i>Food & Refreshments</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-army-green">Included in Registration</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Free drink voucher</li>
                                <li><i class="fas fa-check text-success me-2"></i>Water stations during race</li>
                                <li><i class="fas fa-check text-success me-2"></i>Post-race refreshments</li>
                            </ul>
                            
                            <h6 class="text-army-green mt-3">Additional Options</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-star text-warning me-2"></i>Braai packs available for purchase</li>
                                <li><i class="fas fa-star text-warning me-2"></i>Food vendors on-site</li>
                                <li><i class="fas fa-star text-warning me-2"></i>VIP refreshment area</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-army-green text-white">
                            <h5 class="mb-0"><i class="fas fa-medal me-2"></i>Awards & Prizes</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-army-green">Prize Categories</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-trophy text-warning me-2"></i>Overall Male & Female Winners</li>
                                <li><i class="fas fa-trophy text-warning me-2"></i>Age Group Winners (5-year brackets)</li>
                                <li><i class="fas fa-trophy text-warning me-2"></i>Team Competition Winners</li>
                            </ul>
                            
                            <h6 class="text-army-green mt-3">All Finishers Receive</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-medal text-success me-2"></i>Official finisher's medal</li>
                                <li><i class="fas fa-certificate text-success me-2"></i>Digital completion certificate</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Preparation Tips -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center text-army-green mb-5">Marathon Day Preparation</h2>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-bed fa-4x text-army-green mb-3"></i>
                        <h5 class="fw-bold">Night Before</h5>
                        <ul class="list-unstyled text-muted">
                            <li>Get a good night's sleep (7-8 hours)</li>
                            <li>Eat a carb-rich dinner</li>
                            <li>Prepare your race kit</li>
                            <li>Set multiple alarms</li>
                            <li>Hydrate well</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-sun fa-4x text-army-green mb-3"></i>
                        <h5 class="fw-bold">Race Morning</h5>
                        <ul class="list-unstyled text-muted">
                            <li>Wake up 2-3 hours before race</li>
                            <li>Eat familiar breakfast</li>
                            <li>Arrive at venue by 6:00 AM</li>
                            <li>Warm up properly</li>
                            <li>Use restroom facilities</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-backpack fa-4x text-army-green mb-3"></i>
                        <h5 class="fw-bold">What to Bring</h5>
                        <ul class="list-unstyled text-muted">
                            <li>Race number bib</li>
                            <li>Comfortable running shoes</li>
                            <li>Weather-appropriate clothing</li>
                            <li>Personal water bottle</li>
                            <li>Identification & confirmation</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <?php if (isRegistrationOpen()): ?>
        <section class="py-5 bg-army-green text-white">
            <div class="container text-center">
                <h2 class="display-6 fw-bold mb-3">Ready for Race Day?</h2>
                <p class="lead mb-4">
                    Join us for an unforgettable marathon experience. Register now and be part of 
                    Buffalo Marathon 2025!
                </p>
                
                <?php if (isLoggedIn()): ?>
                    <a href="/register-marathon.php" class="btn btn-light btn-lg">
                        <i class="fas fa-running me-2"></i>Register for Marathon
                    </a>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Sign Up & Register
                    </a>
                <?php endif; ?>
                
                <div class="mt-3">
                    <small class="opacity-75">
                        Registration closes on <?php echo formatDate(REGISTRATION_DEADLINE, 'F j, Y'); ?>
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?>