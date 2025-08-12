<?php
/**
 * Buffalo Marathon 2025 - Marathon Registration
 * Production Ready - 2025-08-08 13:19:16 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

// Require login
requireLogin();

// Check if registration is open
if (!isRegistrationOpen()) {
    setFlashMessage('error', 'Marathon registration is currently closed.');
    redirectTo('/dashboard.php');
}

$user = getCurrentUser();
if (!$user) {
    setFlashMessage('error', 'User session expired. Please login again.');
    redirectTo('/login.php');
}

// Check if user already registered
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM registrations WHERE user_id = ? AND payment_status != 'cancelled'");
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()) {
        setFlashMessage('warning', 'You have already registered for Buffalo Marathon 2025.');
        redirectTo('/dashboard.php');
    }
} catch (Exception $e) {
    error_log("Registration check error: " . $e->getMessage());
}

// Get categories
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY price");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    setFlashMessage('error', 'Unable to load race categories. Please try again.');
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
            'category_id' => (int)($_POST['category_id'] ?? 0),
            't_shirt_size' => sanitizeInput($_POST['t_shirt_size'] ?? ''),
            'dietary_restrictions' => sanitizeInput($_POST['dietary_restrictions'] ?? ''),
            'medical_conditions' => sanitizeInput($_POST['medical_conditions'] ?? ''),
            'payment_method' => sanitizeInput($_POST['payment_method'] ?? ''),
            'payment_reference' => sanitizeInput($_POST['payment_reference'] ?? ''),
            'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? '')
        ];
        
        // Validation
        if (empty($form_data['category_id'])) {
            $errors[] = 'Please select a race category.';
        }
        
        if (!in_array($form_data['t_shirt_size'], ['XS', 'S', 'M', 'L', 'XL', 'XXL'])) {
            $errors[] = 'Please select a valid t-shirt size.';
        }
        
        if (!in_array($form_data['payment_method'], ['mobile_money', 'bank_transfer', 'cash'])) {
            $errors[] = 'Please select a valid payment method.';
        }
        
        // Validate selected category
        $selected_category = null;
        foreach ($categories as $category) {
            if ($category['id'] == $form_data['category_id']) {
                $selected_category = $category;
                break;
            }
        }
        
        if (!$selected_category) {
            $errors[] = 'Invalid race category selected.';
        }
        
        // Age validation for category
        if ($selected_category && $user['date_of_birth']) {
            $birth_date = new DateTime($user['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birth_date)->y;
            
            if ($age < $selected_category['min_age'] || $age > $selected_category['max_age']) {
                $errors[] = "Age requirement not met for {$selected_category['name']}. Required age: {$selected_category['min_age']}-{$selected_category['max_age']} years.";
            }
        }
        
        // Check category availability
        if ($selected_category && $selected_category['max_participants'] > 0) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE category_id = ? AND payment_status != 'cancelled'");
                $stmt->execute([$selected_category['id']]);
                $current_count = $stmt->fetchColumn();
                
                if ($current_count >= $selected_category['max_participants']) {
                    $errors[] = "Sorry, {$selected_category['name']} is fully booked.";
                }
            } catch (Exception $e) {
                $errors[] = 'Unable to check category availability. Please try again.';
            }
        }
        
        // Create registration
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                $registration_number = generateRegistrationNumber();
                $payment_amount = $selected_category['price'];
                
                // Update emergency contact in user profile if provided
                if (!empty($form_data['emergency_contact_name']) || !empty($form_data['emergency_contact_phone'])) {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET emergency_contact_name = ?, emergency_contact_phone = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $form_data['emergency_contact_name'] ?: $user['emergency_contact_name'],
                        $form_data['emergency_contact_phone'] ?: $user['emergency_contact_phone'],
                        $user['id']
                    ]);
                }
                
                // Insert registration
                $stmt = $db->prepare("
                    INSERT INTO registrations (
                        user_id, category_id, registration_number, t_shirt_size,
                        dietary_restrictions, medical_conditions, payment_method,
                        payment_reference, payment_amount, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $user['id'],
                    $form_data['category_id'],
                    $registration_number,
                    $form_data['t_shirt_size'],
                    $form_data['dietary_restrictions'],
                    $form_data['medical_conditions'],
                    $form_data['payment_method'],
                    $form_data['payment_reference'],
                    $payment_amount
                ]);
                
                $registration_id = $db->lastInsertId();
                
                // Log payment
                $stmt = $db->prepare("
                    INSERT INTO payment_logs (registration_id, payment_method, payment_reference, amount, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $registration_id,
                    $form_data['payment_method'],
                    $form_data['payment_reference'],
                    $payment_amount
                ]);
                
                $db->commit();
                
                // Log activity
                logActivity('marathon_registration', "Registered for {$selected_category['name']} - {$registration_number}", $user['id']);
                
                // Send confirmation email
                $email_subject = "Registration Confirmation - Buffalo Marathon 2025";
                $email_body = "
                <h2>Registration Confirmed!</h2>
                <p>Dear {$user['first_name']},</p>
                <p>Thank you for registering for Buffalo Marathon 2025!</p>
                <h3>Registration Details:</h3>
                <ul>
                    <li><strong>Registration Number:</strong> {$registration_number}</li>
                    <li><strong>Category:</strong> {$selected_category['name']} ({$selected_category['distance']})</li>
                    <li><strong>T-Shirt Size:</strong> {$form_data['t_shirt_size']}</li>
                    <li><strong>Amount:</strong> " . formatCurrency($payment_amount) . "</li>
                </ul>
                <p>Please keep your registration number safe. You'll need it for race pack collection.</p>
                <p>See you at the marathon!</p>
                ";
                queueEmail($user['email'], $email_subject, $email_body);
                
                setFlashMessage('success', "Registration successful! Your registration number is: {$registration_number}");
                redirectTo('/dashboard.php');
                
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Registration failed. Please try again.';
                error_log("Marathon registration error: " . $e->getMessage());
            }
        }
    }
}

// Pre-select category if provided
$selected_category_id = $_GET['category'] ?? $form_data['category_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Marathon - Buffalo Marathon 2025</title>
    <meta name="description" content="Register for Buffalo Marathon 2025. Choose your race category and secure your spot in Zambia's premier running event.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .registration-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 3rem 0;
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
        
        .category-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        
        .category-card:hover {
            border-color: var(--army-green);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .category-card.selected {
            border-color: var(--army-green);
            background: rgba(75, 83, 32, 0.1);
        }
        
        .category-price {
            background: var(--gold);
            color: var(--army-green);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 900;
            display: inline-block;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .payment-method:hover,
        .payment-method.selected {
            border-color: var(--army-green);
            background: rgba(75, 83, 32, 0.1);
        }
        
        .form-control:focus {
            border-color: var(--army-green);
            box-shadow: 0 0 0 0.2rem rgba(75, 83, 32, 0.25);
        }
        
        .btn-army-green {
            background-color: var(--army-green);
            border-color: var(--army-green);
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .btn-army-green:hover {
            background-color: var(--army-green-dark);
            border-color: var(--army-green-dark);
            color: white;
        }
        
        .text-army-green {
            color: var(--army-green) !important;
        }
        
        .countdown-widget {
            background: rgba(255, 215, 0, 0.1);
            border: 2px solid var(--gold);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
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
                <a class="nav-link" href="/dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="registration-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3">Register for Buffalo Marathon 2025</h1>
            <p class="lead">Choose your race category and secure your spot in Zambia's premier running event</p>
            
            <!-- Countdown Info -->
            <div class="countdown-widget mx-auto mt-4" style="max-width: 500px;">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="h3 fw-bold text-army-green mb-1"><?php echo getDaysUntilMarathon(); ?></div>
                        <small>Days Until Marathon</small>
                    </div>
                    <div class="col-4">
                        <div class="h3 fw-bold text-army-green mb-1"><?php echo getDaysUntilDeadline(); ?></div>
                        <small>Days to Register</small>
                    </div>
                    <div class="col-4">
                        <div class="h3 fw-bold text-army-green mb-1"><?php echo getDaysUntilEarlyBird(); ?></div>
                        <small>Early Bird Days</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Form -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="form-container">
                        <div class="form-header">
                            <h3 class="mb-0"><i class="fas fa-running me-2"></i>Complete Your Registration</h3>
                            <p class="mb-0 mt-2 opacity-75">Step 2 of 2: Marathon Registration</p>
                        </div>
                        
                        <div class="p-4">
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
                            
                            <form method="POST" id="registrationForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <!-- Race Categories -->
                                <h5 class="text-army-green mb-4"><i class="fas fa-medal me-2"></i>Choose Your Race Category</h5>
                                
                                <div class="row g-3 mb-5">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="category-card" data-category-id="<?php echo $category['id']; ?>">
                                                <input type="radio" name="category_id" value="<?php echo $category['id']; ?>" 
                                                       id="category_<?php echo $category['id']; ?>" 
                                                       <?php echo ($selected_category_id == $category['id']) ? 'checked' : ''; ?>
                                                       style="display: none;">
                                                
                                                <div class="text-center mb-3">
                                                    <h6 class="fw-bold text-army-green"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($category['distance']); ?></p>
                                                    <div class="category-price"><?php echo formatCurrency($category['price']); ?></div>
                                                </div>
                                                
                                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($category['description']); ?></p>
                                                
                                                <div class="text-center">
                                                    <small class="text-muted">
                                                        Age: <?php echo $category['min_age']; ?>-<?php echo $category['max_age']; ?> years
                                                    </small>
                                                </div>
                                                
                                                <?php if ($category['max_participants'] > 0): ?>
                                                    <?php
                                                    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE category_id = ? AND payment_status != 'cancelled'");
                                                    $stmt->execute([$category['id']]);
                                                    $current_count = $stmt->fetchColumn();
                                                    $percentage = ($current_count / $category['max_participants']) * 100;
                                                    ?>
                                                    <div class="mt-3">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small>Availability</small>
                                                            <small><?php echo $current_count; ?>/<?php echo $category['max_participants']; ?></small>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background-color: var(--army-green);"></div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Personal Details -->
                                <h5 class="text-army-green mb-4"><i class="fas fa-user me-2"></i>Personal Details</h5>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="t_shirt_size" class="form-label">T-Shirt Size <span class="text-danger">*</span></label>
                                        <select class="form-select" id="t_shirt_size" name="t_shirt_size" required>
                                            <option value="">Select Size</option>
                                            <option value="XS" <?php echo (($form_data['t_shirt_size'] ?? '') === 'XS') ? 'selected' : ''; ?>>XS</option>
                                            <option value="S" <?php echo (($form_data['t_shirt_size'] ?? '') === 'S') ? 'selected' : ''; ?>>S</option>
                                            <option value="M" <?php echo (($form_data['t_shirt_size'] ?? '') === 'M') ? 'selected' : ''; ?>>M</option>
                                            <option value="L" <?php echo (($form_data['t_shirt_size'] ?? '') === 'L') ? 'selected' : ''; ?>>L</option>
                                            <option value="XL" <?php echo (($form_data['t_shirt_size'] ?? '') === 'XL') ? 'selected' : ''; ?>>XL</option>
                                            <option value="XXL" <?php echo (($form_data['t_shirt_size'] ?? '') === 'XXL') ? 'selected' : ''; ?>>XXL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                               value="<?php echo htmlspecialchars($form_data['emergency_contact_name'] ?? $user['emergency_contact_name'] ?? ''); ?>"
                                               placeholder="Full name of emergency contact">
                                    </div>
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                        <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                               value="<?php echo htmlspecialchars($form_data['emergency_contact_phone'] ?? $user['emergency_contact_phone'] ?? ''); ?>"
                                               placeholder="+260 XXX XXXXXX (e.g., +260 972 545 658)">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dietary_restrictions" class="form-label">Dietary Restrictions</label>
                                        <input type="text" class="form-control" id="dietary_restrictions" name="dietary_restrictions" 
                                               value="<?php echo htmlspecialchars($form_data['dietary_restrictions'] ?? ''); ?>"
                                               placeholder="Any dietary restrictions or allergies">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="medical_conditions" class="form-label">Medical Conditions</label>
                                    <textarea class="form-control" id="medical_conditions" name="medical_conditions" rows="3"
                                              placeholder="Any medical conditions we should be aware of"><?php echo htmlspecialchars($form_data['medical_conditions'] ?? ''); ?></textarea>
                                </div>
                                
                                <!-- Payment Method -->
                                <h5 class="text-army-green mb-4"><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="payment-method" data-payment="mobile_money">
                                            <input type="radio" name="payment_method" value="mobile_money" id="mobile_money" style="display: none;">
                                            <i class="fas fa-mobile-alt fa-2x text-army-green mb-2"></i>
                                            <h6>Mobile Money</h6>
                                            <small class="text-muted">MTN, Airtel, Zamtel</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-method" data-payment="bank_transfer">
                                            <input type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" style="display: none;">
                                            <i class="fas fa-university fa-2x text-army-green mb-2"></i>
                                            <h6>Bank Transfer</h6>
                                            <small class="text-muted">Direct bank deposit</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-method" data-payment="cash">
                                            <input type="radio" name="payment_method" value="cash" id="cash" style="display: none;">
                                            <i class="fas fa-money-bill fa-2x text-army-green mb-2"></i>
                                            <h6>Cash Payment</h6>
                                            <small class="text-muted">Pay at registration</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4" id="payment_reference_section" style="display: none;">
                                    <label for="payment_reference" class="form-label">Payment Reference</label>
                                    <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                                           value="<?php echo htmlspecialchars($form_data['payment_reference'] ?? ''); ?>"
                                           placeholder="Transaction ID or reference number">
                                    <div class="form-text">If paying via mobile money or bank transfer, enter your transaction reference</div>
                                </div>
                                
                                <!-- Terms and Conditions -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="waiver" name="waiver" required>
                                        <label class="form-check-label" for="waiver">
                                            I acknowledge that I participate in Buffalo Marathon 2025 at my own risk and agree to the 
                                            <a href="/waiver.php" target="_blank">Event Waiver</a> and 
                                            <a href="/terms.php" target="_blank">Terms & Conditions</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="photo_consent" name="photo_consent">
                                        <label class="form-check-label" for="photo_consent">
                                            I consent to photographs and videos being taken during the event for promotional purposes
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Registration Summary -->
                                <div class="card bg-light mb-4" id="registration_summary" style="display: none;">
                                    <div class="card-body">
                                        <h6 class="text-army-green mb-3">Registration Summary</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Participant:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?><br>
                                                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Category:</strong> <span id="summary_category">-</span><br>
                                                <strong>Distance:</strong> <span id="summary_distance">-</span><br>
                                                <strong>Registration Fee:</strong> <span id="summary_price">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-army-green btn-lg" id="submit_btn" disabled>
                                        <i class="fas fa-running me-2"></i>Complete Registration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const categories = <?php echo json_encode($categories); ?>;
        
        // Category selection
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.category-card').forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Check the radio button
                const categoryId = this.dataset.categoryId;
                document.getElementById('category_' + categoryId).checked = true;
                
                // Update summary
                updateSummary();
            });
        });
        
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Check the radio button
                const paymentType = this.dataset.payment;
                document.getElementById(paymentType).checked = true;
                
                // Show/hide payment reference field
                const referenceSection = document.getElementById('payment_reference_section');
                if (paymentType === 'cash') {
                    referenceSection.style.display = 'none';
                    document.getElementById('payment_reference').required = false;
                } else {
                    referenceSection.style.display = 'block';
                    document.getElementById('payment_reference').required = true;
                }
                
                checkFormComplete();
            });
        });
        
        // Update registration summary
        function updateSummary() {
            const selectedCategory = document.querySelector('input[name="category_id"]:checked');
            if (selectedCategory) {
                const categoryId = selectedCategory.value;
                const category = categories.find(c => c.id == categoryId);
                
                if (category) {
                    document.getElementById('summary_category').textContent = category.name;
                    document.getElementById('summary_distance').textContent = category.distance;
                    document.getElementById('summary_price').textContent = 'K' + parseFloat(category.price).toFixed(2);
                    document.getElementById('registration_summary').style.display = 'block';
                }
            }
            
            checkFormComplete();
        }
        
        // Check if form is complete
        function checkFormComplete() {
            const categorySelected = document.querySelector('input[name="category_id"]:checked');
            const tShirtSize = document.getElementById('t_shirt_size').value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const waiver = document.getElementById('waiver').checked;
            
            const submitBtn = document.getElementById('submit_btn');
            
            if (categorySelected && tShirtSize && paymentMethod && waiver) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // Form validation
        document.getElementById('t_shirt_size').addEventListener('change', checkFormComplete);
        document.getElementById('waiver').addEventListener('change', checkFormComplete);
        
        // Initialize if category is pre-selected
        const preSelectedCategory = document.querySelector('input[name="category_id"]:checked');
        if (preSelectedCategory) {
            const categoryCard = document.querySelector(`[data-category-id="${preSelectedCategory.value}"]`);
            if (categoryCard) {
                categoryCard.classList.add('selected');
                updateSummary();
            }
        }
        
        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submit_btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Registration...';
        });
    </script>
</body>
</html>