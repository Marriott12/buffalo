<?php
require_once 'includes/functions.php';
requireLogin();

$page_title = 'My Registration';
$db = getDB();

// Get user's registration with detailed information
$stmt = $db->prepare("
    SELECT r.*, c.name as category_name, c.distance, c.price, c.description
    FROM registrations r 
    JOIN categories c ON r.category_id = c.id 
    WHERE r.user_id = ? AND r.payment_status != 'cancelled'
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    setFlash('error', 'No active registration found.');
    header('Location: dashboard.php');
    exit();
}

// Get payment logs
$stmt = $db->prepare("
    SELECT pl.*, u.first_name, u.last_name 
    FROM payment_logs pl 
    LEFT JOIN users u ON pl.changed_by = u.id 
    WHERE pl.registration_id = ? 
    ORDER BY pl.changed_at DESC
");
$stmt->execute([$registration['id']]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 text-army-green">
                        <i class="fas fa-running me-2"></i>My Registration
                    </h1>
                    <p class="text-muted mb-0">Buffalo Marathon 2025 - Registration Details</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registration Card -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-army-green text-white">
                    <h4 class="mb-0">Registration Information</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-army-green"><?php echo htmlspecialchars($registration['category_name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($registration['description']); ?></p>
                            
                            <dl class="row">
                                <dt class="col-sm-5">Registration Number:</dt>
                                <dd class="col-sm-7">
                                    <code class="fs-6"><?php echo htmlspecialchars($registration['registration_number']); ?></code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" 
                                            onclick="copyToClipboard('<?php echo $registration['registration_number']; ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </dd>
                                
                                <dt class="col-sm-5">Distance:</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($registration['distance']); ?></dd>
                                
                                <dt class="col-sm-5">Registration Fee:</dt>
                                <dd class="col-sm-7"><?php echo formatCurrency($registration['price'] ?? 0); ?></dd>
                                
                                <dt class="col-sm-5">Age:</dt>
                                <dd class="col-sm-7"><?php echo $registration['age']; ?> years</dd>
                                
                                <dt class="col-sm-5">Gender:</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($registration['gender']); ?></dd>
                                
                                <dt class="col-sm-5">T-Shirt Size:</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($registration['tshirt_size']); ?></dd>
                                
                                <dt class="col-sm-5">Registered:</dt>
                                <dd class="col-sm-7"><?php echo date('F j, Y g:i A', strtotime($registration['registered_at'])); ?></dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Payment Status -->
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Payment Status</h6>
                                    <h3>
                                        <span class="badge <?php 
                                            echo $registration['payment_status'] === 'paid' ? 'bg-success' : 
                                                ($registration['payment_status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger'); 
                                        ?>">
                                            <?php echo ucfirst($registration['payment_status']); ?>
                                        </span>
                                    </h3>
                                    
                                    <?php if ($registration['payment_status'] === 'paid'): ?>
                                        <p class="text-success mb-0">
                                            <i class="fas fa-check-circle"></i> Payment confirmed!
                                        </p>
                                    <?php elseif ($registration['payment_status'] === 'pending'): ?>
                                        <p class="text-warning mb-2">
                                            <i class="fas fa-clock"></i> Awaiting payment confirmation
                                        </p>
                                        <small class="text-muted">
                                            Please ensure payment has been made according to the instructions sent to your email.
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Emergency Contact -->
                            <div class="mt-3">
                                <h6 class="text-army-green">Emergency Contact</h6>
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($registration['emergency_contact_name']); ?></strong>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-phone me-1"></i>
                                    <?php echo htmlspecialchars($registration['emergency_contact_phone']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- What's Included -->
                    <hr>
                    <h6 class="text-army-green mb-3">
                        <i class="fas fa-gift me-2"></i>What's Included in Your Registration
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-tshirt text-success me-2"></i>
                                    <strong>Branded Marathon T-Shirt</strong><br>
                                    <small class="text-muted">High-quality moisture-wicking fabric (Size: <?php echo $registration['tshirt_size']; ?>)</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-medal text-success me-2"></i>
                                    <strong>Finisher's Medal</strong><br>
                                    <small class="text-muted">Beautiful commemorative medal upon completion</small>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-id-card text-success me-2"></i>
                                    <strong>Race Number Bib</strong><br>
                                    <small class="text-muted">Official race identification with timing chip</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-glass-water text-success me-2"></i>
                                    <strong>Free Drink Voucher</strong><br>
                                    <small class="text-muted">Refreshments during and after the race</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Event Information Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-army-green">
                        <i class="fas fa-calendar-alt me-2"></i>Event Information
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-4">Date:</dt>
                        <dd class="col-8">Saturday, October 11, 2025</dd>
                        
                        <dt class="col-4">Time:</dt>
                        <dd class="col-8">7:00 AM Start</dd>
                        
                        <dt class="col-4">Venue:</dt>
                        <dd class="col-8">Buffalo Park Recreation Centre</dd>
                        
                        <dt class="col-4">Address:</dt>
                        <dd class="col-8">Chalala-Along Joe Chibangu Road</dd>
                    </dl>
                    
                    <hr>
                    
                    <h6 class="text-army-green">Event Activities</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-dumbbell me-2 text-muted"></i>Pre & Post-Race Aerobics</li>
                        <li><i class="fas fa-music me-2 text-muted"></i>Zambia Army Pop Band</li>
                        <li><i class="fas fa-utensils me-2 text-muted"></i>Food Zones & Braai Packs</li>
                        <li><i class="fas fa-wine-glass me-2 text-muted"></i>Chill Lounge</li>
                        <li><i class="fas fa-child me-2 text-muted"></i>Kids Zone</li>
                    </ul>
                    
                    <a href="info.php" class="btn btn-outline-army-green btn-sm w-100 mt-2">
                        <i class="fas fa-info-circle me-1"></i>Full Event Details
                    </a>
                </div>
            </div>
            
            <!-- Payment History -->
            <?php if (!empty($payment_history)): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 text-army-green">
                        <i class="fas fa-history me-2"></i>Payment History
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($payment_history as $log): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <small class="fw-bold">
                                <?php echo ucfirst($log['old_status']); ?> → <?php echo ucfirst($log['new_status']); ?>
                            </small>
                            <?php if ($log['first_name']): ?>
                                <br><small class="text-muted">by <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></small>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">
                            <?php echo date('M j, g:i A', strtotime($log['changed_at'])); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('Registration number copied to clipboard!', 'success');
    });
}
</script>

<?php include 'includes/footer.php'; ?>