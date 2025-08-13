<?php
/**
 * Buffalo Marathon 2025 - Advanced Analytics Dashboard
 * Detailed analytics and insights for administrators
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

class AnalyticsEngine {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get registration conversion funnel
     */
    public function getRegistrationFunnel() {
        try {
            // Website visits (if we had tracking)
            $website_visits = 1000; // Placeholder
            
            // User registrations
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'participant'");
            $user_registrations = $stmt->fetchColumn();
            
            // Marathon registrations started
            $stmt = $this->db->query("SELECT COUNT(*) FROM registrations");
            $marathon_started = $stmt->fetchColumn();
            
            // Completed payments
            $stmt = $this->db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'confirmed'");
            $payments_completed = $stmt->fetchColumn();
            
            return [
                'website_visits' => $website_visits,
                'user_registrations' => $user_registrations,
                'marathon_started' => $marathon_started,
                'payments_completed' => $payments_completed,
                'conversion_rates' => [
                    'visit_to_signup' => round(($user_registrations / $website_visits) * 100, 2),
                    'signup_to_register' => round(($marathon_started / $user_registrations) * 100, 2),
                    'register_to_pay' => round(($payments_completed / $marathon_started) * 100, 2),
                    'overall' => round(($payments_completed / $website_visits) * 100, 2)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Funnel analysis error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get geographic distribution
     */
    public function getGeographicDistribution() {
        try {
            $stmt = $this->db->query("
                SELECT city, COUNT(*) as count
                FROM users u
                JOIN registrations r ON u.id = r.user_id
                WHERE r.payment_status = 'confirmed'
                GROUP BY city
                ORDER BY count DESC
                LIMIT 20
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Geographic analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get registration trends over time
     */
    public function getRegistrationTrends($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT DATE(created_at) as date, 
                       COUNT(*) as registrations,
                       SUM(CASE WHEN payment_status = 'confirmed' THEN 1 ELSE 0 END) as paid_registrations
                FROM registrations 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Trends analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment method preferences
     */
    public function getPaymentMethodAnalysis() {
        try {
            $stmt = $this->db->query("
                SELECT payment_method, 
                       COUNT(*) as count,
                       SUM(amount) as total_amount,
                       AVG(amount) as avg_amount
                FROM registrations 
                WHERE payment_status = 'confirmed'
                GROUP BY payment_method
                ORDER BY count DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Payment analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get age demographics
     */
    public function getAgeDemographics() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Under 18'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 55 THEN '46-55'
                        ELSE '55+'
                    END as age_group,
                    COUNT(*) as count
                FROM users u
                JOIN registrations r ON u.id = r.user_id
                WHERE r.payment_status = 'confirmed'
                AND u.date_of_birth IS NOT NULL
                GROUP BY age_group
                ORDER BY count DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Age demographics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category performance metrics
     */
    public function getCategoryPerformance() {
        try {
            $stmt = $this->db->query("
                SELECT c.name, c.max_participants, c.price,
                       COUNT(r.id) as registrations,
                       SUM(CASE WHEN r.payment_status = 'confirmed' THEN 1 ELSE 0 END) as paid_registrations,
                       ROUND((COUNT(CASE WHEN r.payment_status = 'confirmed' THEN r.id END) / c.max_participants) * 100, 2) as fill_rate,
                       SUM(CASE WHEN r.payment_status = 'confirmed' THEN r.amount ELSE 0 END) as revenue
                FROM categories c
                LEFT JOIN registrations r ON c.id = r.category_id
                GROUP BY c.id, c.name, c.max_participants, c.price
                ORDER BY fill_rate DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Category performance error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get peak registration times
     */
    public function getPeakRegistrationTimes() {
        try {
            $stmt = $this->db->query("
                SELECT HOUR(created_at) as hour, COUNT(*) as registrations
                FROM registrations
                GROUP BY HOUR(created_at)
                ORDER BY registrations DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Peak times analysis error: " . $e->getMessage());
            return [];
        }
    }
}

$analytics = new AnalyticsEngine();
$funnel = $analytics->getRegistrationFunnel();
$geographic = $analytics->getGeographicDistribution();
$trends = $analytics->getRegistrationTrends();
$payment_methods = $analytics->getPaymentMethodAnalysis();
$age_demographics = $analytics->getAgeDemographics();
$category_performance = $analytics->getCategoryPerformance();
$peak_times = $analytics->getPeakRegistrationTimes();

$page_title = 'Advanced Analytics';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Advanced Analytics</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="exportAnalytics()">
                <i class="fas fa-download"></i> Export Report
            </button>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Registration Funnel -->
    <?php if ($funnel): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-funnel-dollar"></i> Registration Conversion Funnel</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="funnel-step">
                                <h3 class="text-primary"><?php echo number_format($funnel['website_visits']); ?></h3>
                                <p class="mb-0">Website Visits</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="funnel-step">
                                <h3 class="text-info"><?php echo number_format($funnel['user_registrations']); ?></h3>
                                <p class="mb-1">User Signups</p>
                                <small class="text-muted"><?php echo $funnel['conversion_rates']['visit_to_signup']; ?>% conversion</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="funnel-step">
                                <h3 class="text-warning"><?php echo number_format($funnel['marathon_started']); ?></h3>
                                <p class="mb-1">Marathon Registrations</p>
                                <small class="text-muted"><?php echo $funnel['conversion_rates']['signup_to_register']; ?>% conversion</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="funnel-step">
                                <h3 class="text-success"><?php echo number_format($funnel['payments_completed']); ?></h3>
                                <p class="mb-1">Completed Payments</p>
                                <small class="text-muted"><?php echo $funnel['conversion_rates']['register_to_pay']; ?>% conversion</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="mb-0"><strong>Overall Conversion Rate: <?php echo $funnel['conversion_rates']['overall']; ?>%</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Category Performance -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Category Performance</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Method Preferences</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Trends -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Registration Trends (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Demographics and Geography -->
    <div class="row g-4">
        <!-- Age Demographics -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Age Demographics</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($age_demographics as $group): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo htmlspecialchars($group['age_group']); ?></span>
                        <span class="badge bg-primary"><?php echo $group['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Geographic Distribution -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Geographic Distribution</h5>
                </div>
                <div class="card-body">
                    <?php foreach (array_slice($geographic, 0, 10) as $location): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo htmlspecialchars($location['city']); ?></span>
                        <span class="badge bg-success"><?php echo $location['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Performance Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($category_performance, 'name')); ?>,
        datasets: [{
            label: 'Fill Rate (%)',
            data: <?php echo json_encode(array_column($category_performance, 'fill_rate')); ?>,
            backgroundColor: 'rgba(75, 83, 32, 0.7)',
            borderColor: '#4B5320',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($payment_methods, 'payment_method')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($payment_methods, 'count')); ?>,
            backgroundColor: ['#4B5320', '#8F9779', '#FFD700', '#28a745', '#17a2b8']
        }]
    },
    options: {
        responsive: true
    }
});

// Registration Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($trends, 'date')); ?>,
        datasets: [{
            label: 'Total Registrations',
            data: <?php echo json_encode(array_column($trends, 'registrations')); ?>,
            borderColor: '#4B5320',
            backgroundColor: 'rgba(75, 83, 32, 0.1)',
            tension: 0.4
        }, {
            label: 'Paid Registrations',
            data: <?php echo json_encode(array_column($trends, 'paid_registrations')); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
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

function exportAnalytics() {
    alert('Analytics export feature coming soon!');
}
</script>

<style>
.funnel-step {
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 10px;
}

.funnel-step h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 10px;
}
</style>

<?php include '../includes/footer.php'; ?>
