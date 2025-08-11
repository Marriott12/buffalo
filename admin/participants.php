<?php
/**
 * Buffalo Marathon 2025 - Admin Participants Management
 * Production Ready - 2025-08-08 13:41:45 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Handle actions
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
                    $stmt = $db->prepare("UPDATE registrations SET payment_status = 'confirmed', payment_date = NOW() WHERE id = ?");
                    $stmt->execute([$registration_id]);
                    setFlashMessage('success', 'Payment confirmed successfully.');
                    break;
                    
                case 'mark_collected':
                    $stmt = $db->prepare("UPDATE registrations SET race_pack_collected = 1 WHERE id = ?");
                    $stmt->execute([$registration_id]);
                    setFlashMessage('success', 'Race pack marked as collected.');
                    break;
                    
                case 'cancel_registration':
                    $stmt = $db->prepare("UPDATE registrations SET payment_status = 'cancelled', status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$registration_id]);
                    setFlashMessage('success', 'Registration cancelled.');
                    break;
            }
            
            logActivity('admin_action', "Admin action: {$action} for registration {$registration_id}");
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Action failed. Please try again.');
            error_log("Admin action error: " . $e->getMessage());
        }
    }
    
    redirectTo('/admin/participants.php');
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
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

if ($category_filter) {
    $where_conditions[] = "r.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.registration_number LIKE ?)";
    $search_term = "%{$search}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get participants
$participants = [];
$total_count = 0;
try {
    $db = getDB();
    
    // Count total
    $count_query = "
        SELECT COUNT(*) 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        JOIN categories c ON r.category_id = c.id 
        WHERE {$where_clause}
    ";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_count = $stmt->fetchColumn();
    
    // Get participants
    $query = "
        SELECT r.*, u.first_name, u.last_name, u.email, u.phone, u.date_of_birth, 
               c.name as category_name, c.distance
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN categories c ON r.category_id = c.id
        WHERE {$where_clause}
        ORDER BY r.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $participants = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Participants fetch error: " . $e->getMessage());
}

$total_pages = ceil($total_count / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants Management - Buffalo Marathon 2025</title>
    
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
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .participants-table {
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
        .status-cancelled { background: #e2e3e5; color: #6c757d; }
        
        .btn-sm-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .text-army-green { color: var(--army-green) !important; }
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
                            <a class="nav-link active" href="/admin/participants.php">
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
                        <h4 class="text-army-green fw-bold mb-0">Participants Management</h4>
                        <small class="text-muted">Manage registrations, payments, and race pack collection</small>
                    </div>
                    <div class="text-end">
                        <div class="text-army-green fw-bold"><?php echo number_format($total_count); ?> Total Participants</div>
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
                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Name, email, or registration number">
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
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-army-green">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    <a href="/admin/participants.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Participants Table -->
                    <div class="participants-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Participant</th>
                                        <th>Registration #</th>
                                        <th>Category</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participants as $participant): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($participant['email']); ?></small><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($participant['phone']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-army-green"><?php echo htmlspecialchars($participant['registration_number']); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($participant['category_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($participant['distance']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo formatCurrency($participant['payment_amount']); ?></div>
                                                    <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $participant['payment_method'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="status-badge status-<?php echo $participant['payment_status']; ?>">
                                                        <?php echo ucfirst($participant['payment_status']); ?>
                                                    </span>
                                                    <?php if ($participant['race_pack_collected']): ?>
                                                        <br><small class="text-success"><i class="fas fa-check me-1"></i>Pack Collected</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo formatDateTime($participant['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($participant['payment_status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="confirm_payment">
                                                            <input type="hidden" name="registration_id" value="<?php echo $participant['id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm-icon" 
                                                                    title="Confirm Payment"
                                                                    onclick="return confirm('Confirm payment for this registration?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($participant['payment_status'] === 'confirmed' && !$participant['race_pack_collected']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="mark_collected">
                                                            <input type="hidden" name="registration_id" value="<?php echo $participant['id']; ?>">
                                                            <button type="submit" class="btn btn-info btn-sm-icon" 
                                                                    title="Mark Pack Collected"
                                                                    onclick="return confirm('Mark race pack as collected?')">
                                                                <i class="fas fa-box"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-primary btn-sm-icon" 
                                                            title="View Details"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#participantModal<?php echo $participant['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($participant['payment_status'] !== 'cancelled'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="cancel_registration">
                                                            <input type="hidden" name="registration_id" value="<?php echo $participant['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm-icon" 
                                                                    title="Cancel Registration"
                                                                    onclick="return confirm('Cancel this registration? This action cannot be undone.')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Participant Details Modal -->
                                        <div class="modal fade" id="participantModal<?php echo $participant['id']; ?>">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-army-green text-white">
                                                        <h5 class="modal-title">Participant Details</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <h6 class="text-army-green">Personal Information</h6>
                                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($participant['email']); ?></p>
                                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($participant['phone']); ?></p>
                                                                <p><strong>Date of Birth:</strong> <?php echo $participant['date_of_birth'] ? formatDate($participant['date_of_birth']) : 'Not provided'; ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="text-army-green">Registration Details</h6>
                                                                <p><strong>Registration #:</strong> <?php echo htmlspecialchars($participant['registration_number']); ?></p>
                                                                <p><strong>Category:</strong> <?php echo htmlspecialchars($participant['category_name']); ?> (<?php echo htmlspecialchars($participant['distance']); ?>)</p>
                                                                <p><strong>T-Shirt Size:</strong> <?php echo htmlspecialchars($participant['t_shirt_size']); ?></p>
                                                                <p><strong>Payment Amount:</strong> <?php echo formatCurrency($participant['payment_amount']); ?></p>
                                                                <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $participant['payment_method'])); ?></p>
                                                                <?php if ($participant['payment_reference']): ?>
                                                                    <p><strong>Payment Reference:</strong> <?php echo htmlspecialchars($participant['payment_reference']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if ($participant['dietary_restrictions'] || $participant['medical_conditions']): ?>
                                                                <div class="col-12">
                                                                    <h6 class="text-army-green">Additional Information</h6>
                                                                    <?php if ($participant['dietary_restrictions']): ?>
                                                                        <p><strong>Dietary Restrictions:</strong> <?php echo htmlspecialchars($participant['dietary_restrictions']); ?></p>
                                                                    <?php endif; ?>
                                                                    <?php if ($participant['medical_conditions']): ?>
                                                                        <p><strong>Medical Conditions:</strong> <?php echo htmlspecialchars($participant['medical_conditions']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="col-12">
                                                                <h6 class="text-army-green">Status</h6>
                                                                <p>
                                                                    <span class="status-badge status-<?php echo $participant['payment_status']; ?>">
                                                                        <?php echo ucfirst($participant['payment_status']); ?>
                                                                    </span>
                                                                    <?php if ($participant['race_pack_collected']): ?>
                                                                        <span class="badge bg-success ms-2">Race Pack Collected</span>
                                                                    <?php endif; ?>
                                                                </p>
                                                                <p><strong>Registered:</strong> <?php echo formatDateTime($participant['created_at']); ?></p>
                                                                <?php if ($participant['payment_date']): ?>
                                                                    <p><strong>Payment Date:</strong> <?php echo formatDateTime($participant['payment_date']); ?></p>
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