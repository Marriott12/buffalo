<?php
/**
 * Buffalo Marathon 2025 - Analytics Dashboard
 * Comprehensive analytics and insights
 * Created: 2025-01-09
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';
require_once '../includes/cache.php';
require_once '../includes/logger.php';

// Require admin access
requireAdmin();

// Get analytics data
$analytics = getAnalyticsData();

function getAnalyticsData() {
    $cacheKey = 'analytics_dashboard';
    $data = cache_get($cacheKey);
    
    if ($data === null) {
        try {
            $db = getDB();
            $data = [];
            
            // Overview metrics
            $data['overview'] = getOverviewMetrics($db);
            
            // Registration trends
            $data['registration_trends'] = getRegistrationTrends($db);
            
            // Category distribution
            $data['category_distribution'] = getCategoryDistribution($db);
            
            // Payment status breakdown
            $data['payment_breakdown'] = getPaymentBreakdown($db);
            
            // Demographics
            $data['demographics'] = getDemographics($db);
            
            // Revenue analytics
            $data['revenue'] = getRevenueAnalytics($db);
            
            // Performance metrics
            $data['performance'] = getPerformanceMetrics($db);
            
            // Recent activity
            $data['recent_activity'] = getRecentActivity($db);
            
            // Cache for 5 minutes
            cache_set($cacheKey, $data, 300);
            
        } catch (Exception $e) {
            log_error("Analytics data error: " . $e->getMessage(), 'admin');
            $data = [];
        }
    }
    
    return $data;
}

function getOverviewMetrics($db) {
    $metrics = [];
    
    // Total registrations
    $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status != 'cancelled'");
    $metrics['total_registrations'] = $stmt->fetchColumn();
    
    // Confirmed registrations
    $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid'");
    $metrics['confirmed_registrations'] = $stmt->fetchColumn();
    
    // Pending payments
    $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending'");
    $metrics['pending_payments'] = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $db->query("SELECT SUM(c.price) FROM registrations r JOIN categories c ON r.category_id = c.id WHERE r.payment_status = 'paid'");
    $metrics['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Today's registrations
    $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE DATE(registered_at) = CURDATE()");
    $metrics['today_registrations'] = $stmt->fetchColumn();
    
    // Race packs collected
    $stmt = $db->query("SELECT COUNT(*) FROM registrations WHERE race_pack_collected = 1");
    $metrics['packs_collected'] = $stmt->fetchColumn();
    
    // Average registration per day
    $stmt = $db->query("
        SELECT AVG(daily_count) FROM (
            SELECT DATE(registered_at) as reg_date, COUNT(*) as daily_count
            FROM registrations 
            WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(registered_at)
        ) as daily_stats
    ");
    $metrics['avg_daily_registrations'] = round($stmt->fetchColumn() ?: 0, 1);
    
    return $metrics;
}

function getRegistrationTrends($db) {
    $stmt = $db->query("
        SELECT 
            DATE(registered_at) as date,
            COUNT(*) as registrations,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as confirmed
        FROM registrations 
        WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(registered_at)
        ORDER BY date DESC
    ");
    
    return $stmt->fetchAll();
}

function getCategoryDistribution($db) {
    $stmt = $db->query("
        SELECT 
            c.name,
            c.distance,
            c.price,
            c.max_participants,
            COUNT(r.id) as registrations,
            SUM(CASE WHEN r.payment_status = 'paid' THEN 1 ELSE 0 END) as confirmed,
            ROUND((COUNT(r.id) / c.max_participants) * 100, 1) as fill_percentage
        FROM categories c
        LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status != 'cancelled'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY registrations DESC
    ");
    
    return $stmt->fetchAll();
}

function getPaymentBreakdown($db) {
    $stmt = $db->query("
        SELECT 
            payment_status,
            COUNT(*) as count,
            SUM(c.price) as total_amount
        FROM registrations r
        JOIN categories c ON r.category_id = c.id
        GROUP BY payment_status
    ");
    
    return $stmt->fetchAll();
}

function getDemographics($db) {
    $demographics = [];
    
    // Age distribution
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN age < 18 THEN 'Under 18'
                WHEN age BETWEEN 18 AND 25 THEN '18-25'
                WHEN age BETWEEN 26 AND 35 THEN '26-35'
                WHEN age BETWEEN 36 AND 45 THEN '36-45'
                WHEN age BETWEEN 46 AND 55 THEN '46-55'
                ELSE '55+'
            END as age_group,
            COUNT(*) as count
        FROM registrations
        WHERE payment_status != 'cancelled'
        GROUP BY age_group
        ORDER BY age_group
    ");
    $demographics['age_groups'] = $stmt->fetchAll();
    
    // Gender distribution
    $stmt = $db->query("
        SELECT gender, COUNT(*) as count
        FROM registrations
        WHERE payment_status != 'cancelled'
        GROUP BY gender
    ");
    $demographics['gender'] = $stmt->fetchAll();
    
    // T-shirt sizes
    $stmt = $db->query("
        SELECT tshirt_size, COUNT(*) as count
        FROM registrations
        WHERE payment_status != 'cancelled'
        GROUP BY tshirt_size
        ORDER BY FIELD(tshirt_size, 'XS', 'S', 'M', 'L', 'XL', 'XXL')
    ");
    $demographics['tshirt_sizes'] = $stmt->fetchAll();
    
    return $demographics;
}

function getRevenueAnalytics($db) {
    $revenue = [];
    
    // Revenue by category
    $stmt = $db->query("
        SELECT 
            c.name,
            COUNT(r.id) as registrations,
            SUM(c.price) as revenue
        FROM registrations r
        JOIN categories c ON r.category_id = c.id
        WHERE r.payment_status = 'paid'
        GROUP BY c.id
        ORDER BY revenue DESC
    ");
    $revenue['by_category'] = $stmt->fetchAll();
    
    // Daily revenue trend
    $stmt = $db->query("
        SELECT 
            DATE(r.registered_at) as date,
            SUM(c.price) as revenue,
            COUNT(r.id) as registrations
        FROM registrations r
        JOIN categories c ON r.category_id = c.id
        WHERE r.payment_status = 'paid' AND r.registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(r.registered_at)
        ORDER BY date DESC
    ");
    $revenue['daily_trend'] = $stmt->fetchAll();
    
    return $revenue;
}

function getPerformanceMetrics($db) {
    $metrics = [];
    
    // Database performance
    $stmt = $db->query("SHOW TABLE STATUS");
    $tables = $stmt->fetchAll();
    $totalSize = 0;
    foreach ($tables as $table) {
        $totalSize += $table['Data_length'] + $table['Index_length'];
    }
    $metrics['database_size'] = $totalSize;
    
    // Cache hit rate (simulated)
    $metrics['cache_hit_rate'] = 85.4; // This would come from actual cache stats
    
    // Average page load time (simulated)
    $metrics['avg_page_load'] = 0.8; // seconds
    
    return $metrics;
}

function getRecentActivity($db) {
    $stmt = $db->query("
        SELECT 
            al.action,
            al.description,
            al.created_at,
            u.first_name,
            u.last_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 20
    ");
    
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Buffalo Marathon 2025 Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
        }
        
        .bg-army-green { background-color: var(--army-green) !important; }
        .text-army-green { color: var(--army-green) !important; }
        
        .metric-card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--army-green);
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .analytics-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(75, 83, 32, 0.05);
        }
        
        .progress-bar-army {
            background-color: var(--army-green);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-army-green">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/admin/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon Admin
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/admin/">Dashboard</a>
                <a class="nav-link" href="/admin/participants.php">Participants</a>
                <a class="nav-link" href="/admin/bulk-operations.php">Bulk Operations</a>
                <a class="nav-link" href="/admin/reports.php">Reports</a>
                <a class="nav-link active" href="/admin/analytics-dashboard.php">Analytics</a>
                <a class="nav-link" href="/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 text-army-green"><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</h1>
                    <div class="text-muted">
                        <i class="fas fa-clock me-1"></i>Last updated: <?php echo date('M j, Y g:i A'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Metrics -->
        <?php if (!empty($analytics['overview'])): ?>
            <div class="row g-4 mb-5">
                <div class="col-lg-3 col-md-6">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <div class="metric-value"><?php echo $analytics['overview']['total_registrations']; ?></div>
                            <div class="metric-label">Total Registrations</div>
                            <small class="text-success">
                                <i class="fas fa-arrow-up me-1"></i>
                                <?php echo $analytics['overview']['today_registrations']; ?> today
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <div class="metric-value"><?php echo $analytics['overview']['confirmed_registrations']; ?></div>
                            <div class="metric-label">Confirmed Payments</div>
                            <small class="text-info">
                                <?php echo $analytics['overview']['pending_payments']; ?> pending
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <div class="metric-value"><?php echo formatCurrency($analytics['overview']['total_revenue']); ?></div>
                            <div class="metric-label">Total Revenue</div>
                            <small class="text-primary">
                                Avg: <?php echo $analytics['overview']['avg_daily_registrations']; ?> per day
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <div class="metric-value"><?php echo $analytics['overview']['packs_collected']; ?></div>
                            <div class="metric-label">Race Packs Collected</div>
                            <small class="text-warning">
                                <?php echo $analytics['overview']['confirmed_registrations'] - $analytics['overview']['packs_collected']; ?> remaining
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Registration Trends Chart -->
            <div class="col-lg-8">
                <div class="card analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Registration Trends (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="registrationTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Distribution -->
            <div class="col-lg-4">
                <div class="card analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Category Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Details -->
            <div class="col-lg-6">
                <div class="card analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Category Performance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($analytics['category_distribution'])): ?>
                            <?php foreach ($analytics['category_distribution'] as $category): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="text-muted">
                                            <?php echo $category['registrations']; ?>/<?php echo $category['max_participants']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar progress-bar-army" 
                                             style="width: <?php echo min($category['fill_percentage'], 100); ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $category['fill_percentage']; ?>% filled 
                                        | <?php echo $category['confirmed']; ?> confirmed
                                        | <?php echo formatCurrency($category['price']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Demographics -->
            <div class="col-lg-6">
                <div class="card analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Demographics</h5>
                    </div>
                    <div class="card-body">
                        <!-- Age Groups -->
                        <?php if (!empty($analytics['demographics']['age_groups'])): ?>
                            <h6 class="text-muted mb-3">Age Groups</h6>
                            <?php foreach ($analytics['demographics']['age_groups'] as $ageGroup): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?php echo htmlspecialchars($ageGroup['age_group']); ?></span>
                                    <span class="badge bg-secondary"><?php echo $ageGroup['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Gender Distribution -->
                        <?php if (!empty($analytics['demographics']['gender'])): ?>
                            <h6 class="text-muted mb-3 mt-4">Gender Distribution</h6>
                            <?php foreach ($analytics['demographics']['gender'] as $gender): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?php echo htmlspecialchars($gender['gender']); ?></span>
                                    <span class="badge bg-info"><?php echo $gender['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Revenue Analytics -->
            <div class="col-lg-8">
                <div class="card analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-dollar-sign me-2"></i>Revenue Analytics</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4">
                <div class="card analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($analytics['recent_activity'])): ?>
                            <?php foreach ($analytics['recent_activity'] as $activity): ?>
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-bold" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($activity['description']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?php echo formatDateTime($activity['created_at'], 'M j, g:i A'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart.js configuration
        const chartColors = {
            primary: '#4B5320',
            secondary: '#222B1F',
            success: '#198754',
            info: '#0dcaf0',
            warning: '#ffc107',
            danger: '#dc3545',
            light: '#f8f9fa',
            dark: '#212529'
        };

        // Registration Trends Chart
        <?php if (!empty($analytics['registration_trends'])): ?>
        const registrationTrendsCtx = document.getElementById('registrationTrendsChart').getContext('2d');
        new Chart(registrationTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse(array_column($analytics['registration_trends'], 'date'))); ?>,
                datasets: [{
                    label: 'Total Registrations',
                    data: <?php echo json_encode(array_reverse(array_column($analytics['registration_trends'], 'registrations'))); ?>,
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primary + '20',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Confirmed',
                    data: <?php echo json_encode(array_reverse(array_column($analytics['registration_trends'], 'confirmed'))); ?>,
                    borderColor: chartColors.success,
                    backgroundColor: chartColors.success + '20',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        // Category Distribution Chart
        <?php if (!empty($analytics['category_distribution'])): ?>
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($analytics['category_distribution'], 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($analytics['category_distribution'], 'registrations')); ?>,
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.secondary,
                        chartColors.success,
                        chartColors.info,
                        chartColors.warning,
                        chartColors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>

        // Revenue Chart
        <?php if (!empty($analytics['revenue']['daily_trend'])): ?>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_reverse(array_column($analytics['revenue']['daily_trend'], 'date'))); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_reverse(array_column($analytics['revenue']['daily_trend'], 'revenue'))); ?>,
                    backgroundColor: chartColors.primary,
                    borderColor: chartColors.primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'K' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>