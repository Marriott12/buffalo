<?php
/**
 * Buffalo Marathon 2025 - Admin Payments Management
 * Production Ready - 2025-08-08 13:59:46 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Security token invalid.');
    } else {
        $action = $_POST['action'];
        $registration_id = (int)($_POST['registration_id'] ?? 0);
        
        try {
            $db = getDB();
            
            switch ($action) {
                case 'confirm_payment':
                    $db->beginTransaction();
                    
                    // Update registration
                    $stmt = $db->prepare("UPDATE registrations SET payment_status = 'confirmed', payment_date = NOW() WHERE id = ?");
                    $stmt->execute([$registration_id]);
                    
                    // Log payment
                    $stmt = $db->prepare("UPDATE payment_logs SET status = 'confirmed', confirmed_at = NOW() WHERE registration_id = ?");
                    $stmt->execute([$registration_id]);
                    
                    $db->commit();
                    setFlashMessage('success', 'Payment confirmed successfully.');
                    break;
                    
                case 'reject_payment':
                    $reason = sanitizeInput($_POST['reason'] ?? '');
                    $db->beginTransaction();
                    
                    $stmt = $db->prepare("UPDATE registrations SET payment_status = 'failed' WHERE id = ?");
                    $stmt->execute([$registration_id]);
                    
                    $stmt = $db->prepare("UPDATE payment_logs SET status = 'failed', notes = ? WHERE registration_id = ?");
                    $stmt->execute([$reason, $registration_id]);
                    
                    $db->commit();
                    setFlashMessage('success', 'Payment rejected.');
                    break;
            }
            
            logActivity('admin_payment_action', "Payment action: {$action} for registration {$registration_id}");
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            setFlashMessage('error', 'Action failed. Please try again.');
            error_log("Admin payment action error: " . $e->getMessage());
        }
    }
    
    redirectTo('/admin/payments.php');
}

// Get payments with filters
$status_filter = $_GET['status'] ?? 'pending';
$method_filter = $_GET['method'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["r.payment_status != 'cancelled'"];
$params = [];

if ($status_filter) {
    $where_conditions[] = "r.payment_status = ?";
    $params[] = $status_filter;
}

if ($method_filter) {
    $where_conditions[] = "r.payment_method = ?";
    $params[] = $method_filter;
}

if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR r.registration_number LIKE ? OR r.payment_reference LIKE ?)";
    $search_term = "%{$search}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get payments
$payments = [];
$total_count = 0;
try {
    $db = getDB();
    
    // Count total
    $count_query = "
        SELECT COUNT(*) 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE {$where_clause}
    ";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_count = $stmt->fetchColumn();
    
    // Get payments
    $query = "
        SELECT r.*, u.first_name, u.last_name, u.email, c.name as category_name
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN categories c ON r.category_id = c.id
        WHERE {$where_clause}
        ORDER BY r.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
    
    // Get payment summary
    $stmt = $db->query("
        SELECT 
            payment_status,
            COUNT(*) as count,
            SUM(payment_amount) as total_amount
        FROM registrations 
        WHERE payment_status != 'cancelled'
        GROUP BY payment_status
    ");
    $payment_summary = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    error_log("Payments fetch error: " . $e->getMessage());
}

$total_pages = ceil($total_count / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Buffalo Marathon 2025</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
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
        
        .payment-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .summary-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            border-color: var(--army-green);
            transform: translateY(-3px);
        }
        
        .summary-number {
            font-size: 2rem;
            font-weight: 900;
            color: var(--army-green);
        }
        
        .payments-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: var(--army-green);
            color: white;
            border: none;
            font-weight: 600;
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
        
        .btn-sm-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
                            <a class="nav-link active" href="/admin/payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
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
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm">
                    <div>
                        <h4 class="text-army-green fw-bold mb-0">Payment Management</h4>
                        <small class="text-muted">Process and track marathon registration payments</small>
                    </div>
                    <div class="text-end">
                        <div class="text-army-green fw-bold"><?php echo number_format($total_count); ?> Total Payments</div>
                        <small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                </div>
                
                <!-- Flash Messages -->
                <?php
                $flash_messages = getAllFlashMessages();
                foreach ($flash_messages as $type => $message):
                    $alert_class = match($type) {
                        'success' => 'alert-success',
                        'error' => 'alert-danger',
                        'warning' => 'alert-warning',
                        default => 'alert-info'
                    };
                ?>
                    <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show mx-4">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
                
                <!-- Content -->
                <div class="p-4">
                    <!-- Payment Summary -->
                    <div class="payment-summary">
                        <h5 class="text-army-green mb-4">Payment Summary</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="summary-card">
                                    <div class="summary-number"><?php echo $payment_summary['pending'] ?? 0; ?></div>
                                    <div class="text-muted">Pending</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card">
                                    <div class="summary-number"><?php echo $payment_summary['confirmed'] ?? 0; ?></div>
                                    <div class="text-muted">Confirmed</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card">
                                    <div class="summary-number"><?php echo $payment_summary['failed'] ?? 0; ?></div>
                                    <div class="text-muted">Failed</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card">
                                    <div class="summary-number"><?php echo formatCurrency(array_sum($payment_summary) * 500); ?></div>
                                    <div class="text-muted">Total Revenue</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="bg-white rounded p-3 mb-4 shadow-sm">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Name, email, or reference">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="method">
                                    <option value="">All Methods</option>
                                    <option value="mobile_money" <?php echo $method_filter === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="cash" <?php echo $method_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-army-green">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    <a href="/admin/payments.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Payments Table -->
                    <div class="payments-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Participant</th>
                                        <th>Registration #</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-army-green"><?php echo htmlspecialchars($payment['registration_number']); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold"><?php echo htmlspecialchars($payment['category_name']); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo formatCurrency($payment['payment_amount']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($payment['payment_reference']): ?>
                                                    <code><?php echo htmlspecialchars($payment['payment_reference']); ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                                    <?php echo ucfirst($payment['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo formatDateTime($payment['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($payment['payment_status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="confirm_payment">
                                                            <input type="hidden" name="registration_id" value="<?php echo $payment['id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm-icon" 
                                                                    title="Confirm Payment"
                                                                    onclick="return confirm('Confirm this payment?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <button class="btn btn-danger btn-sm-icon" 
                                                                title="Reject Payment"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal<?php echo $payment['id']; ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-primary btn-sm-icon" 
                                                            title="View Details"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#paymentModal<?php echo $payment['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Payment Details Modal -->
                                        <div class="modal fade" id="paymentModal<?php echo $payment['id']; ?>">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-army-green text-white">
                                                        <h5 class="modal-title">Payment Details</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <h6 class="text-army-green">Participant Information</h6>
                                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($payment['email']); ?></p>
                                                                <p><strong>Registration #:</strong> <?php echo htmlspecialchars($payment['registration_number']); ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="text-army-green">Payment Information</h6>
                                                                <p><strong>Amount:</strong> <?php echo formatCurrency($payment['payment_amount']); ?></p>
                                                                <p><strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                                                                <p><strong>Reference:</strong> <?php echo htmlspecialchars($payment['payment_reference'] ?: 'Not provided'); ?></p>
                                                                <p><strong>Status:</strong> 
                                                                    <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                                                        <?php echo ucfirst($payment['payment_status']); ?>
                                                                    </span>
                                                                </p>
                                                                <p><strong>Created:</strong> <?php echo formatDateTime($payment['created_at']); ?></p>
                                                                <?php if ($payment['payment_date']): ?>
                                                                    <p><strong>Confirmed:</strong> <?php echo formatDateTime($payment['payment_date']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Reject Payment Modal -->
                                        <?php if ($payment['payment_status'] === 'pending'): ?>
                                            <div class="modal fade" id="rejectModal<?php echo $payment['id']; ?>">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Reject Payment</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="action" value="reject_payment">
                                                                <input type="hidden" name="registration_id" value="<?php echo $payment['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="reason<?php echo $payment['id']; ?>" class="form-label">Reason for rejection</label>
                                                                    <textarea class="form-control" id="reason<?php echo $payment['id']; ?>" name="reason" 
                                                                              rows="3" placeholder="Please provide a reason for rejecting this payment"></textarea>
                                                                </div>
                                                                
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                                    This action will mark the payment as failed and notify the participant.
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Reject Payment</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-dismiss flash messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>