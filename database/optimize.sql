-- Buffalo Marathon 2025 - Database Optimization
-- Performance indexes and optimizations
-- Run these commands on production database for better performance

-- Add performance indexes
CREATE INDEX IF NOT EXISTS idx_registrations_status_created ON registrations(payment_status, created_at);
CREATE INDEX IF NOT EXISTS idx_registrations_category_status ON registrations(category_id, payment_status);
CREATE INDEX IF NOT EXISTS idx_users_email_role ON users(email, role);
CREATE INDEX IF NOT EXISTS idx_users_created ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status, created_at);
CREATE INDEX IF NOT EXISTS idx_payments_status_date ON payment_logs(status, created_at);

-- Optimize existing tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE registrations;
OPTIMIZE TABLE categories;
OPTIMIZE TABLE activity_logs;
OPTIMIZE TABLE email_queue;
OPTIMIZE TABLE payment_logs;

-- Update table statistics
ANALYZE TABLE users;
ANALYZE TABLE registrations;
ANALYZE TABLE categories;
ANALYZE TABLE activity_logs;
ANALYZE TABLE email_queue;
ANALYZE TABLE payment_logs;

-- Create views for common queries
CREATE OR REPLACE VIEW v_registration_summary AS
SELECT 
    c.name as category_name,
    c.max_participants,
    COUNT(r.id) as registered_count,
    SUM(CASE WHEN r.payment_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
    SUM(CASE WHEN r.payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN r.payment_status = 'confirmed' THEN r.amount ELSE 0 END) as revenue,
    (c.max_participants - COUNT(CASE WHEN r.payment_status = 'confirmed' THEN r.id END)) as spots_remaining
FROM categories c
LEFT JOIN registrations r ON c.id = r.category_id
GROUP BY c.id, c.name, c.max_participants;

-- Create view for dashboard statistics
CREATE OR REPLACE VIEW v_dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM registrations WHERE payment_status = 'confirmed') as total_confirmed,
    (SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending') as total_pending,
    (SELECT SUM(amount) FROM registrations WHERE payment_status = 'confirmed') as total_revenue,
    (SELECT COUNT(*) FROM users WHERE role = 'participant') as total_users,
    (SELECT COUNT(*) FROM registrations WHERE created_at >= CURDATE()) as registrations_today,
    (SELECT COUNT(*) FROM registrations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as registrations_week;

-- Partitioning for activity_logs (if table becomes large)
-- ALTER TABLE activity_logs PARTITION BY RANGE (YEAR(created_at)) (
--     PARTITION p2024 VALUES LESS THAN (2025),
--     PARTITION p2025 VALUES LESS THAN (2026),
--     PARTITION p2026 VALUES LESS THAN (2027),
--     PARTITION p_future VALUES LESS THAN MAXVALUE
-- );

-- Archive old activity logs (older than 1 year)
-- CREATE TABLE activity_logs_archive LIKE activity_logs;
-- INSERT INTO activity_logs_archive SELECT * FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
-- DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Set optimal MySQL configuration settings
-- Add these to your my.cnf file:
/*
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 64M
query_cache_type = 1
tmp_table_size = 64M
max_heap_table_size = 64M
key_buffer_size = 128M
sort_buffer_size = 2M
read_buffer_size = 128K
read_rnd_buffer_size = 256K
thread_cache_size = 8
table_open_cache = 2000
*/
