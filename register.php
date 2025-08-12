<?php
/**
 * Buffalo Marathon 2025 - User Registration
 * Production Ready - 2025-08-08 12:37:31 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('/dashboard.php');
}

$errors = [];
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    }
    
    if (empty($errors)) {
        // Sanitize input
        $form_data = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
            'gender' => sanitizeInput($_POST['gender'] ?? ''),
            'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Validation
        if (empty($form_data['first_name'])) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($form_data['last_name'])) {
            $errors[] = 'Last name is required.';
        }
        
        if (!validateEmail($form_data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($form_data['phone'])) {
            $errors[] = 'Phone number is required.';
        }
        
        if (empty($form_data['date_of_birth'])) {
            $errors[] = 'Date of birth is required.';
        }
        
        if (!in_array($form_data['gender'], ['male', 'female', 'other'])) {
            $errors[] = 'Please select a valid gender.';
        }
        
        if (strlen($form_data['password']) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        
        if ($form_data['password'] !== $form_data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$form_data['email']]);
                
                if ($stmt->fetch()) {
                    $errors[] = 'An account with this email address already exists.';
                }
            } catch (Exception $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
        
        // Age validation
        if (empty($errors)) {
            $birth_date = new DateTime($form_data['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birth_date)->y;
            
            if ($age < 5) {
                $errors[] = 'You must be at least 5 years old to register.';
            }
        }
        
        // Create account
        if (empty($errors)) {
            try {
                $hashed_password = hashPassword($form_data['password']);
                
                $stmt = $db->prepare("
                    INSERT INTO users (
                        email, password, first_name, last_name, phone, 
                        date_of_birth, gender, emergency_contact_name, 
                        emergency_contact_phone, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $form_data['email'],
                    $hashed_password,
                    $form_data['first_name'],
                    $form_data['last_name'],
                    $form_data['phone'],
                    $form_data['date_of_birth'],
                    $form_data['gender'],
                    $form_data['emergency_contact_name'],
                    $form_data['emergency_contact_phone']
                ]);
                
                $user_id = $db->lastInsertId();
                
                // Log activity
                logActivity('user_registration', "New user registered: {$form_data['email']}", $user_id);
                
                // Send welcome email
                sendWelcomeEmail($form_data['email'], $form_data['first_name']);
                
                // Auto-login user
                loginUser($user_id, $form_data['email'], 'user');
                
                setFlashMessage('success', 'Account created successfully! Welcome to Buffalo Marathon 2025.');
                
                // Redirect
                $redirect = $_GET['redirect'] ?? '/dashboard.php';
                if (isset($_GET['category'])) {
                    $redirect = '/register-marathon.php?category=' . urlencode($_GET['category']);
                }
                
                redirectTo($redirect);
                
            } catch (Exception $e) {
                $errors[] = 'Failed to create account. Please try again.';
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}

// Get selected category if provided
$selected_category = null;
if (isset($_GET['category'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, price FROM categories WHERE id = ? AND is_active = 1");
        $stmt->execute([$_GET['category']]);
        $selected_category = $stmt->fetch();
    } catch (Exception $e) {
        // Ignore error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Buffalo Marathon 2025</title>
    <meta name="description" content="Create your Buffalo Marathon 2025 account and join Zambia's premier running event.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .registration-hero {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 2rem 0;
        }
        
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .form-body {
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: var(--army-green);
            box-shadow: 0 0 0 0.2rem rgba(75, 83, 32, 0.25);
        }
        
        .category-preview {
            background: rgba(75, 83, 32, 0.1);
            border: 2px solid var(--army-green);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
        
        .text-army-green {
            color: var(--army-green) !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--army-green);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon 2025
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/login.php">Already have an account? <strong>Login</strong></a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="registration-hero">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3">Join Buffalo Marathon 2025</h1>
            <p class="lead">Create your account and become part of Zambia's premier running event</p>
            
            <?php if ($selected_category): ?>
                <div class="category-preview mx-auto mt-4" style="max-width: 400px;">
                    <h5 class="fw-bold text-army-green mb-2">
                        <i class="fas fa-running me-2"></i><?php echo htmlspecialchars($selected_category['name']); ?>
                    </h5>
                    <p class="mb-0">Registration Fee: <strong class="text-army-green"><?php echo formatCurrency($selected_category['price']); ?></strong></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Registration Form -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="form-container">
                        <div class="form-header">
                            <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Your Account</h3>
                            <p class="mb-0 mt-2 opacity-75">Step 1 of 2: Account Creation</p>
                        </div>
                        
                        <div class="form-body">
                            <!-- Error Display -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <!-- Personal Information -->
                                <h5 class="text-army-green mb-3"><i class="fas fa-user me-2"></i>Personal Information</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                                        <div class="form-text">This will be your login username</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                                               placeholder="+260 XXX XXXXXX (e.g., +260 972 545 658)" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>" 
                                               max="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo (($form_data['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo (($form_data['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo (($form_data['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Emergency Contact -->
                                <h5 class="text-army-green mb-3 mt-4"><i class="fas fa-phone me-2"></i>Emergency Contact</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                               value="<?php echo htmlspecialchars($form_data['emergency_contact_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                        <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                               value="<?php echo htmlspecialchars($form_data['emergency_contact_phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <!-- Password -->
                                <h5 class="text-army-green mb-3 mt-4"><i class="fas fa-lock me-2"></i>Account Security</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                        <div class="form-text">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <!-- Terms and Conditions -->
                                <div class="mt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="/terms.php" target="_blank">Terms of Service</a> 
                                            and <a href="/privacy.php" target="_blank">Privacy Policy</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="marketing" name="marketing">
                                        <label class="form-check-label" for="marketing">
                                            I would like to receive updates about Buffalo Marathon and future events
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-army-green btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Create Account
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <p class="mb-0">Already have an account? 
                                        <a href="/login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" 
                                           class="text-army-green fw-bold">Login here</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Indicators -->
    <section class="py-4 bg-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <i class="fas fa-shield-alt fa-2x text-army-green mb-2"></i>
                    <h6>Secure Registration</h6>
                    <small class="text-muted">Your data is protected with enterprise-grade security</small>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-clock fa-2x text-army-green mb-2"></i>
                    <h6>Quick Process</h6>
                    <small class="text-muted">Registration takes less than 3 minutes</small>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-envelope fa-2x text-army-green mb-2"></i>
                    <h6>Instant Confirmation</h6>
                    <small class="text-muted">Receive immediate confirmation via email</small>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-headset fa-2x text-army-green mb-2"></i>
                    <h6>24/7 Support</h6>
                    <small class="text-muted">Get help whenever you need it</small>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Age validation
        document.getElementById('date_of_birth').addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            
            if (age < 5) {
                this.setCustomValidity('You must be at least 5 years old to register');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>