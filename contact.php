<?php
/**
 * Buffalo Marathon 2025 - Contact Page
 * Production Ready - 2025-08-08 13:48:27 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

$errors = [];
$success = false;
$form_data = [];

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    }
    
    if (empty($errors)) {
        // Sanitize input
        $form_data = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'subject' => sanitizeInput($_POST['subject'] ?? ''),
            'message' => sanitizeInput($_POST['message'] ?? ''),
            'inquiry_type' => sanitizeInput($_POST['inquiry_type'] ?? '')
        ];
        
        // Validation
        if (empty($form_data['name'])) {
            $errors[] = 'Name is required.';
        }
        
        if (!validateEmail($form_data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($form_data['subject'])) {
            $errors[] = 'Subject is required.';
        }
        
        if (empty($form_data['message'])) {
            $errors[] = 'Message is required.';
        }
        
        if (strlen($form_data['message']) < 10) {
            $errors[] = 'Message must be at least 10 characters long.';
        }
        
        // Send email if no errors
        if (empty($errors)) {
            try {
                // Prepare email content
                $email_subject = "Contact Form: " . $form_data['subject'];
                $email_body = "
                <h2>New Contact Form Submission</h2>
                <p><strong>From:</strong> {$form_data['name']} ({$form_data['email']})</p>
                " . ($form_data['phone'] ? "<p><strong>Phone:</strong> {$form_data['phone']}</p>" : "") . "
                <p><strong>Inquiry Type:</strong> {$form_data['inquiry_type']}</p>
                <p><strong>Subject:</strong> {$form_data['subject']}</p>
                <hr>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($form_data['message'])) . "</p>
                <hr>
                <p><small>Submitted on: " . date('Y-m-d H:i:s') . " UTC</small></p>
                ";
                
                // Queue email to admin
                queueEmail(ADMIN_EMAIL, $email_subject, $email_body);
                
                // Send confirmation to user
                $user_subject = "Contact Confirmation - Buffalo Marathon 2025";
                $user_body = "
                <h2>Thank you for contacting us!</h2>
                <p>Dear {$form_data['name']},</p>
                <p>We have received your message and will respond within 24-48 hours.</p>
                <h3>Your Message:</h3>
                <p><strong>Subject:</strong> {$form_data['subject']}</p>
                <p>" . nl2br(htmlspecialchars($form_data['message'])) . "</p>
                <p>Best regards,<br>Buffalo Marathon Team</p>
                ";
                
                queueEmail($form_data['email'], $user_subject, $user_body);
                
                // Log activity
                logActivity('contact_form', "Contact form submitted: {$form_data['subject']}", null);
                
                $success = true;
                $form_data = []; // Clear form
                
            } catch (Exception $e) {
                $errors[] = 'Failed to send message. Please try again.';
                error_log("Contact form error: " . $e->getMessage());
            }
        }
    }
}

$page_title = 'Contact Us - Buffalo Marathon 2025';
$page_description = 'Contact Buffalo Marathon 2025 organizers. Get support, ask questions, or provide feedback about Zambia\'s premier running event.';

// Include header
include 'includes/header.php';
?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .contact-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 4rem 0;
        }
        
        .contact-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .contact-form {
            padding: 3rem;
        }
        
        .contact-info {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .info-item {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            transition: background 0.3s ease;
        }
        
        .info-item:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .info-icon {
            width: 60px;
            height: 60px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--army-green);
            font-size: 1.5rem;
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
            transform: translateY(-2px);
        }
        
        .text-army-green { color: var(--army-green) !important; }
        
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: var(--gold);
            color: var(--army-green);
            transform: translateY(-3px);
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
    <section class="contact-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Contact Us</h1>
            <p class="lead mb-4">
                We're here to help! Get in touch with our team for any questions, 
                support, or feedback about Buffalo Marathon 2025.
            </p>
        </div>
    </section>

    <!-- Contact Form & Info -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="contact-card">
                        <div class="row g-0">
                            <!-- Contact Form -->
                            <div class="col-lg-8">
                                <div class="contact-form">
                                    <h3 class="text-army-green mb-4">Send us a Message</h3>
                                    
                                    <?php if ($success): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Message sent successfully!</strong> 
                                            We'll get back to you within 24-48 hours.
                                        </div>
                                    <?php endif; ?>
                                    
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
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                                                       placeholder="+260 XXX XXXXXX">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="inquiry_type" class="form-label">Inquiry Type</label>
                                                <select class="form-select" id="inquiry_type" name="inquiry_type">
                                                    <option value="">Select Type</option>
                                                    <option value="general" <?php echo (($form_data['inquiry_type'] ?? '') === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                                                    <option value="registration" <?php echo (($form_data['inquiry_type'] ?? '') === 'registration') ? 'selected' : ''; ?>>Registration Support</option>
                                                    <option value="payment" <?php echo (($form_data['inquiry_type'] ?? '') === 'payment') ? 'selected' : ''; ?>>Payment Issues</option>
                                                    <option value="technical" <?php echo (($form_data['inquiry_type'] ?? '') === 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                                                    <option value="media" <?php echo (($form_data['inquiry_type'] ?? '') === 'media') ? 'selected' : ''; ?>>Media Inquiry</option>
                                                    <option value="sponsorship" <?php echo (($form_data['inquiry_type'] ?? '') === 'sponsorship') ? 'selected' : ''; ?>>Sponsorship</option>
                                                    <option value="volunteer" <?php echo (($form_data['inquiry_type'] ?? '') === 'volunteer') ? 'selected' : ''; ?>>Volunteer</option>
                                                    <option value="other" <?php echo (($form_data['inquiry_type'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="subject" name="subject" 
                                                   value="<?php echo htmlspecialchars($form_data['subject'] ?? ''); ?>" 
                                                   placeholder="Brief description of your inquiry" required>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="message" name="message" rows="5" 
                                                      placeholder="Please provide details about your inquiry..." required><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-army-green btn-lg">
                                                <i class="fas fa-paper-plane me-2"></i>Send Message
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Contact Info -->
                            <div class="col-lg-4">
                                <div class="contact-info">
                                    <h4 class="mb-4">Get in Touch</h4>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <h6>Email Us</h6>
                                        <p class="mb-0"><?php echo SITE_EMAIL; ?></p>
                                        <small class="opacity-75">Response within 24-48 hours</small>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <h6>Call Us</h6>
                                        <p class="mb-0">+260-XXX-XXXXXX</p>
                                        <small class="opacity-75">Mon-Fri, 8:00 AM - 5:00 PM</small>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <h6>Visit Us</h6>
                                        <p class="mb-0"><?php echo EVENT_VENUE; ?><br>
                                        <?php echo EVENT_ADDRESS; ?><br>
                                        <?php echo EVENT_CITY; ?></p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6>Follow Us</h6>
                                        <div class="social-links">
                                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                                            <a href="#"><i class="fab fa-twitter"></i></a>
                                            <a href="#"><i class="fab fa-instagram"></i></a>
                                            <a href="#"><i class="fab fa-youtube"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-4">
                        <h2 class="text-army-green mb-3">Find Us</h2>
                        <p class="text-muted">Buffalo Park Recreation Centre is easily accessible from all parts of Lusaka</p>
                    </div>
                    
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15241.135829577657!2d28.27!3d-15.39!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sBuffalo%20Park%20Recreation%20Centre!5e0!3m2!1sen!2szm!4v1691483200000"
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Help -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="text-army-green mb-3">Quick Help</h2>
                <p class="text-muted">Common questions and quick solutions</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-user-plus fa-3x text-army-green mb-3"></i>
                            <h5>Registration Help</h5>
                            <p class="text-muted">Need help with creating an account or registering for the marathon?</p>
                            <a href="/faq.php#category-0" class="btn btn-outline-army-green">View FAQ</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-3x text-army-green mb-3"></i>
                            <h5>Payment Support</h5>
                            <p class="text-muted">Issues with mobile money, bank transfer, or payment confirmation?</p>
                            <a href="/faq.php#category-0" class="btn btn-outline-army-green">Payment FAQ</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x text-army-green mb-3"></i>
                            <h5>Race Day Info</h5>
                            <p class="text-muted">Questions about race day schedule, location, or what to bring?</p>
                            <a href="/schedule.php" class="btn btn-outline-army-green">View Schedule</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script>
// Character counter for message field
const messageField = document.getElementById('message');
if (messageField) {
    const counter = document.createElement('small');
    counter.className = 'text-muted';
    messageField.parentNode.appendChild(counter);
    
    function updateCounter() {
        const length = messageField.value.length;
        counter.textContent = `${length} characters`;
        
        if (length < 10) {
            counter.className = 'text-danger';
        } else {
            counter.className = 'text-muted';
        }
    }
    
    messageField.addEventListener('input', updateCounter);
    updateCounter();
}

// Form submission loading state
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>