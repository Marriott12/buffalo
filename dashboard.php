<?php
/**
 * Buffalo Marathon 2025 - User Dashboard
 * Production Ready - 2025-08-08 12:37:31 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Require login
requireLogin();

$user = getCurrentUser();
if (!$user) {
    setFlashMessage('error', 'User session expired. Please login again.');
    redirectTo('/login.php');
}

// Get user's marathon registration
$registration = null;
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, c.name as category_name, c.distance, c.price 
        FROM registrations r 
        JOIN categories c ON r.category_id = c.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $registration = $stmt->fetch();
} catch (Exception $e) {
    error_log("Dashboard registration fetch error: " . $e->getMessage());
}

// Get recent activity
$activities = [];
try {
    $stmt = $db->prepare("
        SELECT action, description, created_at 
        FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Dashboard activity fetch error: " . $e->getMessage());
}

// Get announcements for registered users
$announcements = [];
try {
    $stmt = $db->query("
        SELECT title, content, type, created_at 
        FROM announcements 
        WHERE is_active = 1 AND target_audience IN ('all', 'registered') 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Dashboard announcements fetch error: " . $e->getMessage());
}

$days_until_marathon = getDaysUntilMarathon();
$days_until_deadline = getDaysUntilDeadline();
$registration_open = isRegistrationOpen();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Buffalo Marathon 2025</title>
    <meta name="description" content="Your Buffalo Marathon 2025 dashboard - manage your registration, view updates, and track your progress.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .bg-army-green { background-color: var(--army-green) !important; }
        .text-army-green { color: var(--army-green) !important; }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 2rem 0;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--army-green);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--army-green);
        }
        
        .registration-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 2px solid var(--army-green);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-confirmed {
            background: #28a745;
            color: white;
        }
        
        .status-pending {
            background: #ffc107;
            color: #212529;
        }
        
        .status-registered {
            background: #17a2b8;
            color: white;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid var(--army-green);
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .countdown-widget {
            background: linear-gradient(135deg, var(--gold), #FFA500);
            color: var(--army-green);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
        }
        
        .countdown-number {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
        }
        
        .btn-army-green {
            background-color: var(--army-green);
            border-color: var(--army-green);
            color: white;
        }
        
        .btn-army-green:hover {
            background-color: var(--army-green-dark);
            border-color: var(--army-green-dark);
            color: white;
        }
        
        .quick-action {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action:hover {
            border-color: var(--army-green);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            color: inherit;
            text-decoration: none;
        }
    </style>
</head>
<body class="bg-light">
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
                        <a class="nav-link active" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/schedule.php">Schedule</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile.php">Profile</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/admin/">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php
    $flash_messages = getAllFlashMessages();
    foreach ($flash_messages as $flash):
        $type = $flash['type'];
        $message = $flash['message'];
        $alert_class = match($type) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
    ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show m-0">
            <div class="container">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-2">
                        Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!
                    </h1>
                    <p class="lead mb-0">
                        Manage your Buffalo Marathon 2025 registration and stay updated with the latest information.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="countdown-widget">
                        <div class="countdown-number"><?php echo $days_until_marathon; ?></div>
                        <div class="fw-bold">Days Until Marathon</div>
                        <small>October 11, 2025</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Dashboard Content -->
    <section class="py-5">
        <div class="container">
            <!-- Stats Row -->
            <div class="row g-4 mb-5">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stats-number"><?php echo $days_until_marathon; ?></div>
                                <div class="text-muted">Days to Marathon</div>
                            </div>
                            <i class="fas fa-calendar-alt fa-2x text-army-green"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stats-number"><?php echo $days_until_deadline; ?></div>
                                <div class="text-muted">Days to Register</div>
                            </div>
                            <i class="fas fa-clock fa-2x text-army-green"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stats-number"><?php echo $registration ? '1' : '0'; ?></div>
                                <div class="text-muted">Your Registrations</div>
                            </div>
                            <i class="fas fa-running fa-2x text-army-green"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stats-number"><?php echo count($activities); ?></div>
                                <div class="text-muted">Recent Activities</div>
                            </div>
                            <i class="fas fa-chart-line fa-2x text-army-green"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Registration Status -->
                <div class="col-lg-8">
                    <?php if ($registration): ?>
                        <div class="registration-card mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4 class="text-army-green mb-0">
                                    <i class="fas fa-medal me-2"></i>Your Marathon Registration
                                </h4>
                                <span class="status-badge status-<?php echo $registration['payment_status']; ?>">
                                    <?php echo ucfirst($registration['payment_status']); ?>
                                </span>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-army-green">Race Category</h6>
                                    <p class="mb-2"><?php echo htmlspecialchars($registration['category_name']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($registration['distance']); ?></small>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-army-green">Registration Number</h6>
                                    <p class="mb-2 fw-bold"><?php echo htmlspecialchars($registration['registration_number']); ?></p>
                                    <small class="text-muted">Keep this number safe</small>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-army-green">T-Shirt Size</h6>
                                    <p class="mb-2"><?php echo htmlspecialchars($registration['t_shirt_size']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-army-green">Registration Fee</h6>
                                    <p class="mb-2 fw-bold"><?php echo formatCurrency($registration['payment_amount']); ?></p>
                                    <small class="text-muted">Payment Method: <?php echo ucfirst(str_replace('_', ' ', $registration['payment_method'])); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($registration['payment_status'] === 'pending'): ?>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Payment Pending:</strong> Please complete your payment to confirm your registration.
                                    <a href="/payment.php?registration=<?php echo $registration['id']; ?>" class="btn btn-warning btn-sm ms-2">
                                        Complete Payment
                                    </a>
                                </div>
                            <?php elseif ($registration['payment_status'] === 'confirmed'): ?>
                                <div class="alert alert-success mt-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Registration Confirmed!</strong> You're all set for Buffalo Marathon 2025.
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="/registration-details.php" class="btn btn-army-green me-2">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <a href="/download-confirmation.php" class="btn btn-outline-army-green">
                                    <i class="fas fa-download me-1"></i>Download Confirmation
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="registration-card mb-4">
                            <div class="text-center py-5">
                                <i class="fas fa-running fa-4x text-army-green mb-3"></i>
                                <h4 class="text-army-green mb-3">Ready to Join the Marathon?</h4>
                                <p class="text-muted mb-4">
                                    You haven't registered for Buffalo Marathon 2025 yet. 
                                    Choose your race category and secure your spot today!
                                </p>
                                
                                <?php if ($registration_open): ?>
                                    <a href="/register-marathon.php" class="btn btn-army-green btn-lg">
                                        <i class="fas fa-plus me-2"></i>Register for Marathon
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-times-circle me-2"></i>
                                        Registration is currently closed.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="mb-4">
                        <h5 class="text-army-green mb-3">Quick Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="/profile.php" class="quick-action d-block">
                                    <i class="fas fa-user-edit fa-2x text-army-green mb-2"></i>
                                    <h6>Edit Profile</h6>
                                    <small class="text-muted">Update your information</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/schedule.php" class="quick-action d-block">
                                    <i class="fas fa-calendar-alt fa-2x text-army-green mb-2"></i>
                                    <h6>Event Schedule</h6>
                                    <small class="text-muted">View event timeline</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/training.php" class="quick-action d-block">
                                    <i class="fas fa-dumbbell fa-2x text-army-green mb-2"></i>
                                    <h6>Training Tips</h6>
                                    <small class="text-muted">Prepare for the race</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="/contact.php" class="quick-action d-block">
                                    <i class="fas fa-headset fa-2x text-army-green mb-2"></i>
                                    <h6>Get Support</h6>
                                    <small class="text-muted">Contact our team</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Recent Activity -->
                    <div class="card mb-4">
                        <div class="card-header bg-army-green text-white">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($activities)): ?>
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($activity['action']); ?></div>
                                        <?php if ($activity['description']): ?>
                                            <div class="text-muted small"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        <?php endif; ?>
                                        <div class="text-muted small mt-1">
                                            <i class="fas fa-clock me-1"></i><?php echo formatDateTime($activity['created_at']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="fas fa-info-circle me-2"></i>No recent activity
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Announcements -->
                    <?php if (!empty($announcements)): ?>
                        <div class="card">
                            <div class="card-header bg-army-green text-white">
                                <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Latest Updates</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="mb-3 pb-3 <?php echo $announcement !== end($announcements) ? 'border-bottom' : ''; ?>">
                                        <h6 class="text-army-green"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i><?php echo formatDateTime($announcement['created_at']); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                                <a href="/announcements.php" class="btn btn-outline-army-green btn-sm w-100">
                                    View All Updates
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-dismiss flash messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>