<?php
/**
 * Buffalo Marathon 2025 - Admin Dashboard
 * Production Ready - 2025-08-08 13:38:30 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Get comprehensive statistics
$stats = [];
try {
    $db = getDB();
    
    // Basic stats
    $stats = getDBStats();
    
    // Registration by category
    $stmt = $db->query("
        SELECT c.name, c.max_participants, COUNT(r.id) as registrations,
               SUM(CASE WHEN r.payment_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed
        FROM categories c
        LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status != 'cancelled'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY registrations DESC
    ");
    $stats['by_category'] = $stmt->fetchAll();
    
    // Payment status breakdown
    $stmt = $db->query("
        SELECT payment_status, COUNT(*) as count, SUM(payment_amount) as total_amount
        FROM registrations 
        WHERE payment_status != 'cancelled'
        GROUP BY payment_status
    ");
    $stats['payment_breakdown'] = $stmt->fetchAll();
    
    // Recent registrations (last 7 days)
    $stmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM registrations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stats['recent_registrations'] = $stmt->fetchAll();
    
    // Pending payments
    $stmt = $db->query("
        SELECT COUNT(*) as count, SUM(payment_amount) as amount
        FROM registrations 
        WHERE payment_status = 'pending'
    ");
    $pending = $stmt->fetch();
    $stats['pending_payments'] = $pending['count'];
    $stats['pending_amount'] = $pending['amount'];
    
    // Recent activity
    $stmt = $db->query("
        SELECT al.*, u.first_name, u.last_name, u.email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stats['recent_activity'] = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin stats error: " . $e->getMessage());
}

$days_until_marathon = getDaysUntilMarathon();
$days_until_deadline = getDaysUntilDeadline();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Buffalo Marathon 2025</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .admin-sidebar {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            min-height: 100vh;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 4px solid var(--gold);
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
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
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid var(--army-green);
            background: white;
            border-radius: 0 10px 10px 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1edff; color: #0c5460; }
        .status-failed { background: #f8d7da; color: #721c24; }
        
        .text-army-green { color: var(--army-green) !important; }
        .bg-army-green { background-color: var(--army-green) !important; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <div class="sidebar-brand">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="fas fa-running me-2"></i>Admin Panel
                    </h5>
                    <small class="text-white-50">Buffalo Marathon 2025</small>
                </div>
                
                <nav class="sidebar-nav">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin/">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/participants.php">
                                <i class="fas fa-users me-2"></i>Participants
                                <span class="badge bg-warning ms-2"><?php echo $stats['total_registrations']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
                                <span class="badge bg-danger ms-2"><?php echo $stats['pending_payments']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="/dashboard.php">
                                <i class="fas fa-arrow-left me-2"></i>Back to Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm">
                    <div>
                        <h4 class="text-army-green fw-bold mb-0">Admin Dashboard</h4>
                        <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                    </div>
                    <div class="text-end">
                        <div class="text-army-green fw-bold"><?php echo $days_until_marathon; ?> Days Until Marathon</div>
                        <small class="text-muted"><?php echo $days_until_deadline; ?> days until registration deadline</small>
                    </div>
                </div>
                
                <!-- Dashboard Content -->
                <div class="p-4">
                    <!-- Quick Stats -->
                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stats-number"><?php echo number_format($stats['total_registrations']); ?></div>
                                        <div class="text-muted">Total Registrations</div>
                                    </div>
                                    <i class="fas fa-users fa-2x text-army-green"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stats-number"><?php echo number_format($stats['confirmed_payments']); ?></div>
                                        <div class="text-muted">Confirmed Payments</div>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stats-number"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                                        <div class="text-muted">Total Revenue</div>
                                    </div>
                                    <i class="fas fa-money-bill fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stats-number"><?php echo $stats['pending_payments']; ?></div>
                                        <div class="text-muted">Pending Payments</div>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Registration by Category Chart -->
                        <div class="col-lg-8">
                            <div class="chart-container">
                                <h5 class="text-army-green mb-4">Registration by Category</h5>
                                <canvas id="categoryChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        
                        <!-- Payment Status -->
                        <div class="col-lg-4">
                            <div class="chart-container">
                                <h5 class="text-army-green mb-4">Payment Status</h5>
                                <canvas id="paymentChart" width="300" height="300"></canvas>
                                
                                <div class="mt-4">
                                    <?php foreach ($stats['payment_breakdown'] as $payment): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                                <?php echo ucfirst($payment['payment_status']); ?>
                                            </span>
                                            <div class="text-end">
                                                <div class="fw-bold"><?php echo $payment['count']; ?></div>
                                                <small class="text-muted"><?php echo formatCurrency($payment['total_amount']); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <h5 class="text-army-green mb-4">Recent Activity</h5>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($stats['recent_activity'] as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($activity['action']); ?></div>
                                                    <?php if ($activity['description']): ?>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($activity['description']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($activity['first_name']): ?>
                                                        <div class="text-muted small">
                                                            by <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted"><?php echo formatDateTime($activity['created_at']); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="/admin/activity.php" class="btn btn-outline-army-green btn-sm">View All Activity</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Registration Trend -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <h5 class="text-army-green mb-4">Registration Trend (Last 7 Days)</h5>
                                <canvas id="trendChart" width="400" height="200"></canvas>
                                
                                <div class="mt-4">
                                    <div class="row g-3">
                                        <div class="col-6 text-center">
                                            <div class="h4 text-army-green mb-0"><?php echo count($stats['recent_registrations']); ?></div>
                                            <small class="text-muted">Active Days</small>
                                        </div>
                                        <div class="col-6 text-center">
                                            <div class="h4 text-army-green mb-0">
                                                <?php echo array_sum(array_column($stats['recent_registrations'], 'count')); ?>
                                            </div>
                                            <small class="text-muted">Total This Week</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row g-4 mt-4">
                        <div class="col-12">
                            <div class="chart-container">
                                <h5 class="text-army-green mb-4">Quick Actions</h5>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <a href="/admin/participants.php" class="btn btn-army-green w-100">
                                            <i class="fas fa-users me-2"></i>Manage Participants
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/admin/payments.php" class="btn btn-warning w-100">
                                            <i class="fas fa-credit-card me-2"></i>Process Payments
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/admin/announcements.php" class="btn btn-info w-100 text-white">
                                            <i class="fas fa-bullhorn me-2"></i>New Announcement
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/admin/reports.php" class="btn btn-success w-100">
                                            <i class="fas fa-download me-2"></i>Export Data
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    
    <script>
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($stats['by_category']); ?>;
        
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.map(item => item.name),
                datasets: [{
                    label: 'Registrations',
                    data: categoryData.map(item => item.registrations),
                    backgroundColor: '#4B5320',
                    borderColor: '#222B1F',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Payment Status Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentData = <?php echo json_encode($stats['payment_breakdown']); ?>;
        
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: paymentData.map(item => item.payment_status.charAt(0).toUpperCase() + item.payment_status.slice(1)),
                datasets: [{
                    data: paymentData.map(item => item.count),
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Registration Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php echo json_encode($stats['recent_registrations']); ?>;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Registrations',
                    data: trendData.map(item => item.count),
                    borderColor: '#4B5320',
                    backgroundColor: 'rgba(75, 83, 32, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>