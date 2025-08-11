<?php
/**
 * Buffalo Marathon 2025 - Admin Settings
 * Production Ready - 2025-08-08 14:16:14 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

requireAdmin();

$errors = [];
$success = false;

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        try {
            $db = getDB();
            
            $settings = [
                'registration_open' => isset($_POST['registration_open']) ? 1 : 0,
                'early_bird_active' => isset($_POST['early_bird_active']) ? 1 : 0,
                'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                'max_registrations' => (int)($_POST['max_registrations'] ?? 2000)
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            }
            
            logActivity('settings_updated', 'Admin updated system settings');
            $success = true;
            
        } catch (Exception $e) {
            $errors[] = 'Failed to update settings.';
            error_log("Settings update error: " . $e->getMessage());
        }
    }
}

// Get current settings
$current_settings = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Settings fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Buffalo Marathon 2025</title>
    
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
                        <li class="nav-item"><a class="nav-link" href="/admin/announcements.php"><i class="fas fa-bullhorn me-2"></i>Announcements</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                        <li class="nav-item"><a class="nav-link active" href="/admin/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li class="nav-item mt-3"><a class="nav-link" href="/dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Site</a></li>
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm">
                    <div>
                        <h4 class="text-army-green fw-bold mb-0">System Settings</h4>
                        <small class="text-muted">Configure marathon registration and system preferences</small>
                    </div>
                </div>
                
                <div class="p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Settings updated successfully!</div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Registration Settings -->
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Registration Settings</h5></div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="registration_open" name="registration_open" 
                                                   <?php echo ($current_settings['registration_open'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="registration_open">Registration Open</label>
                                        </div>
                                        <small class="text-muted">Allow new registrations</small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="early_bird_active" name="early_bird_active" 
                                                   <?php echo ($current_settings['early_bird_active'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="early_bird_active">Early Bird Pricing</label>
                                        </div>
                                        <small class="text-muted">Enable early bird pricing</small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="max_registrations" class="form-label">Maximum Registrations</label>
                                    <input type="number" class="form-control" id="max_registrations" name="max_registrations" 
                                           value="<?php echo htmlspecialchars($current_settings['max_registrations'] ?? 2000); ?>">
                                    <small class="text-muted">Total maximum registrations allowed</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Settings -->
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">System Settings</h5></div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                                   <?php echo ($current_settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_notifications">Email Notifications</label>
                                        </div>
                                        <small class="text-muted">Send automatic email notifications</small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                   <?php echo ($current_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                        </div>
                                        <small class="text-muted">Put website in maintenance mode</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>