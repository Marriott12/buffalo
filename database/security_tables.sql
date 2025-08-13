-- Buffalo Marathon 2025 - Security Enhancement Tables
-- Additional tables for enhanced security monitoring

-- Rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_limits_ip_action (ip_address, action),
    INDEX idx_rate_limits_created (created_at)
);

-- Security events logging
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_security_logs_type (event_type),
    INDEX idx_security_logs_ip (ip_address),
    INDEX idx_security_logs_created (created_at)
);

-- IP blocking table
CREATE TABLE IF NOT EXISTS ip_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    blocked_until TIMESTAMP NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_blocks_until (blocked_until),
    INDEX idx_ip_blocks_ip (ip_address)
);

-- Login attempts tracking
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255),
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    user_agent TEXT,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email (email),
    INDEX idx_login_attempts_ip (ip_address),
    INDEX idx_login_attempts_time (attempted_at)
);

-- Session tracking for security
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_sessions_user (user_id),
    INDEX idx_user_sessions_activity (last_activity)
);

-- Add IP address tracking to existing tables
ALTER TABLE registrations ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45);
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45);

-- Create indexes for new columns
CREATE INDEX IF NOT EXISTS idx_registrations_ip ON registrations(ip_address);
CREATE INDEX IF NOT EXISTS idx_activity_logs_ip ON activity_logs(ip_address);
