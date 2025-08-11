<?php
/**
 * Buffalo Marathon 2025 - Admin Reports System
 * Production Ready - 2025-08-08 14:03:53 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Handle report generation
$report_type = $_GET['type'] ?? 'overview';
$export_format = $_GET['export'] ?? '';

// Generate reports based on type
$report_data = [];
try {
    $db = getDB();
    
    switch ($report_type) {
        case 'overview':
            $report_data = generateOverviewReport($db);
            break;
        case 'registrations':
            $report_data = generateRegistrationsReport($db);
            break;
        case 'payments':
            $report_data = generatePaymentsReport($db);
            break;
        case 'categories':
            $report_data = generateCategoriesReport($db);
            break;
        case 'demographics':
            $report_data = generateDemographicsReport($db);
            break;
    }
    
    // Handle export
    if ($export_format) {
        handleExport($report_data, $report_type, $export_format);
    }
    
} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
    $report_data = ['error' => 'Failed to generate report'];
}

function generateOverviewReport($db) {
    $data = [];
    
    // Basic statistics
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT r.id) as total_registrations,
            COUNT(DISTINCT CASE WHEN r.payment_status = 'confirmed' THEN r.id END) as confirmed_registrations,
            SUM(CASE WHEN r.payment_status = 'confirmed' THEN r.payment_amount ELSE 0 END) as total_revenue,
            COUNT(DISTINCT CASE WHEN r.payment_status = 'pending' THEN r.id END) as pending_payments
        FROM users u 
        LEFT JOIN registrations r ON u.id = r.user_id AND r.payment_status != 'cancelled'
    ");
    $data['overview'] = $stmt->fetch();
    
    // Registration trend (last 30 days)
    $stmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM registrations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND payment_status != 'cancelled'
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $data['trend'] = $stmt->fetchAll();
    
    // Category breakdown
    $stmt = $db->query("
        SELECT c.name, COUNT(r.id) as registrations, SUM(r.payment_amount) as revenue
        FROM categories c
        LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status = 'confirmed'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY registrations DESC
    ");
    $data['categories'] = $stmt->fetchAll();
    
    return $data;
}

function generateRegistrationsReport($db) {
    $data = [];
    
    // All registrations with user details
    $stmt = $db->query("
        SELECT 
            r.registration_number,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            c.name as category,
            r.t_shirt_size,
            r.payment_status,
            r.payment_amount,
            r.created_at
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN categories c ON r.category_id = c.id
        WHERE r.payment_status != 'cancelled'
        ORDER BY r.created_at DESC
    ");
    $data['registrations'] = $stmt->fetchAll();
    
    return $data;
}

function generatePaymentsReport($db) {
    $data = [];
    
    // Payment summary
    $stmt = $db->query("
        SELECT 
            payment_method,
            payment_status,
            COUNT(*) as count,
            SUM(payment_amount) as total_amount
        FROM registrations 
        WHERE payment_status != 'cancelled'
        GROUP BY payment_method, payment_status
        ORDER BY payment_method, payment_status
    ");
    $data['payment_summary'] = $stmt->fetchAll();
    
    // Daily revenue
    $stmt = $db->query("
        SELECT 
            DATE(payment_date) as date,
            COUNT(*) as transactions,
            SUM(payment_amount) as revenue
        FROM registrations 
        WHERE payment_status = 'confirmed'
        AND payment_date IS NOT NULL
        GROUP BY DATE(payment_date)
        ORDER BY date DESC
        LIMIT 30
    ");
    $data['daily_revenue'] = $stmt->fetchAll();
    
    return $data;
}

function generateCategoriesReport($db) {
    $data = [];
    
    // Category performance
    $stmt = $db->query("
        SELECT 
            c.name,
            c.distance,
            c.price,
            c.max_participants,
            COUNT(r.id) as registrations,
            COUNT(CASE WHEN r.payment_status = 'confirmed' THEN 1 END) as confirmed,
            SUM(CASE WHEN r.payment_status = 'confirmed' THEN r.payment_amount ELSE 0 END) as revenue,
            ROUND(COUNT(r.id) / NULLIF(c.max_participants, 0) * 100, 2) as fill_percentage
        FROM categories c
        LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status != 'cancelled'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY registrations DESC
    ");
    $data['categories'] = $stmt->fetchAll();
    
    return $data;
}

function generateDemographicsReport($db) {
    $data = [];
    
    // Age distribution
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN YEAR(CURDATE()) - YEAR(u.date_of_birth) < 18 THEN 'Under 18'
                WHEN YEAR(CURDATE()) - YEAR(u.date_of_birth) BETWEEN 18 AND 29 THEN '18-29'
                WHEN YEAR(CURDATE()) - YEAR(u.date_of_birth) BETWEEN 30 AND 39 THEN '30-39'
                WHEN YEAR(CURDATE()) - YEAR(u.date_of_birth) BETWEEN 40 AND 49 THEN '40-49'
                WHEN YEAR(CURDATE()) - YEAR(u.date_of_birth) BETWEEN 50 AND 59 THEN '50-59'
                ELSE '60+'
            END as age_group,
            COUNT(*) as count
        FROM users u
        JOIN registrations r ON u.id = r.user_id
        WHERE r.payment_status = 'confirmed'
        AND u.date_of_birth IS NOT NULL
        GROUP BY age_group
        ORDER BY age_group
    ");
    $data['age_distribution'] = $stmt->fetchAll();
    
    // Gender distribution
    $stmt = $db->query("
        SELECT 
            COALESCE(u.gender, 'Not specified') as gender,
            COUNT(*) as count
        FROM users u
        JOIN registrations r ON u.id = r.user_id
        WHERE r.payment_status = 'confirmed'
        GROUP BY u.gender
    ");
    $data['gender_distribution'] = $stmt->fetchAll();
    
    return $data;
}

function handleExport($data, $type, $format) {
    if ($format === 'csv') {
        exportCSV($data, $type);
    } elseif ($format === 'json') {
        exportJSON($data, $type);
    }
}

function exportCSV($data, $type) {
    $filename = "buffalo_marathon_2025_{$type}_" . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    
    $output = fopen('php://output', 'w');
    
    if ($type === 'registrations' && isset($data['registrations'])) {
        // CSV headers
        fputcsv($output, [
            'Registration Number', 'First Name', 'Last Name', 'Email', 'Phone',
            'Category', 'T-Shirt Size', 'Payment Status', 'Amount', 'Registration Date'
        ]);
        
        // CSV data
        foreach ($data['registrations'] as $row) {
            fputcsv($output, [
                $row['registration_number'],
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['phone'],
                $row['category'],
                $row['t_shirt_size'],
                $row['payment_status'],
                $row['payment_amount'],
                $row['created_at']
            ]);
        }
    }
    
    fclose($output);
    exit;
}

function exportJSON($data, $type) {
    $filename = "buffalo_marathon_2025_{$type}_" . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Buffalo Marathon 2025</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    
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
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
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
        
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--army-green);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            color: var(--army-green);
        }
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
                            <a class="nav-link" href="/admin/">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/participants.php">
                                <i class="fas fa-users me-2"></i>Participants
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin/reports.php">
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
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm">
                    <div>
                        <h4 class="text-army-green fw-bold mb-0">Reports & Analytics</h4>
                        <small class="text-muted">Comprehensive event insights and data analysis</small>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-army-green dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i>Export Report
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?type=<?php echo $report_type; ?>&export=csv">
                                <i class="fas fa-file-csv me-2"></i>CSV Format
                            </a></li>
                            <li><a class="dropdown-item" href="?type=<?php echo $report_type; ?>&export=json">
                                <i class="fas fa-file-code me-2"></i>JSON Format
                            </a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-4">
                    <!-- Report Navigation -->
                    <div class="mb-4">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $report_type === 'overview' ? 'active' : ''; ?>" 
                                   href="?type=overview">Overview</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $report_type === 'registrations' ? 'active' : ''; ?>" 
                                   href="?type=registrations">Registrations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $report_type === 'payments' ? 'active' : ''; ?>" 
                                   href="?type=payments">Payments</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $report_type === 'categories' ? 'active' : ''; ?>" 
                                   href="?type=categories">Categories</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $report_type === 'demographics' ? 'active' : ''; ?>" 
                                   href="?type=demographics">Demographics</a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Report Content -->
                    <?php if ($report_type === 'overview' && isset($report_data['overview'])): ?>
                        <!-- Overview Report -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo number_format($report_data['overview']['total_users']); ?></div>
                                    <div class="text-muted">Total Users</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo number_format($report_data['overview']['total_registrations']); ?></div>
                                    <div class="text-muted">Total Registrations</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo formatCurrency($report_data['overview']['total_revenue']); ?></div>
                                    <div class="text-muted">Total Revenue</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo number_format($report_data['overview']['pending_payments']); ?></div>
                                    <div class="text-muted">Pending Payments</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="report-card">
                                    <h5 class="text-army-green mb-4">Registration Trend (Last 30 Days)</h5>
                                    <div class="chart-container">
                                        <canvas id="trendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="report-card">
                                    <h5 class="text-army-green mb-4">Category Breakdown</h5>
                                    <div class="chart-container">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    