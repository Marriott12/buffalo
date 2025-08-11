<?php
require_once '../includes/functions.php';
requireAdmin();

$page_title = 'Admin Dashboard';
$db = getDB();

// Get comprehensive statistics
$stats = [];

// Basic registration stats
$stats['total_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status != 'cancelled'")->fetchColumn();
$stats['paid_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid'")->fetchColumn();
$stats['pending_payments'] = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending'")->fetchColumn();
$stats['total_revenue'] = $db->query("SELECT SUM(c.fee) FROM registrations r JOIN categories c ON r.category_id = c.id WHERE r.payment_status = 'paid'")->fetchColumn() ?? 0;

// Category breakdown
$stmt = $db->query("
    SELECT c.name, COUNT(r.id) as count, SUM(c.fee) as revenue
    FROM categories c 
    LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status != 'cancelled'
    GROUP BY c.id, c.name 
    ORDER BY count DESC
");
$category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent registrations
$stmt = $db->query("
    SELECT r.*, u.first_name, u.last_name, u.email, c.name as category_name, c.fee
    FROM registrations r 
    JOIN users u ON r.user_id = u.id 
    JOIN categories c ON r.category_id = c.id 
    ORDER BY r.registered_at DESC 
    LIMIT 10
");
$recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment method breakdown
$stmt = $db->query("
    SELECT payment_method, COUNT(*) as count 
    FROM registrations 
    WHERE payment_status = 'paid' AND payment_method IS NOT NULL
    GROUP BY payment_method
");
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Registration trends (last 30 days)
$stmt = $db->query("
    SELECT DATE(registered_at) as date, COUNT(*) as count
    FROM registrations 
    WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(registered_at)
    ORDER BY date ASC
");
$registration_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Time until marathon
$marathon_status = getMarathonStatus();
$days_until_marathon = getDaysUntilMarathon();
$days_until_deadline = getDaysUntilDeadline();

logActivity('admin_dashboard_view', 'Viewed admin dashboard');

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-lg-2 col-md-3">
            <div class="admin-sidebar">
                <div class="px-3 mb-4">
                    <h6 class="text-white mb-0">
                        <i class="fas fa-cog me-2"></i>Admin Panel
                    </h6>
                    <small class="text-white-50">Buffalo Marathon 2025</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="participants.php">
                        <i class="fas fa-users me-2"></i>Participants
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="fas fa-credit-card me-2"></i>Payments
                    </a>
                    <a class="nav-link" href="schedule.php">
                        <i class="fas fa-calendar me-2"></i>Schedule
                    </a>
                    <a class="nav-link" href="announcements.php">
                        <i class="fas fa-bullhorn me-2"></i>Announcements
                    </a>
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-list me-2"></i>Categories
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cogs me-2"></i>Settings
                    </a>
                    <hr class="border-secondary mx-3">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-globe me-2"></i>View Website
                    </a>
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10 col-md-9">
            <div class="admin-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="display-6 text-army-green mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </h1>
                        <p class="text-muted mb-0">Buffalo Marathon 2025 Administration</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-clock me-1"></i>
                            <span id="current-time"><?php echo date('g:i:s A'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Marathon Status Alert -->
                <?php if ($marathon_status === 'registration_open'): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="alert-heading mb-1">
                                    <i class="fas fa-check-circle me-2"></i>Registration Open
                                </h5>
                                <p class="mb-0">
                                    Marathon registration is currently open. 
                                    <strong><?php echo $days_until_deadline; ?> days</strong> remaining until deadline.
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                <div class="small text-muted">Marathon in</div>
                                <div class="h4 mb-0"><?php echo $days_until_marathon; ?> days</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($marathon_status === 'registration_closed'): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-4">
                        <h5 class="alert-heading mb-1">
                            <i class="fas fa-exclamation-triangle me-2"></i>Registration Closed
                        </h5>
                        <p class="mb-0">
                            Registration deadline has passed. Marathon in <strong><?php echo $days_until_marathon; ?> days</strong>.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <h5 class="alert-heading mb-1">
                            <i class="fas fa-flag-checkered me-2"></i>Marathon Complete
                        </h5>
                        <p class="mb-0">Buffalo Marathon 2025 has been completed. View final reports and results.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="dashboard-card">
                            <i class="fas fa-users card-icon"></i>
                            <div class="card-number"><?php echo number_format($stats['total_registrations']); ?></div>
                            <div class="card-label">Total Participants</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="dashboard-card">
                            <i class="fas fa-check-circle card-icon"></i>
                            <div class="card-number"><?php echo number_format($stats['paid_registrations']); ?></div>
                            <div class="card-label">Confirmed Payments</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="dashboard-card">
                            <i class="fas fa-clock card-icon"></i>
                            <div class="card-number"><?php echo number_format($stats['pending_payments']); ?></div>
                            <div class="card-label">Pending Payments</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="dashboard-card">
                            <i class="fas fa-money-bill-wave card-icon"></i>
                            <div class="card-number"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                            <div class="card-label">Total Revenue</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Category Breakdown -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-army-green text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Registration by Category
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($category_stats as $category): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                            <small class="text-muted">
                                                Revenue: <?php echo formatCurrency($category['revenue'] ?? 0); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-army-green"><?php echo $category['count']; ?></span>
                                        </div>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-army-green" 
                                             style="width: <?php echo $stats['total_registrations'] > 0 ? ($category['count'] / $stats['total_registrations'] * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Registrations -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-army-green text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Registrations
                                </h5>
                                <a href="participants.php" class="btn btn-light btn-sm">
                                    <i class="fas fa-eye me-1"></i>View All
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Participant</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($recent_registrations, 0, 8) as $reg): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></strong>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($reg['registration_number']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($reg['category_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $reg['payment_status'] === 'paid' ? 'bg-success' : 
                                                             ($reg['payment_status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($reg['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j', strtotime($reg['registered_at'])); ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Quick Actions -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-army-green text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="participants.php?filter=pending" class="btn btn-outline-warning">
                                        <i class="fas fa-clock me-2"></i>Review Pending Payments (<?php echo $stats['pending_payments']; ?>)
                                    </a>
                                    <a href="announcements.php?action=create" class="btn btn-outline-primary">
                                        <i class="fas fa-bullhorn me-2"></i>Create Announcement
                                    </a>
                                    <a href="schedule.php?action=create" class="btn btn-outline-info">
                                        <i class="fas fa-calendar-plus me-2"></i>Add Schedule Event
                                    </a>
                                    <a href="reports.php" class="btn btn-outline-success">
                                        <i class="fas fa-download me-2"></i>Export Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-army-green text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-server me-2"></i>System Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Registration Status</span>
                                        <span class="badge <?php echo isRegistrationOpen() ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo isRegistrationOpen() ? 'Open' : 'Closed'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Database</span>
                                        <span class="badge bg-success">Online</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Email System</span>
                                        <span class="badge bg-success">Operational</span>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between">
                                        <span>Last Backup</span>
                                        <small class="text-muted"><?php echo date('M j, g:i A'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-army-green text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>Payment Methods
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($payment_methods)): ?>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span><?php echo htmlspecialchars($method['payment_method'] ?? 'Not specified'); ?></span>
                                            <span class="badge bg-army-green"><?php echo $method['count']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No payment data available yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
    document.getElementById('current-time').textContent = timeString;
}

// Update time every second
setInterval(updateTime, 1000);

// Auto-refresh dashboard data every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>

<?php include '../includes/footer.php'; ?>