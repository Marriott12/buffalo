<?php
/**
 * Buffalo Marathon 2025 - Login Page
 * Production Ready - 2025-08-08 12:37:31 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('/dashboard.php');
}

$errors = [];
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    }
    
    if (empty($errors)) {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Basic validation
        if (empty($email)) {
            $errors[] = 'Email address is required.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // Attempt login
        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, email, password, role, first_name FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && verifyPassword($password, $user['password'])) {
                    // Login successful
                    loginUser($user['id'], $user['email'], $user['role']);
                    
                    setFlashMessage('success', "Welcome back, {$user['first_name']}!");
                    
                    // Redirect to intended page or dashboard
                    $redirect = $_GET['redirect'] ?? '/dashboard.php';
                    redirectTo($redirect);
                } else {
                    $errors[] = 'Invalid email address or password.';
                    logActivity('login_failed', "Failed login attempt for: {$email}");
                }
                
            } catch (Exception $e) {
                $errors[] = 'Login failed. Please try again.';
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Buffalo Marathon 2025</title>
    <meta name="description" content="Login to your Buffalo Marathon 2025 account to manage your registration and access your dashboard.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .login-hero {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .login-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.1)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }
        
        .login-container {
            position: relative;
            z-index: 2;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 3rem 2rem 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 3rem 2rem;
        }
        
        .form-control:focus {
            border-color: var(--army-green);
            box-shadow: 0 0 0 0.2rem rgba(75, 83, 32, 0.25);
        }
        
        .btn-army-green {
            background-color: var(--army-green);
            border-color: var(--army-green);
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-army-green:hover {
            background-color: var(--army-green-dark);
            border-color: var(--army-green-dark);
            color: white;
            transform: translateY(-1px);
        }
        
        .text-army-green {
            color: var(--army-green) !important;
        }
        
        .countdown-info {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .social-login {
            border-top: 1px solid #dee2e6;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-dark position-absolute w-100" style="z-index: 1000;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon 2025
            </a>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="login-hero">
        <div class="container login-container">
            <div class="row justify-content-center align-items-center">
                <!-- Left Side - Info -->
                <div class="col-lg-6 text-white mb-5 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Welcome Back!</h1>
                    <p class="lead mb-4">
                        Login to your Buffalo Marathon 2025 account to manage your registration, 
                        track your progress, and stay updated with the latest event information.
                    </p>
                    
                    <!-- Live Countdown Info -->
                    <div class="countdown-info">
                        <h5 class="text-army-green fw-bold mb-2">
                            <i class="fas fa-clock me-2"></i>Registration Status
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h3 fw-bold text-warning mb-1"><?php echo getDaysUntilMarathon(); ?></div>
                                    <small>Days Until Marathon</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h3 fw-bold text-warning mb-1"><?php echo getDaysUntilDeadline(); ?></div>
                                    <small>Days to Register</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Event Info -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt fa-2x me-3" style="color: var(--gold);"></i>
                                <div>
                                    <h6 class="mb-0">October 11, 2025</h6>
                                    <small class="opacity-75">Saturday, 7:00 AM</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt fa-2x me-3" style="color: var(--gold);"></i>
                                <div>
                                    <h6 class="mb-0">Buffalo Park</h6>
                                    <small class="opacity-75">Recreation Centre</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <i class="fas fa-sign-in-alt fa-3x mb-3" style="color: var(--gold);"></i>
                            <h3 class="mb-0">Login to Your Account</h3>
                            <p class="mb-0 mt-2 opacity-75">Access your marathon dashboard</p>
                        </div>
                        
                        <div class="login-body">
                            <!-- Error Display -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope text-army-green"></i>
                                        </span>
                                        <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($email); ?>" 
                                               placeholder="your.email@example.com" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-semibold">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock text-army-green"></i>
                                        </span>
                                        <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                               placeholder="Enter your password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">
                                            Remember me
                                        </label>
                                    </div>
                                    <a href="/forgot-password.php" class="text-army-green text-decoration-none">
                                        Forgot password?
                                    </a>
                                </div>
                                
                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-army-green btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Social Login -->
                            <div class="social-login text-center">
                                <p class="text-muted mb-3">Don't have an account yet?</p>
                                <a href="/register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" 
                                   class="btn btn-outline-army-green w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create New Account
                                </a>
                            </div>
                            
                            <!-- Quick Stats -->
                            <div class="mt-4 pt-4 border-top">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="h6 text-army-green mb-0"><?php echo number_format(getDBStats()['total_registrations']); ?></div>
                                        <small class="text-muted">Registered</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h6 text-army-green mb-0">6</div>
                                        <small class="text-muted">Categories</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h6 text-army-green mb-0">64</div>
                                        <small class="text-muted">Days Left</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form submission loading state
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
        });
    </script>
</body>
</html>