<?php
/**
 * Buffalo Marathon 2025 - System Health Dashboard
 * Real-time system monitoring interface for administrators
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';
require_once '../includes/monitoring.php';
require_once '../includes/cache.php';

// Require admin access
requireAdmin();

// Get system health data
$health_data = SystemHealthMonitor::getSystemHealth();
$page_title = 'System Health Dashboard';

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">System Health Dashboard</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="refreshHealth()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <div class="badge bg-<?php echo $health_data['overall_status'] === 'healthy' ? 'success' : ($health_data['overall_status'] === 'warning' ? 'warning' : 'danger'); ?> fs-6">
                <?php echo ucfirst($health_data['overall_status']); ?>
            </div>
        </div>
    </div>

    <!-- Alert Section -->
    <?php if (!empty($health_data['alerts'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-<?php echo $health_data['overall_status'] === 'critical' ? 'danger' : 'warning'; ?>">
                <div class="card-header bg-<?php echo $health_data['overall_status'] === 'critical' ? 'danger' : 'warning'; ?> text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> System Alerts</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($health_data['alerts'] as $alert): ?>
                    <div class="alert alert-<?php echo $alert['level'] === 'critical' ? 'danger' : 'warning'; ?> mb-2">
                        <strong><?php echo ucfirst($alert['component']); ?>:</strong> 
                        <?php echo htmlspecialchars($alert['message']); ?>
                        <small class="text-muted float-end"><?php echo date('H:i:s', $alert['timestamp']); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Health Status Cards -->
    <div class="row g-3 mb-4">
        <?php foreach ($health_data['checks'] as $component => $status): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-<?php echo $status['status'] === 'healthy' ? 'success' : ($status['status'] === 'warning' ? 'warning' : 'danger'); ?>">
                <div class="card-header bg-<?php echo $status['status'] === 'healthy' ? 'success' : ($status['status'] === 'warning' ? 'warning' : 'danger'); ?> text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-<?php echo $component === 'database' ? 'database' : ($component === 'capacity' ? 'users' : ($component === 'payments' ? 'credit-card' : ($component === 'email' ? 'envelope' : 'shield-alt'))); ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $component)); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($component === 'database'): ?>
                        <p class="mb-1"><strong>Response Time:</strong> <?php echo $status['response_time_ms']; ?>ms</p>
                        <p class="mb-0"><strong>Users:</strong> <?php echo number_format($status['user_count']); ?></p>
                    <?php elseif ($component === 'capacity'): ?>
                        <p class="mb-1"><strong>Max Utilization:</strong> <?php echo $status['max_utilization']; ?>%</p>
                        <?php if (!empty($status['critical_categories'])): ?>
                        <p class="mb-0 text-danger"><strong>Critical:</strong> <?php echo implode(', ', $status['critical_categories']); ?></p>
                        <?php endif; ?>
                    <?php elseif ($component === 'payments'): ?>
                        <p class="mb-1"><strong>Recent Registrations:</strong> <?php echo $status['recent_registrations']; ?></p>
                        <p class="mb-0"><strong>Pending Payments:</strong> <?php echo $status['pending_payments']; ?></p>
                    <?php elseif ($component === 'email'): ?>
                        <p class="mb-0"><strong>Failed (Last Hour):</strong> <?php echo $status['failed_emails_last_hour']; ?></p>
                    <?php elseif ($component === 'security'): ?>
                        <p class="mb-0"><strong>Checks Passed:</strong> <?php echo $status['checks_passed']; ?>/<?php echo $status['total_checks']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Performance Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Performance Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-2"><strong>Memory Usage:</strong><br>
                            <?php echo round($health_data['performance']['memory_usage'] / 1024 / 1024, 2); ?> MB</p>
                            
                            <p class="mb-2"><strong>Memory Peak:</strong><br>
                            <?php echo round($health_data['performance']['memory_peak'] / 1024 / 1024, 2); ?> MB</p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2"><strong>Execution Time:</strong><br>
                            <?php echo round($health_data['performance']['execution_time'] * 1000, 2); ?>ms</p>
                            
                            <p class="mb-2"><strong>PHP Version:</strong><br>
                            <?php echo $health_data['performance']['php_version']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Category Utilization</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($health_data['checks']['capacity']['categories'])): ?>
                    <?php foreach ($health_data['checks']['capacity']['categories'] as $category): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                            <span><?php echo $category['utilization']; ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $category['utilization'] > 90 ? 'danger' : ($category['utilization'] > 75 ? 'warning' : 'success'); ?>" 
                                 style="width: <?php echo $category['utilization']; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $category['registered']; ?>/<?php echo $category['max_participants']; ?> participants</small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100" onclick="clearCache()">
                                <i class="fas fa-broom"></i> Clear Cache
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="backup.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-download"></i> Download Backup
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="participants.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-users"></i> Manage Participants
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="settings.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-cog"></i> System Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshHealth() {
    location.reload();
}

function clearCache() {
    if (confirm('Clear all cache? This may temporarily slow down the system.')) {
        fetch('ajax/clear-cache.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully!');
                location.reload();
            } else {
                alert('Error clearing cache: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

// Auto-refresh every 30 seconds
setInterval(function() {
    const indicator = document.querySelector('.btn-outline-secondary i');
    indicator.classList.add('fa-spin');
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>
