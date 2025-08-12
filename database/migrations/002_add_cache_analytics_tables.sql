-- Buffalo Marathon 2025 - Database Migration 002
-- Add Cache and Analytics Tables
-- Created: 2025-01-09

USE buffalo_marathon;

-- Cache table for performance optimization
CREATE TABLE cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    cache_value LONGTEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_cache_expires (expires_at),
    INDEX idx_cache_created (created_at)
);

-- Analytics tracking table
CREATE TABLE analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    event_data JSON,
    page_url VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_analytics_type (event_type),
    INDEX idx_analytics_name (event_name),
    INDEX idx_analytics_user (user_id),
    INDEX idx_analytics_created (created_at),
    INDEX idx_analytics_ip (ip_address)
);

-- Rate limiting table
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    window_start TIMESTAMP NOT NULL,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_limit (identifier, action_type),
    INDEX idx_rate_limits_identifier (identifier),
    INDEX idx_rate_limits_action (action_type),
    INDEX idx_rate_limits_window (window_start),
    INDEX idx_rate_limits_last_attempt (last_attempt)
);

-- Registration statistics cache table
CREATE TABLE registration_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    total_registrations INT NOT NULL DEFAULT 0,
    confirmed_registrations INT NOT NULL DEFAULT 0,
    pending_registrations INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_stats (category_id),
    INDEX idx_stats_category (category_id),
    INDEX idx_stats_updated (last_updated)
);

-- Email templates table
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_templates_name (template_name),
    INDEX idx_templates_active (is_active)
);

-- System monitoring table
CREATE TABLE system_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,4),
    metric_unit VARCHAR(20),
    metric_data JSON,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_monitoring_name (metric_name),
    INDEX idx_monitoring_recorded (recorded_at)
);

-- Initialize cache for all categories
INSERT INTO registration_stats (category_id, total_registrations, confirmed_registrations, pending_registrations)
SELECT 
    c.id,
    COALESCE(total.count, 0) as total_registrations,
    COALESCE(confirmed.count, 0) as confirmed_registrations,
    COALESCE(pending.count, 0) as pending_registrations
FROM categories c
LEFT JOIN (
    SELECT category_id, COUNT(*) as count 
    FROM registrations 
    WHERE payment_status != 'cancelled' 
    GROUP BY category_id
) total ON c.id = total.category_id
LEFT JOIN (
    SELECT category_id, COUNT(*) as count 
    FROM registrations 
    WHERE payment_status = 'paid' 
    GROUP BY category_id
) confirmed ON c.id = confirmed.category_id
LEFT JOIN (
    SELECT category_id, COUNT(*) as count 
    FROM registrations 
    WHERE payment_status = 'pending' 
    GROUP BY category_id
) pending ON c.id = pending.category_id
WHERE c.is_active = 1;

-- Insert default email templates
INSERT INTO email_templates (template_name, subject, body_html, body_text, variables, created_by) VALUES
('welcome', 'Welcome to Buffalo Marathon 2025!', 
'<h2>Welcome {{first_name}}!</h2><p>Thank you for joining Buffalo Marathon 2025. The marathon will take place on {{marathon_date}}.</p>', 
'Welcome {{first_name}}! Thank you for joining Buffalo Marathon 2025. The marathon will take place on {{marathon_date}}.', 
'["first_name", "marathon_date"]', 1),

('registration_confirmation', 'Registration Confirmed - Buffalo Marathon 2025', 
'<h2>Registration Confirmed!</h2><p>Hello {{first_name}}, your registration for {{category_name}} has been confirmed. Registration number: {{registration_number}}</p>', 
'Registration Confirmed! Hello {{first_name}}, your registration for {{category_name}} has been confirmed. Registration number: {{registration_number}}', 
'["first_name", "category_name", "registration_number"]', 1),

('payment_reminder', 'Payment Reminder - Buffalo Marathon 2025', 
'<h2>Payment Reminder</h2><p>Hello {{first_name}}, your registration is pending payment. Please complete your payment for {{category_name}}.</p>', 
'Payment Reminder. Hello {{first_name}}, your registration is pending payment. Please complete your payment for {{category_name}}.', 
'["first_name", "category_name", "amount"]', 1);

-- Log this migration
INSERT INTO activity_logs (user_id, action, description, ip_address) 
VALUES (1, 'database_migration', 'Applied migration 002: Added cache, analytics, rate limiting, and email template tables', 'system');

SELECT 'Migration 002 completed successfully - Cache and analytics tables created' as status;