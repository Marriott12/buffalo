<?php
/**
 * Buffalo Marathon 2025 - Dynamic Admin Dashboard
 * Real-time analytics and comprehensive management interface
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Admin authentication check
if (!function_exists('requireAdmin')) {
    header('Location: ../login.php');
    exit;
}
requireAdmin();

$page_title = 'Dynamic Admin Dashboard - Buffalo Marathon 2025';

// Initialize database connection
try {
    if (function_exists('getDatabase')) {
        $db = getDatabase();
    } else {
        require_once '../includes/database.php';
        $db = getDatabase();
    }
} catch (Exception $e) {
    error_log("Dashboard DB Error: " . $e->getMessage());
    die("Database connection failed. Please check configuration.");
}

// Get real-time comprehensive statistics
$stats = [];
$dashboard_data = [];

try {
    // Core Statistics
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    $stats['total_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE status != 'cancelled'")->fetchColumn();
    $stats['confirmed_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'confirmed'")->fetchColumn();
    $stats['paid_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid'")->fetchColumn();
    $stats['pending_payments'] = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending'")->fetchColumn();
    $stats['failed_payments'] = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'failed'")->fetchColumn();
    
    // Revenue Statistics
    $revenue_query = "SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'pending' THEN final_amount ELSE 0 END) as pending_revenue,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
        AVG(CASE WHEN payment_status = 'paid' THEN final_amount END) as avg_payment
    FROM registrations";
    $revenue_data = $db->query($revenue_query)->fetch(PDO::FETCH_ASSOC);
    $stats = array_merge($stats, $revenue_data);
    
    // Today's Statistics
    $today_query = "SELECT 
        COUNT(*) as today_registrations,
        SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as today_revenue
    FROM registrations 
    WHERE DATE(created_at) = CURDATE()";
    $today_data = $db->query($today_query)->fetch(PDO::FETCH_ASSOC);
    $stats = array_merge($stats, $today_data);
    
    // This week's statistics
    $week_query = "SELECT 
        COUNT(*) as week_registrations,
        SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as week_revenue
    FROM registrations 
    WHERE YEARWEEK(created_at) = YEARWEEK(NOW())";
    $week_data = $db->query($week_query)->fetch(PDO::FETCH_ASSOC);
    $stats = array_merge($stats, $week_data);
    
} catch (Exception $e) {
    error_log("Dashboard Stats Error: " . $e->getMessage());
    // Set default values on error
    $stats = array_fill_keys([
        'total_users', 'total_registrations', 'confirmed_registrations', 
        'paid_registrations', 'pending_payments', 'failed_payments',
        'total_revenue', 'pending_revenue', 'paid_count', 'avg_payment',
        'today_registrations', 'today_revenue', 'week_registrations', 'week_revenue'
    ], 0);
}

// Get detailed analytics data
try {
    // Category performance analysis
    $category_stats = $db->query("
        SELECT 
            c.id,
            c.name,
            c.distance,
            c.price,
            c.early_bird_price,
            c.max_participants,
            COUNT(r.id) as total_registrations,
            COUNT(CASE WHEN r.payment_status = 'paid' THEN 1 END) as paid_registrations,
            COUNT(CASE WHEN r.status = 'confirmed' THEN 1 END) as confirmed_registrations,
            SUM(CASE WHEN r.payment_status = 'paid' THEN r.final_amount ELSE 0 END) as revenue,
            ROUND((COUNT(r.id) / NULLIF(c.max_participants, 0)) * 100, 1) as capacity_percentage,
            AVG(r.final_amount) as avg_registration_fee
        FROM categories c 
        LEFT JOIN registrations r ON c.id = r.category_id AND r.status != 'cancelled'
        WHERE c.is_active = 1
        GROUP BY c.id, c.name, c.distance, c.price, c.early_bird_price, c.max_participants
        ORDER BY total_registrations DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent registrations with enhanced details
    $recent_registrations = $db->query("
        SELECT 
            r.id,
            r.registration_number,
            r.bib_number,
            r.created_at,
            r.payment_status,
            r.status,
            r.final_amount,
            r.payment_method,
            r.t_shirt_size,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            c.name as category_name,
            c.distance,
            TIMESTAMPDIFF(HOUR, r.created_at, NOW()) as hours_ago
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        JOIN categories c ON r.category_id = c.id 
        ORDER BY r.created_at DESC 
        LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment method breakdown
    $payment_methods = $db->query("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(final_amount) as total_amount,
            ROUND(AVG(final_amount), 2) as avg_amount
        FROM registrations 
        WHERE payment_status != 'cancelled'
        GROUP BY payment_method 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily registration trends (last 30 days)
    $daily_trends = $db->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as registrations,
            SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as revenue,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count
        FROM registrations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // T-shirt size distribution
    $tshirt_sizes = $db->query("
        SELECT 
            t_shirt_size,
            COUNT(*) as count
        FROM registrations 
        WHERE t_shirt_size IS NOT NULL AND status != 'cancelled'
        GROUP BY t_shirt_size 
        ORDER BY FIELD(t_shirt_size, 'XS', 'S', 'M', 'L', 'XL', 'XXL')
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // System alerts and notifications
    $alerts = [];
    
    // Check for failed payments in last 24 hours
    $failed_payments_24h = $db->query("
        SELECT COUNT(*) 
        FROM registrations 
        WHERE payment_status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ")->fetchColumn();
    
    if ($failed_payments_24h > 0) {
        $alerts[] = [
            'type' => 'warning',
            'message' => "$failed_payments_24h failed payments in the last 24 hours",
            'action' => 'payments.php?status=failed'
        ];
    }
    
    // Check for pending payments older than 48 hours
    $old_pending = $db->query("
        SELECT COUNT(*) 
        FROM registrations 
        WHERE payment_status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
    ")->fetchColumn();
    
    if ($old_pending > 0) {
        $alerts[] = [
            'type' => 'info',
            'message' => "$old_pending payments pending for more than 48 hours",
            'action' => 'payments.php?status=pending&old=1'
        ];
    }
    
    // Check capacity warnings (>90% full)
    foreach ($category_stats as $category) {
        if ($category['capacity_percentage'] > 90 && $category['max_participants'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $category['name'] . " is " . $category['capacity_percentage'] . "% full",
                'action' => 'participants.php?category=' . $category['id']
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Dashboard Data Error: " . $e->getMessage());
    $category_stats = [];
    $recent_registrations = [];
    $payment_methods = [];
    $daily_trends = [];
    $tshirt_sizes = [];
    $alerts = [];
}

// Calculate additional metrics
$conversion_rate = $stats['total_registrations'] > 0 ? 
    round(($stats['paid_registrations'] / $stats['total_registrations']) * 100, 1) : 0;

$payment_success_rate = ($stats['paid_registrations'] + $stats['failed_payments']) > 0 ? 
    round(($stats['paid_registrations'] / ($stats['paid_registrations'] + $stats['failed_payments'])) * 100, 1) : 0;

// Format numbers for display
function formatNumber($number) {
    return number_format($number, 0);
}

function formatCurrency($amount) {
    return 'ZMW ' . number_format($amount, 2);
}

function getStatusBadge($status) {
    $badges = [
        'paid' => 'success',
        'pending' => 'warning', 
        'failed' => 'danger',
        'confirmed' => 'success',
        'cancelled' => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">🏃‍♂️ Dynamic Admin Dashboard</h1>
                    <p class="text-muted">Real-time Buffalo Marathon 2025 Analytics</p>
                </div>
                <div class="text-end">
                    <small class="text-muted">Last updated: <?= date('M j, Y g:i A') ?></small><br>
                    <span class="badge bg-success">Live Data</span>
                </div>
            </div>
        </div>
    </div>

    <!-- System Alerts -->
    <?php if (!empty($alerts)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h6><i class="fas fa-exclamation-triangle"></i> System Alerts</h6>
                <ul class="mb-0">
                    <?php foreach ($alerts as $alert): ?>
                    <li>
                        <a href="<?= htmlspecialchars($alert['action']) ?>" class="alert-link">
                            <?= htmlspecialchars($alert['message']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Performance Indicators -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-2">Total Registrations</h6>
                            <h3 class="text-primary mb-0"><?= formatNumber($stats['total_registrations']) ?></h3>
                            <small class="text-success">+<?= formatNumber($stats['today_registrations']) ?> today</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-2">Paid Registrations</h6>
                            <h3 class="text-success mb-0"><?= formatNumber($stats['paid_registrations']) ?></h3>
                            <small class="text-muted"><?= $conversion_rate ?>% conversion</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-2">Total Revenue</h6>
                            <h3 class="text-success mb-0"><?= formatCurrency($stats['total_revenue'] ?? 0) ?></h3>
                            <small class="text-success">+<?= formatCurrency($stats['today_revenue'] ?? 0) ?> today</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-money-bill-wave fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-2">Pending Payments</h6>
                            <h3 class="text-warning mb-0"><?= formatNumber($stats['pending_payments']) ?></h3>
                            <small class="text-muted"><?= formatCurrency($stats['pending_revenue'] ?? 0) ?> value</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-2">Average Payment</h6>
                            <h3 class="text-info mb-0"><?= formatCurrency($stats['avg_payment'] ?? 0) ?></h3>
                            <small class="text-muted">Per registration</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calculator fa-2x text-info opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted mb-2">Success Rate</h6>
                            <h3 class="text-primary mb-0"><?= $payment_success_rate ?>%</h3>
                            <small class="text-muted">Payment success</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📊 Category Performance</h5>
                    <a href="participants.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Distance</th>
                                    <th>Registrations</th>
                                    <th>Capacity</th>
                                    <th>Revenue</th>
                                    <th>Avg. Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_stats as $category): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($category['distance']) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= $category['total_registrations'] ?></span>
                                        <small class="text-success">(<?= $category['paid_registrations'] ?> paid)</small>
                                    </td>
                                    <td>
                                        <?php if ($category['max_participants']): ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar 
                                                    <?= $category['capacity_percentage'] > 90 ? 'bg-danger' : 
                                                        ($category['capacity_percentage'] > 70 ? 'bg-warning' : 'bg-success') ?>" 
                                                     style="width: <?= $category['capacity_percentage'] ?>%">
                                                    <?= $category['capacity_percentage'] ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted"><?= $category['total_registrations'] ?>/<?= $category['max_participants'] ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Unlimited</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= formatCurrency($category['revenue']) ?></strong></td>
                                    <td><?= formatCurrency($category['avg_registration_fee'] ?? 0) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">💳 Payment Methods</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($payment_methods as $method): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0"><?= ucwords(str_replace('_', ' ', $method['payment_method'])) ?></h6>
                            <small class="text-muted"><?= formatCurrency($method['avg_amount']) ?> avg</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary"><?= $method['count'] ?></span><br>
                            <small class="text-success"><?= formatCurrency($method['total_amount']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & T-Shirt Sizes -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">⏰ Recent Registrations</h5>
                    <a href="participants.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Registration #</th>
                                    <th>Participant</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $reg): ?>
                                <tr>
                                    <td>
                                        <code><?= htmlspecialchars($reg['registration_number']) ?></code>
                                        <?php if ($reg['bib_number']): ?>
                                            <br><small class="text-muted">Bib: <?= htmlspecialchars($reg['bib_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($reg['email']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($reg['category_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($reg['distance']) ?></small>
                                    </td>
                                    <td><strong><?= formatCurrency($reg['final_amount']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadge($reg['payment_status']) ?>">
                                            <?= ucfirst($reg['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($reg['hours_ago'] < 24): ?>
                                            <?= $reg['hours_ago'] ?> hours ago
                                        <?php else: ?>
                                            <?= date('M j, Y', strtotime($reg['created_at'])) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">👕 T-Shirt Sizes</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $total_tshirts = array_sum(array_column($tshirt_sizes, 'count'));
                    foreach ($tshirt_sizes as $size): 
                        $percentage = $total_tshirts > 0 ? round(($size['count'] / $total_tshirts) * 100, 1) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0">Size <?= htmlspecialchars($size['t_shirt_size']) ?></h6>
                            <small class="text-muted"><?= $percentage ?>% of total</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-info"><?= $size['count'] ?></span>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 5px;">
                        <div class="progress-bar bg-info" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">Total: <?= formatNumber($total_tshirts) ?> shirts needed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">⚡ Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="participants.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users"></i> Manage Participants
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="payments.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-credit-card"></i> Review Payments
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-chart-bar"></i> Generate Reports
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="announcements.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-bullhorn"></i> Send Announcement
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-refresh script -->
<script>
// Auto-refresh dashboard every 5 minutes
setTimeout(function() {
    window.location.reload();
}, 300000);

// Add timestamp to show last refresh
document.addEventListener('DOMContentLoaded', function() {
    setInterval(function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        document.querySelector('.text-muted').innerHTML = 'Last updated: ' + timeString;
    }, 1000);
});
</script>

<?php include '../includes/footer.php'; ?>
