<?php
/**
 * Buffalo Marathon 2025 - Admin Announcements Management
 * Production Ready - 2025-08-08 14:16:14 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

requireAdmin();

$errors = [];
$success = false;

// Handle announcement creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $form_data = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'content' => sanitizeInput($_POST['content'] ?? ''),
            'type' => sanitizeInput($_POST['type'] ?? 'general'),
            'target_audience' => sanitizeInput($_POST['target_audience'] ?? 'all'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (empty($form_data['title'])) $errors[] = 'Title is required.';
        if (empty($form_data['content'])) $errors[] = 'Content is required.';
        
        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    INSERT INTO announcements (title, content, type, target_audience, is_active, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $form_data['title'], $form_data['content'], $form_data['type'],
                    $form_data['target_audience'], $form_data['is_active'], $_SESSION['user_id']
                ]);
                
                logActivity('announcement_created', "Created announcement: {$form_data['title']}");
                $success = true;
                
            } catch (Exception $e) {
                $errors[] = 'Failed to create announcement.';
                error_log("Announcement creation error: " . $e->getMessage());
            }
        }
    }
}

// Get announcements
$announcements = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT a.*, u.first_name, u.last_name 
        FROM announcements a 
        JOIN users u ON a.created_by = u.id 
        ORDER BY a.created_at DESC
    ");
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Announcements fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements Management - Buffalo Marathon 2025</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root { --army-green: #4B5320; --army-green-dark: #222B1F; --gold: #FFD700; }
        .admin-sidebar { background: linear-gradient(135deg, var(--army-green), var(--army-green-dark)); min-height: 100vh; }
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav .nav-link { color: rgba(255,255,255,0.8); padding: 0.75rem 1.5rem; transition: all 0.3s ease; }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-left: 4px solid var(--gold); }
        .main-content { background: #f8f9fa; min-height: 100vh; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <div class="sidebar-brand">
                    <h5 class="text-white fw-bold mb-0"><i class="fas fa-running me-2"></i>Admin Panel</h5>
                    <small class="text-white-50">Buffalo Marathon 2025</small>
                </div>
                <nav class="sidebar-nav">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="/admin/"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/participants.php"><i class="fas fa-users me-2"></i>Participants</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/payments.php"><i class="fas fa-credit-card me-2"></i>Payments</a></li>
                        <li class="nav-item"><a class="nav-link active" href="/admin/announcements.php"><i class="fas fa-bullhorn me-2"></i>Announcements</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li class="nav-item mt-3"><a class="nav-link" href="/dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Site</a></li>
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm">
                    <div>
                        <h4 class="text-army-green fw-bold mb-0">Announcements Management</h4>
                        <small class="text-muted">Create and manage event announcements</small>
                    </div>
                </div>
                
                <div class="p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Announcement created successfully!</div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Create Announcement Form -->
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">Create New Announcement</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="type" class="form-label">Type</label>
                                        <select class="form-select" id="type" name="type">
                                            <option value="general">General</option>
                                            <option value="urgent">Urgent</option>
                                            <option value="update">Update</option>
                                            <option value="reminder">Reminder</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="target_audience" class="form-label">Target Audience</label>
                                        <select class="form-select" id="target_audience" name="target_audience">
                                            <option value="all">All Users</option>
                                            <option value="registered">Registered Participants</option>
                                            <option value="unregistered">Unregistered Users</option>
                                            <option value="vip">VIP Participants</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                            <label class="form-check-label" for="is_active">Active (visible to users)</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i>Create Announcement</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Existing Announcements -->
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Existing Announcements</h5></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Audience</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($announcements as $announcement): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($announcement['title']); ?></strong></td>
                                                <td><span class="badge bg-secondary"><?php echo ucfirst($announcement['type']); ?></span></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $announcement['target_audience'])); ?></td>
                                                <td>
                                                    <?php if ($announcement['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></td>
                                                <td><?php echo formatDateTime($announcement['created_at']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $announcement['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $announcement['id']; ?>">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <small class="text-muted">Type: <?php echo ucfirst($announcement['type']); ?></small><br>
                                                                    <small class="text-muted">Audience: <?php echo ucfirst(str_replace('_', ' ', $announcement['target_audience'])); ?></small>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <small class="text-muted">Created: <?php echo formatDateTime($announcement['created_at']); ?></small><br>
                                                                    <small class="text-muted">By: <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>