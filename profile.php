<?php
/**
 * Buffalo Marathon 2025 - User Profile
 * Production Ready - 2025-08-08 13:53:48 UTC
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

$errors = [];
$success = false;

// Handle profile update
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
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
            'gender' => sanitizeInput($_POST['gender'] ?? ''),
            'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? ''),
            'current_password' => $_POST['current_password'] ?? '',
            'new_password' => $_POST['new_password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Validation
        if (empty($form_data['first_name'])) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($form_data['last_name'])) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($form_data['phone'])) {
            $errors[] = 'Phone number is required.';
        }
        
        if (!in_array($form_data['gender'], ['male', 'female', 'other'])) {
            $errors[] = 'Please select a valid gender.';
        }
        
        // Password change validation
        $change_password = !empty($form_data['current_password']) || !empty($form_data['new_password']);
        
        if ($change_password) {
            if (empty($form_data['current_password'])) {
                $errors[] = 'Current password is required to change password.';
            } elseif (!password_verify($form_data['current_password'], $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
            
            if (empty($form_data['new_password'])) {
                $errors[] = 'New password is required.';
            } elseif (strlen($form_data['new_password']) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
            }
            
            if ($form_data['new_password'] !== $form_data['confirm_password']) {
                $errors[] = 'New passwords do not match.';
            }
        }
        
        // Update profile
        if (empty($errors)) {
            try {
                $db = getDB();
                
                if ($change_password) {
                    // Update with password change
                    $hashed_password = password_hash($form_data['new_password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users SET 
                            first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, 
                            gender = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                            password = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $form_data['first_name'], $form_data['last_name'], $form_data['phone'],
                        $form_data['date_of_birth'], $form_data['gender'], 
                        $form_data['emergency_contact_name'], $form_data['emergency_contact_phone'],
                        $hashed_password, $user['id']
                    ]);
                    
                    logActivity('password_change', 'User changed password', $user['id']);
                } else {
                    // Update without password change
                    $stmt = $db->prepare("
                        UPDATE users SET 
                            first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, 
                            gender = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $form_data['first_name'], $form_data['last_name'], $form_data['phone'],
                        $form_data['date_of_birth'], $form_data['gender'], 
                        $form_data['emergency_contact_name'], $form_data['emergency_contact_phone'],
                        $user['id']
                    ]);
                }
                
                logActivity('profile_update', 'User updated profile information', $user['id']);
                
                $success = true;
                
                // Refresh user data
                $user = getCurrentUser();
                
            } catch (Exception $e) {
                $errors[] = 'Failed to update profile. Please try again.';
                error_log("Profile update error: " . $e->getMessage());
            }
        }
    }
}

// Get user's registration if exists
$registration = null;
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, c.name as category_name, c.distance, c.price 
        FROM registrations r 
        JOIN categories c ON r.category_id = c.id 
        WHERE r.user_id = ? AND r.payment_status != 'cancelled'
        ORDER BY r.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $registration = $stmt->fetch();
} catch (Exception $e) {
    error_log("Profile registration fetch error: " . $e->getMessage());
}

// Page title and description for header
$page_title = "My Profile - Buffalo Marathon 2025";
$page_description = "Manage your Buffalo Marathon 2025 profile, update personal information, and view your registration details.";
$additional_css = "
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 3rem 0;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            margin: 0 auto 1rem;
            position: relative;
        }
        
        .profile-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-header {
            color: var(--army-green);
            border-bottom: 2px solid var(--gold);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            border-color: var(--army-green);
            box-shadow: 0 0 0 0.2rem rgba(75, 83, 32, 0.25);
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
        
        .text-army-green { color: var(--army-green) !important; }
        .bg-army-green { background-color: var(--army-green) !important; }
        
        .info-card {
            background: rgba(75, 83, 32, 0.1);
            border-left: 4px solid var(--army-green);
            padding: 1.5rem;
            border-radius: 0 10px 10px 0;
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
    </style>
";

include 'includes/header.php'; 
?>

<!-- Add spacing between header and profile content -->
<div class="py-3"></div>
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
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/categories.php">Categories</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(($user['first_name'] ?? 'User') . ' ' . ($user['last_name'] ?? '')); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="/profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="/dashboard.php">Dashboard</a></li>
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

    <!-- Header -->
    <section class="profile-header">
        <div class="container text-center">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="fw-bold mb-2"><?php echo htmlspecialchars(($user['first_name'] ?? 'User') . ' ' . ($user['last_name'] ?? '')); ?></h2>
            <p class="lead mb-0"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            <small class="opacity-75">
                Member since <?php echo formatDate($user['created_at'], 'F Y'); ?>
            </small>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php
    $flash_messages = getAllFlashMessages();
    if ($success) {
        echo '<div class="alert alert-success alert-dismissible fade show m-0">
                <div class="container">
                    <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              </div>';
    }
    foreach ($flash_messages as $flash):
        $type = $flash['type'] ?? 'info';
        $message = $flash['message'] ?? '';
        switch($type) {
            case 'success': $alert_class = 'alert-success'; break;
            case 'error': $alert_class = 'alert-danger'; break;
            case 'warning': $alert_class = 'alert-warning'; break;
            default: $alert_class = 'alert-info'; break;
        }
    ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show m-0">
            <div class="container">
                <?php echo htmlspecialchars($message ?? ''); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="profile-card">
                        <div class="p-4">
                            <h3 class="section-header">Personal Information</h3>
                            
                            <!-- Error Display -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error ?? ''); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <!-- Basic Information -->
                                <h5 class="text-army-green mb-3">Basic Information</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                                        <div class="form-text">Email cannot be changed. Contact support if needed.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>" 
                                               max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo (($user['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo (($user['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                                                                                        <option value="other" <?php echo (($user['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Emergency Contact -->
                                <h5 class="text-army-green mb-3 mt-4"><i class="fas fa-phone me-2"></i>Emergency Contact</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                               value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                        <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                               value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>">
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Password Change -->
                                <h5 class="text-army-green mb-3 mt-4">Change Password (Optional)</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-army-green btn-lg">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Bank Account Details -->
                    <div class="profile-section">
                        <h5 class="section-header">
                            <i class="fas fa-university me-2"></i>Payment Information
                        </h5>
                        
                        <div class="bank-details-card">
                            <div class="bank-header text-center mb-3">
                                <i class="fas fa-landmark fa-2x text-army-green mb-2"></i>
                                <h6 class="text-army-green mb-0">Official Bank Account</h6>
                                <small class="text-muted">For marathon registration payments</small>
                            </div>
                            
                            <div class="bank-info">
                                <div class="bank-detail-row">
                                    <span class="bank-label">Bank Name:</span>
                                    <span class="bank-value"><?php echo BANK_NAME; ?></span>
                                </div>
                                
                                <div class="bank-detail-row">
                                    <span class="bank-label">Account Name:</span>
                                    <span class="bank-value"><?php echo BANK_ACCOUNT_NAME; ?></span>
                                </div>
                                
                                <div class="bank-detail-row">
                                    <span class="bank-label">Account Number:</span>
                                    <span class="bank-value font-weight-bold"><?php echo BANK_ACCOUNT_NUMBER; ?></span>
                                </div>
                                
                                <div class="bank-detail-row">
                                    <span class="bank-label">Branch:</span>
                                    <span class="bank-value"><?php echo BANK_BRANCH; ?></span>
                                </div>
                                
                                <div class="bank-detail-row">
                                    <span class="bank-label">SWIFT Code:</span>
                                    <span class="bank-value"><?php echo BANK_SWIFT_CODE; ?></span>
                                </div>
                                
                                <div class="bank-detail-row">
                                    <span class="bank-label">Currency:</span>
                                    <span class="bank-value"><?php echo BANK_CURRENCY; ?></span>
                                </div>
                            </div>
                            
                            <div class="bank-actions mt-3">
                                <button class="btn btn-sm btn-outline-army-green w-100" onclick="copyBankDetails()">
                                    <i class="fas fa-copy me-1"></i>Copy Account Number
                                </button>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Note:</strong> Use this account for all marathon registration payments. Include your registration number as reference.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registration Status -->
                    <?php if ($registration): ?>
                        <div class="profile-section">
                            <h5 class="section-header">Marathon Registration</h5>
                            
                            <div class="text-center mb-3">
                                <span class="status-badge status-<?php echo $registration['payment_status']; ?>">
                                    <?php echo ucfirst($registration['payment_status']); ?>
                                </span>
                            </div>
                            
                            <div class="info-card">
                                <h6 class="text-army-green mb-2">Registration Details</h6>
                                <p class="mb-1"><strong>Category:</strong> <?php echo htmlspecialchars($registration['category_name'] ?? ''); ?></p>
                                <p class="mb-1"><strong>Distance:</strong> <?php echo htmlspecialchars($registration['distance'] ?? ''); ?></p>
                                <p class="mb-1"><strong>Registration #:</strong> <?php echo htmlspecialchars($registration['registration_number'] ?? ''); ?></p>
                                <p class="mb-1"><strong>T-Shirt Size:</strong> <?php echo htmlspecialchars($registration['t_shirt_size'] ?? ''); ?></p>
                                <p class="mb-0"><strong>Fee:</strong> <?php echo formatCurrency($registration['payment_amount'] ?? 0); ?></p>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <a href="/dashboard.php" class="btn btn-outline-army-green">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="profile-section">
                            <h5 class="section-header">Marathon Registration</h5>
                            
                            <div class="text-center py-4">
                                <i class="fas fa-running fa-3x text-army-green mb-3"></i>
                                <h6 class="text-army-green">Not Yet Registered</h6>
                                <p class="text-muted mb-3">Join Buffalo Marathon 2025 and be part of Zambia's premier running event.</p>
                                
                                <?php if (isRegistrationOpen()): ?>
                                    <a href="/register-marathon.php" class="btn btn-army-green">
                                        <i class="fas fa-plus me-1"></i>Register Now
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted"><small>Registration is currently closed.</small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Account Information -->
                    <div class="profile-section">
                        <h5 class="section-header">Account Information</h5>
                        
                        <div class="mb-3">
                            <small class="text-muted">Member Since</small><br>
                            <strong><?php echo formatDate($user['created_at'], 'F j, Y'); ?></strong>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Last Updated</small><br>
                            <strong><?php echo formatDate($user['updated_at'] ?? $user['created_at'], 'F j, Y'); ?></strong>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Account Status</small><br>
                            <span class="badge bg-success">Active</span>
                            <?php if ($user['email_verified'] ?? false): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Unverified</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isAdmin()): ?>
                            <div class="mb-3">
                                <small class="text-muted">Role</small><br>
                                <span class="badge bg-army-green">Administrator</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="profile-section">
                        <h5 class="section-header">Quick Actions</h5>
                        
                        <div class="d-grid gap-2">
                            <a href="/dashboard.php" class="btn btn-outline-army-green">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a href="/schedule.php" class="btn btn-outline-army-green">
                                <i class="fas fa-calendar-alt me-2"></i>Event Schedule
                            </a>
                            <a href="/contact.php" class="btn btn-outline-army-green">
                                <i class="fas fa-headset me-2"></i>Contact Support
                            </a>
                            <?php if (isAdmin()): ?>
                                <a href="/admin/" class="btn btn-outline-secondary">
                                    <i class="fas fa-cog me-2"></i>Admin Panel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Show/hide password change section
        const passwordFields = ['current_password', 'new_password', 'confirm_password'];
        passwordFields.forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('focus', function() {
                passwordFields.forEach(id => {
                    document.getElementById(id).parentElement.style.opacity = '1';
                });
            });
        });
        
        // Auto-dismiss flash messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>

    <!-- Bank Details Styling and Functionality -->
    <style>
        .bank-details-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .bank-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .bank-info {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .bank-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .bank-detail-row:last-child {
            border-bottom: none;
        }
        
        .bank-label {
            font-weight: 500;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .bank-value {
            font-weight: 600;
            color: #2d3436;
            text-align: right;
            word-break: break-all;
        }
        
        .bank-actions .btn {
            transition: all 0.3s ease;
        }
        
        .bank-actions .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .bank-detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .bank-value {
                text-align: left;
                margin-top: 4px;
            }
        }
    </style>

    <script>
        function copyBankDetails() {
            const accountNumber = '<?php echo BANK_ACCOUNT_NUMBER; ?>';
            
            // Modern clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(accountNumber).then(function() {
                    showCopySuccess();
                }).catch(function(err) {
                    fallbackCopyTextToClipboard(accountNumber);
                });
            } else {
                // Fallback method
                fallbackCopyTextToClipboard(accountNumber);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess();
                } else {
                    showCopyError();
                }
            } catch (err) {
                showCopyError();
            }
            
            document.body.removeChild(textArea);
        }
        
        function showCopySuccess() {
            const btn = document.querySelector('.bank-actions .btn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
            btn.classList.remove('btn-outline-army-green');
            btn.classList.add('btn-success');
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-army-green');
            }, 2000);
        }
        
        function showCopyError() {
            const btn = document.querySelector('.bank-actions .btn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-times me-1"></i>Failed to copy';
            btn.classList.remove('btn-outline-army-green');
            btn.classList.add('btn-danger');
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-army-green');
            }, 2000);
        }
    </script>

<?php include 'includes/footer.php'; ?>