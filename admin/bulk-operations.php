<?php
/**
 * Buffalo Marathon 2025 - Admin Bulk Operations
 * Advanced bulk management for participant data
 * Created: 2025-01-09
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';
require_once '../includes/cache.php';
require_once '../includes/logger.php';
require_once '../includes/rate-limiter.php';
require_once '../includes/email-templates.php';

// Require admin access
requireAdmin();

// Rate limiting for bulk operations
rate_limit_middleware('admin_bulk', 'admin_' . $_SESSION['user_id']);

// Handle bulk actions
$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Security token invalid.');
    } else {
        $results = handleBulkAction($_POST);
    }
}

function handleBulkAction($postData) {
    $action = $postData['bulk_action'];
    $selection = $postData['selection'] ?? [];
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    if (empty($selection)) {
        return ['success' => 0, 'errors' => 1, 'messages' => ['No items selected']];
    }
    
    try {
        $db = getDB();
        $db->beginTransaction();
        
        switch ($action) {
            case 'confirm_payments':
                $results = bulkConfirmPayments($db, $selection);
                break;
            case 'send_reminders':
                $results = bulkSendReminders($db, $selection);
                break;
            case 'export_selected':
                $results = bulkExportSelected($db, $selection);
                break;
            case 'mark_collected':
                $results = bulkMarkCollected($db, $selection);
                break;
            case 'update_category':
                $results = bulkUpdateCategory($db, $selection, $postData['new_category_id']);
                break;
            case 'cancel_registrations':
                $results = bulkCancelRegistrations($db, $selection);
                break;
            default:
                $results['messages'][] = 'Unknown action';
                $results['errors'] = 1;
        }
        
        if ($results['errors'] === 0) {
            $db->commit();
            log_user_activity('bulk_operation', "Bulk {$action} completed: {$results['success']} items");
        } else {
            $db->rollback();
        }
        
    } catch (Exception $e) {
        $db->rollback();
        log_error("Bulk operation failed: " . $e->getMessage(), 'admin');
        $results = ['success' => 0, 'errors' => 1, 'messages' => ['Operation failed: ' . $e->getMessage()]];
    }
    
    return $results;
}

function bulkConfirmPayments($db, $selection) {
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    foreach ($selection as $registrationId) {
        try {
            $stmt = $db->prepare("
                UPDATE registrations 
                SET payment_status = 'paid', updated_at = NOW() 
                WHERE id = ? AND payment_status IN ('pending', 'processing')
            ");
            
            if ($stmt->execute([$registrationId])) {
                $results['success']++;
                
                // Send confirmation email
                $stmt = $db->prepare("
                    SELECT u.*, r.*, c.name as category_name, c.distance as category_distance 
                    FROM registrations r 
                    JOIN users u ON r.user_id = u.id 
                    JOIN categories c ON r.category_id = c.id 
                    WHERE r.id = ?
                ");
                $stmt->execute([$registrationId]);
                $data = $stmt->fetch();
                
                if ($data) {
                    send_registration_confirmation_email($data, $data);
                }
            } else {
                $results['errors']++;
            }
        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "Error with registration {$registrationId}: " . $e->getMessage();
        }
    }
    
    return $results;
}

function bulkSendReminders($db, $selection) {
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    foreach ($selection as $registrationId) {
        try {
            $stmt = $db->prepare("
                SELECT u.*, r.*, c.name as category_name 
                FROM registrations r 
                JOIN users u ON r.user_id = u.id 
                JOIN categories c ON r.category_id = c.id 
                WHERE r.id = ? AND r.payment_status = 'pending'
            ");
            $stmt->execute([$registrationId]);
            $data = $stmt->fetch();
            
            if ($data && send_payment_reminder_email($data, $data)) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "Error sending reminder for {$registrationId}: " . $e->getMessage();
        }
    }
    
    return $results;
}

function bulkMarkCollected($db, $selection) {
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    foreach ($selection as $registrationId) {
        try {
            $stmt = $db->prepare("
                UPDATE registrations 
                SET race_pack_collected = 1, race_pack_collected_at = NOW(), race_pack_collected_by = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$_SESSION['user_email'], $registrationId])) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "Error with registration {$registrationId}: " . $e->getMessage();
        }
    }
    
    return $results;
}

function bulkUpdateCategory($db, $selection, $newCategoryId) {
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    if (!$newCategoryId) {
        $results['errors'] = count($selection);
        $results['messages'][] = 'No category selected';
        return $results;
    }
    
    foreach ($selection as $registrationId) {
        try {
            $stmt = $db->prepare("
                UPDATE registrations 
                SET category_id = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$newCategoryId, $registrationId])) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "Error with registration {$registrationId}: " . $e->getMessage();
        }
    }
    
    return $results;
}

function bulkCancelRegistrations($db, $selection) {
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    foreach ($selection as $registrationId) {
        try {
            $stmt = $db->prepare("
                UPDATE registrations 
                SET payment_status = 'cancelled', updated_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$registrationId])) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "Error with registration {$registrationId}: " . $e->getMessage();
        }
    }
    
    return $results;
}

// Get participants data with filters
$filters = [
    'category' => $_GET['category'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'collected' => $_GET['collected'] ?? ''
];

$participants = getFilteredParticipants($filters);
$categories = cache_categories();

function getFilteredParticipants($filters) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT r.*, u.first_name, u.last_name, u.email, u.phone, 
                   c.name as category_name, c.distance, c.price
            FROM registrations r
            JOIN users u ON r.user_id = u.id
            JOIN categories c ON r.category_id = c.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND r.category_id = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['payment_status'])) {
            $sql .= " AND r.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.registration_number LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(r.registered_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(r.registered_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if ($filters['collected'] !== '') {
            $sql .= " AND r.race_pack_collected = ?";
            $params[] = $filters['collected'] ? 1 : 0;
        }
        
        $sql .= " ORDER BY r.registered_at DESC LIMIT 500";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        log_error("Error filtering participants: " . $e->getMessage(), 'admin');
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Operations - Buffalo Marathon 2025 Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
        }
        
        .bg-army-green { background-color: var(--army-green) !important; }
        .text-army-green { color: var(--army-green) !important; }
        
        .bulk-actions-bar {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .selection-counter {
            font-weight: 600;
            color: var(--army-green);
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: var(--army-green);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .badge-paid { background-color: #28a745; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-cancelled { background-color: #dc3545; }
        
        .btn-bulk-action {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-army-green">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/admin/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon Admin
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/admin/">Dashboard</a>
                <a class="nav-link" href="/admin/participants.php">Participants</a>
                <a class="nav-link active" href="/admin/bulk-operations.php">Bulk Operations</a>
                <a class="nav-link" href="/admin/reports.php">Reports</a>
                <a class="nav-link" href="/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 text-army-green"><i class="fas fa-tasks me-2"></i>Bulk Operations</h1>
                    <div class="selection-counter">
                        <span id="selection-count">0</span> participants selected
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php if (!empty($results)): ?>
                    <div class="alert alert-<?php echo $results['errors'] > 0 ? 'warning' : 'success'; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $results['errors'] > 0 ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                        <strong>Operation completed:</strong> <?php echo $results['success']; ?> successful, <?php echo $results['errors']; ?> errors
                        <?php if (!empty($results['messages'])): ?>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($results['messages'] as $message): ?>
                                    <li><?php echo htmlspecialchars($message); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $filters['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $filters['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="cancelled" <?php echo $filters['payment_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Race Pack</label>
                                <select name="collected" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $filters['collected'] === '1' ? 'selected' : ''; ?>>Collected</option>
                                    <option value="0" <?php echo $filters['collected'] === '0' ? 'selected' : ''; ?>>Not Collected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Name, email, number..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                                <a href="/admin/bulk-operations.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar" id="bulk-actions-bar" style="display: none;">
                    <form method="POST" id="bulk-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Bulk Actions</label>
                                <select name="bulk_action" class="form-select" required>
                                    <option value="">Select Action</option>
                                    <option value="confirm_payments">Confirm Payments</option>
                                    <option value="send_reminders">Send Payment Reminders</option>
                                    <option value="mark_collected">Mark Race Pack Collected</option>
                                    <option value="update_category">Update Category</option>
                                    <option value="cancel_registrations">Cancel Registrations</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="category-selector" style="display: none;">
                                <label class="form-label">New Category</label>
                                <select name="new_category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success btn-bulk-action">
                                    <i class="fas fa-play me-2"></i>Execute Action
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                                    <i class="fas fa-times me-2"></i>Clear Selection
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Participants Table -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th>Registration #</th>
                                <th>Participant</th>
                                <th>Category</th>
                                <th>Payment Status</th>
                                <th>Amount</th>
                                <th>Race Pack</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No participants found matching your criteria</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($participants as $participant): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selection[]" value="<?php echo $participant['id']; ?>" class="form-check-input participant-checkbox">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($participant['registration_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($participant['email']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?php echo htmlspecialchars($participant['category_name']); ?></span>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($participant['distance']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $participant['payment_status']; ?>">
                                                <?php echo ucfirst($participant['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo formatCurrency($participant['price']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($participant['race_pack_collected']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Collected
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo formatDateTime($participant['registered_at']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($participants)): ?>
                    <div class="mt-3 text-muted">
                        Showing <?php echo count($participants); ?> participants (limited to 500 results)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bulk operations functionality
        const selectAllCheckbox = document.getElementById('select-all');
        const participantCheckboxes = document.querySelectorAll('.participant-checkbox');
        const bulkActionsBar = document.getElementById('bulk-actions-bar');
        const selectionCount = document.getElementById('selection-count');
        const bulkActionSelect = document.querySelector('select[name="bulk_action"]');
        const categorySelector = document.getElementById('category-selector');

        // Select all functionality
        selectAllCheckbox.addEventListener('change', function() {
            participantCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelection();
        });

        // Individual checkbox functionality
        participantCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelection);
        });

        // Show/hide category selector based on action
        bulkActionSelect.addEventListener('change', function() {
            if (this.value === 'update_category') {
                categorySelector.style.display = 'block';
            } else {
                categorySelector.style.display = 'none';
            }
        });

        function updateSelection() {
            const checkedBoxes = document.querySelectorAll('.participant-checkbox:checked');
            const count = checkedBoxes.length;
            
            selectionCount.textContent = count;
            
            if (count > 0) {
                bulkActionsBar.style.display = 'block';
            } else {
                bulkActionsBar.style.display = 'none';
            }
            
            // Update select all checkbox state
            if (count === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (count === participantCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }
        }

        function clearSelection() {
            participantCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            updateSelection();
        }

        // Confirm dangerous actions
        document.getElementById('bulk-form').addEventListener('submit', function(e) {
            const action = bulkActionSelect.value;
            const count = document.querySelectorAll('.participant-checkbox:checked').length;
            
            if (['cancel_registrations', 'confirm_payments'].includes(action)) {
                const actionText = action === 'cancel_registrations' ? 'cancel' : 'confirm payments for';
                if (!confirm(`Are you sure you want to ${actionText} ${count} registrations? This action cannot be undone.`)) {
                    e.preventDefault();
                }
            }
        });

        // Initialize selection state
        updateSelection();
    </script>
</body>
</html>