<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buffalo Marathon 2025 - Join us on October 11, 2025 at Buffalo Park Recreation Centre for an unforgettable running experience with live entertainment, food, and activities for the whole family.">
    <meta name="keywords" content="Buffalo Marathon, running, marathon, half marathon, 10K, 5K, family fun run, Zambia, fitness, race">
    <meta name="author" content="Buffalo Marathon Team">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Buffalo Marathon 2025">
    <meta property="og:description" content="Join Buffalo Marathon 2025 on October 11 at Buffalo Park Recreation Centre. Multiple race categories, live entertainment, food zones, and activities for all ages!">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/buffalo-marathon-og.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo SITE_URL; ?>">
    <meta property="twitter:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Buffalo Marathon 2025">
    <meta property="twitter:description" content="Join Buffalo Marathon 2025 on October 11 at Buffalo Park Recreation Centre. Multiple race categories, live entertainment, food zones, and activities for all ages!">
    <meta property="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/buffalo-marathon-og.jpg">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Buffalo Marathon 2025</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo str_contains($_SERVER['REQUEST_URI'], '/admin/') ? '../' : ''; ?>assets/css/style.css?v=1.0.0" rel="stylesheet">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
    <!-- Skip to main content for accessibility -->
    <a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-army-green fixed-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo str_contains($_SERVER['REQUEST_URI'], '/admin/') ? '../' : ''; ?>index.php">
                <i class="fas fa-running me-2"></i>
                <span>Buffalo Marathon 2025</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (!str_contains($_SERVER['REQUEST_URI'], '/admin/')): ?>
                <!-- Regular site navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" 
                           href="index.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'page' : 'false'; ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>" 
                           href="categories.php">
                            <i class="fas fa-list me-1"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : ''; ?>" 
                           href="schedule.php">
                            <i class="fas fa-calendar-alt me-1"></i>Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'info.php' ? 'active' : ''; ?>" 
                           href="info.php">
                            <i class="fas fa-info-circle me-1"></i>Event Info
                        </a>
                    </li>
                    
                    <!-- User-only links -->
                    <?php if (isLoggedIn()): ?>
                        <?php if (isRegistrationOpen()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'register-marathon.php' ? 'active' : ''; ?>" 
                               href="register-marathon.php">
                                <i class="fas fa-running me-1"></i>Register
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <!-- Admin link for admins only -->
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">
                                    <i class="fas fa-cog me-1"></i>Admin
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- User dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a></li>
                                <li><a class="dropdown-item" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>
                                
                                <!-- Check if user has registration -->
                                <?php
                                $user_registration = null;
                                if (isLoggedIn()) {
                                    $stmt = getDB()->prepare("SELECT id FROM registrations WHERE user_id = ? AND payment_status != 'cancelled' LIMIT 1");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $user_registration = $stmt->fetch();
                                }
                                ?>
                                
                                <?php if ($user_registration): ?>
                                    <li><a class="dropdown-item" href="my-registration.php">
                                        <i class="fas fa-running me-2"></i>My Registration
                                    </a></li>
                                <?php elseif (isRegistrationOpen()): ?>
                                    <li><a class="dropdown-item text-success" href="register-marathon.php">
                                        <i class="fas fa-plus-circle me-2"></i>Register for Marathon
                                    </a></li>
                                <?php endif; ?>
                                
                                <!-- Admin menu for admins -->
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header text-army-green">
                                        <i class="fas fa-cog me-2"></i>Administration
                                    </h6></li>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="admin/participants.php">
                                        <i class="fas fa-users me-2"></i>Manage Participants
                                    </a></li>
                                    <li><a class="dropdown-item" href="admin/announcements.php">
                                        <i class="fas fa-bullhorn me-2"></i>Announcements
                                    </a></li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest user links -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light ms-2 px-3 rounded-pill" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <?php else: ?>
                <!-- Admin navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="participants.php">
                            <i class="fas fa-users me-1"></i>Participants
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">
                            <i class="fas fa-bullhorn me-1"></i>Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-globe me-1"></i>View Website
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i><?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if ($success_msg = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show flash-message" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_msg = getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show flash-message" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($info_msg = getFlash('info')): ?>
        <div class="alert alert-info alert-dismissible fade show flash-message" role="alert">
            <i class="fas fa-info-circle me-2"></i><?php echo $info_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($warning_msg = getFlash('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show flash-message" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $warning_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Current Marathon Status Banner (for admins and during critical periods) -->
    <?php if (isLoggedIn()): ?>
        <?php 
        $marathon_status = getMarathonStatus();
        $days_until_deadline = getDaysUntilDeadline();
        $days_until_marathon = getDaysUntilMarathon();
        ?>
        
        <?php if ($marathon_status === 'registration_open' && $days_until_deadline <= 7): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-0 text-center" role="alert">
                <strong><i class="fas fa-clock me-2"></i>Registration closes in <?php echo $days_until_deadline; ?> days!</strong>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-sm btn-outline-dark ms-2">Sign Up Now</a>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($marathon_status === 'registration_open' && $days_until_marathon <= 30): ?>
            <div class="alert alert-info alert-dismissible fade show mb-0 text-center" role="alert">
                <strong><i class="fas fa-running me-2"></i>Buffalo Marathon 2025 in <?php echo $days_until_marathon; ?> days!</strong>
                Are you registered?
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($marathon_status === 'registration_closed' && $days_until_marathon > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-0 text-center" role="alert">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Registration has closed.</strong>
                Marathon starts in <?php echo $days_until_marathon; ?> days!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main id="main-content" class="main-content">