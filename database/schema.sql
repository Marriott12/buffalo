-- =====================================================
-- Buffalo Marathon 2025 - Production Database Schema
-- Updated: August 13, 2025
-- Version: 2.0 (Production Ready)
-- =====================================================

-- Create database with proper character set for international support
CREATE DATABASE IF NOT EXISTS buffalo_marathon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buffalo_marathon;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(25),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    nationality VARCHAR(100) DEFAULT 'Zambian',
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'staff') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    lockout_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_verification (verification_token),
    INDEX idx_reset (reset_token)
);

-- =====================================================
-- CATEGORIES TABLE (Race Categories)
-- =====================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    distance VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    early_bird_price DECIMAL(10,2),
    max_participants INT DEFAULT 0,
    min_age INT DEFAULT 5,
    max_age INT DEFAULT 100,
    start_time TIME,
    estimated_duration VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
);

-- =====================================================
-- REGISTRATIONS TABLE
-- =====================================================
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'cancelled', 'refunded', 'failed') DEFAULT 'pending',
    
    -- Personal Information
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_phone VARCHAR(25) NOT NULL,
    emergency_contact_relationship VARCHAR(50),
    
    -- Race Information
    estimated_finish_time VARCHAR(20),
    tshirt_size ENUM('XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL') NOT NULL,
    
    -- Medical Information
    medical_conditions TEXT,
    medications TEXT,
    dietary_requirements TEXT,
    
    -- Payment Information
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    payment_date DATETIME,
    
    -- Event Day Information
    bib_number VARCHAR(20),
    start_time DATETIME,
    finish_time DATETIME,
    official_time TIME,
    
    -- Administrative
    notes TEXT,
    confirmed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_registration_number (registration_number),
    INDEX idx_bib_number (bib_number)
);

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ZMW',
    payment_method ENUM('mobile_money', 'bank_transfer', 'card', 'cash', 'other') NOT NULL,
    payment_provider VARCHAR(50),
    transaction_id VARCHAR(100),
    reference_number VARCHAR(100),
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    
    -- Mobile Money Details
    phone_number VARCHAR(25),
    network_provider VARCHAR(50),
    
    -- Payment Response Data
    provider_response JSON,
    
    -- Administrative
    processed_by INT,
    processed_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_registration_id (registration_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_reference_number (reference_number),
    INDEX idx_payment_method (payment_method)
);

-- =====================================================
-- ANNOUNCEMENTS TABLE
-- =====================================================
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('info', 'important', 'urgent', 'success', 'warning') DEFAULT 'info',
    target_audience ENUM('all', 'registered', 'unregistered', 'admins') DEFAULT 'all',
    is_active BOOLEAN DEFAULT TRUE,
    show_on_homepage BOOLEAN DEFAULT FALSE,
    show_on_dashboard BOOLEAN DEFAULT TRUE,
    
    -- Publishing
    published_at DATETIME,
    expires_at DATETIME,
    
    -- Author Information
    created_by INT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_active (is_active),
    INDEX idx_type (type),
    INDEX idx_target (target_audience),
    INDEX idx_published (published_at),
    INDEX idx_expires (expires_at)
);

-- =====================================================
-- EVENT SCHEDULE TABLE
-- =====================================================
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(255),
    category_id INT,
    event_type ENUM('race', 'activity', 'ceremony', 'registration', 'other') DEFAULT 'other',
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    
    INDEX idx_date (event_date),
    INDEX idx_time (event_time),
    INDEX idx_active (is_active),
    INDEX idx_type (event_type),
    INDEX idx_order (display_order)
);

-- =====================================================
-- SYSTEM SETTINGS TABLE
-- =====================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'date', 'time') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    category VARCHAR(50) DEFAULT 'general',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key),
    INDEX idx_category (category),
    INDEX idx_public (is_public)
);

-- =====================================================
-- CONTACT MESSAGES TABLE
-- =====================================================
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(25),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    inquiry_type ENUM('general', 'registration', 'payment', 'technical', 'partnership', 'media', 'other') DEFAULT 'general',
    status ENUM('new', 'read', 'replied', 'resolved', 'archived') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Response Information
    replied_by INT,
    replied_at DATETIME,
    reply_message TEXT,
    
    -- Administrative
    assigned_to INT,
    notes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_type (inquiry_type),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at)
);

-- =====================================================
-- ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    additional_data JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Admin User
INSERT INTO users (first_name, last_name, email, phone, password, role, email_verified, created_at) VALUES
('Buffalo', 'Admin', 'admin@buffalo-marathon.com', '+260 972 545 658', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, NOW());

-- Race Categories
INSERT INTO categories (name, distance, description, price, early_bird_price, max_participants, min_age, start_time, sort_order, is_active) VALUES
('Full Marathon', '42.2 KM', 'The ultimate challenge for serious runners. Experience the full Buffalo Marathon distance.', 200.00, 150.00, 500, 18, '06:00:00', 1, TRUE),
('Half Marathon', '21.1 KM', 'Perfect for intermediate runners looking for a challenging but achievable distance.', 150.00, 120.00, 800, 16, '06:30:00', 2, TRUE),
('Power Challenge', '10 KM', 'High-intensity 10K race for competitive runners and fitness enthusiasts.', 100.00, 80.00, 1000, 14, '07:00:00', 3, TRUE),
('Family Fun Run', '5 KM', 'A fun run perfect for families, beginners, and those looking for a social running experience.', 75.00, 60.00, 1500, 8, '07:30:00', 4, TRUE),
('VIP Run', '5 KM', 'Premium race experience with exclusive amenities and special treatment.', 250.00, 200.00, 100, 18, '08:00:00', 5, TRUE),
('Kid Run', '1 KM', 'Special race designed for children to experience the joy of running.', 25.00, 20.00, 300, 5, '08:30:00', 6, TRUE);

-- System Settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('site_name', 'Buffalo Marathon 2025', 'string', 'Website name', 'general', TRUE),
('marathon_date', '2025-10-11', 'date', 'Marathon event date', 'event', TRUE),
('registration_deadline', '2025-10-01', 'date', 'Registration deadline', 'registration', TRUE),
('early_bird_deadline', '2025-08-31', 'date', 'Early bird pricing deadline', 'registration', TRUE),
('registration_open', 'true', 'boolean', 'Registration status', 'registration', TRUE),
('max_registrations', '4000', 'number', 'Maximum total registrations', 'registration', FALSE),
('contact_phone_1', '+260 972 545 658', 'string', 'Primary contact phone', 'contact', TRUE),
('contact_phone_2', '+260 770 809 062', 'string', 'Secondary contact phone', 'contact', TRUE),
('contact_phone_3', '+260 771 470 868', 'string', 'Tertiary contact phone', 'contact', TRUE),
('contact_email', 'info@buffalo-marathon.com', 'string', 'Main contact email', 'contact', TRUE),
('smtp_host', 'smtp.gmail.com', 'string', 'SMTP server host', 'email', FALSE),
('smtp_username', 'noreply@buffalo-marathon.com', 'string', 'SMTP username', 'email', FALSE),
('smtp_password', 'Buffalo@2025', 'string', 'SMTP password', 'email', FALSE),
('smtp_port', '587', 'number', 'SMTP port', 'email', FALSE),
('event_location', 'Buffalo Park Recreation Centre', 'string', 'Event venue', 'event', TRUE),
('event_city', 'Lusaka', 'string', 'Event city', 'event', TRUE),
('event_country', 'Zambia', 'string', 'Event country', 'event', TRUE);

-- Sample Schedule Events
INSERT INTO schedules (event_name, event_description, event_date, event_time, end_time, location, event_type, display_order, is_active) VALUES
('Registration Opens', 'Online and physical registration begins', '2025-08-01', '00:00:00', '23:59:59', 'Online & Buffalo Park', 'registration', 1, TRUE),
('Early Bird Period Ends', 'Last day for early bird pricing', '2025-08-31', '23:59:59', '23:59:59', 'Online', 'registration', 2, TRUE),
('Race Packet Collection', 'Collect your race materials and bib number', '2025-10-09', '09:00:00', '18:00:00', 'Buffalo Park Recreation Centre', 'registration', 3, TRUE),
('Race Packet Collection', 'Collect your race materials and bib number', '2025-10-10', '09:00:00', '20:00:00', 'Buffalo Park Recreation Centre', 'registration', 4, TRUE),
('Marathon Start', 'Full Marathon (42.2 KM) race begins', '2025-10-11', '06:00:00', '06:00:00', 'Buffalo Park Recreation Centre', 'race', 5, TRUE),
('Half Marathon Start', 'Half Marathon (21.1 KM) race begins', '2025-10-11', '06:30:00', '06:30:00', 'Buffalo Park Recreation Centre', 'race', 6, TRUE),
('10K Power Challenge Start', '10K race begins', '2025-10-11', '07:00:00', '07:00:00', 'Buffalo Park Recreation Centre', 'race', 7, TRUE),
('Family Fun Run Start', '5K family race begins', '2025-10-11', '07:30:00', '07:30:00', 'Buffalo Park Recreation Centre', 'race', 8, TRUE),
('VIP Run Start', 'VIP 5K race begins', '2025-10-11', '08:00:00', '08:00:00', 'Buffalo Park Recreation Centre', 'race', 9, TRUE),
('Kids Run Start', '1K kids race begins', '2025-10-11', '08:30:00', '08:30:00', 'Buffalo Park Recreation Centre', 'race', 10, TRUE),
('Awards Ceremony', 'Prize giving and closing ceremony', '2025-10-11', '11:00:00', '13:00:00', 'Buffalo Park Recreation Centre', 'ceremony', 11, TRUE);

-- Sample Announcement
INSERT INTO announcements (title, content, type, target_audience, is_active, show_on_homepage, show_on_dashboard, published_at, created_by) VALUES
('Welcome to Buffalo Marathon 2025!', 'Registration is now open for Buffalo Marathon 2025. Early bird pricing available until August 31st. Join us for an unforgettable running experience at Buffalo Park Recreation Centre.', 'info', 'all', TRUE, TRUE, TRUE, NOW(), 1);

-- =====================================================
-- CREATE VIEWS FOR REPORTING
-- =====================================================

-- Registration Summary View
CREATE VIEW v_registration_summary AS
SELECT 
    c.name as category_name,
    c.distance,
    c.price,
    COUNT(r.id) as total_registrations,
    COUNT(CASE WHEN r.payment_status = 'paid' THEN 1 END) as paid_registrations,
    COUNT(CASE WHEN r.status = 'confirmed' THEN 1 END) as confirmed_registrations,
    SUM(CASE WHEN r.payment_status = 'paid' THEN p.amount ELSE 0 END) as total_revenue
FROM categories c
LEFT JOIN registrations r ON c.id = r.category_id
LEFT JOIN payments p ON r.id = p.registration_id AND p.status = 'completed'
WHERE c.is_active = TRUE
GROUP BY c.id, c.name, c.distance, c.price
ORDER BY c.sort_order;

-- User Registration View
CREATE VIEW v_user_registrations AS
SELECT 
    u.id as user_id,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.email,
    u.phone,
    r.registration_number,
    c.name as category_name,
    c.distance,
    r.status as registration_status,
    r.payment_status,
    r.amount_paid,
    r.bib_number,
    r.created_at as registration_date
FROM users u
JOIN registrations r ON u.id = r.user_id
JOIN categories c ON r.category_id = c.id
ORDER BY r.created_at DESC;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX idx_registrations_created_date ON registrations(DATE(created_at));
CREATE INDEX idx_payments_created_date ON payments(DATE(created_at));
CREATE INDEX idx_users_created_date ON users(DATE(created_at));
CREATE INDEX idx_contact_messages_created_date ON contact_messages(DATE(created_at));

-- =====================================================
-- DATABASE PROCEDURES
-- =====================================================

DELIMITER //

-- Generate unique registration number
CREATE FUNCTION generate_registration_number(category_prefix VARCHAR(5)) 
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE reg_number VARCHAR(20);
    DECLARE counter INT;
    
    SELECT COUNT(*) + 1 INTO counter FROM registrations WHERE registration_number LIKE CONCAT(category_prefix, '%');
    SET reg_number = CONCAT(category_prefix, YEAR(NOW()), LPAD(counter, 4, '0'));
    
    RETURN reg_number;
END//

DELIMITER ;

-- =====================================================
-- FINAL NOTES
-- =====================================================
-- This schema supports:
-- 1. Complete user management with role-based access
-- 2. Flexible race categories with pricing tiers
-- 3. Comprehensive registration system
-- 4. Payment tracking and processing
-- 5. Event scheduling and announcements
-- 6. Contact form management
-- 7. Activity logging for audit trails
-- 8. System settings for easy configuration
-- 9. Reporting views for analytics
-- 10. Performance optimized with proper indexing
-- 
-- Production Ready Features:
-- - UTF8MB4 encoding for international support
-- - Proper foreign key constraints
-- - Comprehensive indexing
-- - Data validation through ENUM types
-- - JSON support for flexible data storage
-- - Activity logging for security
-- - Views for efficient reporting
-- - Default data for immediate use
--
-- Contact: +260 972 545 658 / +260 770 809 062 / +260 771 470 868
-- Email: info@buffalo-marathon.com
-- =====================================================
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    race_pack_collected BOOLEAN DEFAULT FALSE,
    race_pack_collected_at DATETIME,
    race_pack_collected_by VARCHAR(100),
    race_time TIME,
    race_position INT,
    certificate_generated BOOLEAN DEFAULT FALSE,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_registration_number (registration_number),
    INDEX idx_user_category (user_id, category_id)
);

-- Event schedule
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(200),
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_event_date (event_date),
    INDEX idx_featured (is_featured)
);

-- News/Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT TRUE,
    featured_image VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_published (is_published),
    INDEX idx_featured (is_featured)
);

-- Payment logs
CREATE TABLE payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    amount DECIMAL(10,2),
    notes TEXT,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES registrations(id),
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_registration_id (registration_id)
);

-- Activity logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Email queue
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255),
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_attempt DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Insert default categories
INSERT INTO categories (name, distance, fee, description, sort_order) VALUES
('Full Marathon', '42 KM', 500.00, 'The Full Marathon will be 42 kilometers - The ultimate endurance challenge', 1),
('Half Marathon', '21 KM', 500.00, 'Half-marathon will be 21 kilometers - Perfect for intermediate runners', 2),
('Power Challenge', '10 KM', 500.00, 'Power Challenge will be 10 kilometers - Test your speed and endurance', 3),
('Family Fun Run', '5 KM', 500.00, 'Family Fun Run - Perfect for families and beginners', 4),
('VIP Run', 'Any Distance', 600.00, 'The VIP Run - Premium experience with exclusive perks', 5),
('Kid Run', '1 KM', 450.00, 'For our little heroes - Fun run for children under 18', 6);

-- Insert default admin user (password: Admin123!)
INSERT INTO users (first_name, last_name, email, password, role, email_verified) VALUES
('Admin', 'User', 'admin@buffalo-marathon.com', '$2y$12$LQv3c1ydiCr7tWlL8urEWO3wEiYVOJZI8QZvnFQXyI6f2/mYZtd9y', 'admin', TRUE);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_maintenance', '0', 'Site maintenance mode (0=off, 1=on)'),
('registration_open', '1', 'Registration status (0=closed, 1=open)'),
('max_participants', '1000', 'Maximum number of participants'),
('contact_email', 'info@buffalo-marathon.com', 'Contact email address'),
('contact_phone', '+260 XXX XXXXXXX', 'Contact phone number'),
('payment_instructions', 'Payment can be made via Mobile Money, Bank Transfer, or Cash at the venue.', 'Payment instructions for participants');

-- Insert sample schedule events
INSERT INTO schedules (title, description, event_date, event_time, end_time, location, is_featured, created_by) VALUES
('Registration Opens', 'Marathon registration opens for all categories. Early bird discounts available!', '2025-08-01', '08:00:00', '23:59:00', 'Online Platform', TRUE, 1),
('Training Camp - Week 1', 'Join our professional trainers for marathon preparation sessions', '2025-08-15', '06:00:00', '08:00:00', 'Buffalo Park Recreation Centre', FALSE, 1),
('Training Camp - Week 2', 'Advanced training techniques and nutrition workshop', '2025-08-22', '06:00:00', '08:00:00', 'Buffalo Park Recreation Centre', FALSE, 1),
('Training Camp - Week 3', 'Final preparation and race strategy session', '2025-08-29', '06:00:00', '08:00:00', 'Buffalo Park Recreation Centre', FALSE, 1),
('Pre-Race Briefing', 'Important race information and packet pickup', '2025-09-25', '18:00:00', '20:00:00', 'Buffalo Park Recreation Centre', TRUE, 1),
('Registration Deadline', 'Last day to register for Buffalo Marathon 2025', '2025-09-30', '23:59:00', '23:59:59', 'Online Platform', TRUE, 1),
('Race Pack Collection Day 1', 'Collect your race number, t-shirt, and race pack', '2025-10-09', '09:00:00', '17:00:00', 'Buffalo Park Recreation Centre', TRUE, 1),
('Race Pack Collection Day 2', 'Final day for race pack collection', '2025-10-10', '09:00:00', '19:00:00', 'Buffalo Park Recreation Centre', TRUE, 1),
('Race Day Check-in', 'Final check-in and late race pack collection', '2025-10-11', '06:00:00', '06:45:00', 'Buffalo Park Recreation Centre', TRUE, 1),
('Pre-Race Aerobics', 'Warm-up session with professional instructors', '2025-10-11', '06:00:00', '06:45:00', 'Main Event Area', TRUE, 1),
('Buffalo Marathon 2025 - Race Start', 'Main event day - All categories start', '2025-10-11', '07:00:00', '12:00:00', 'Buffalo Park Recreation Centre, Chalala-Along Joe Chibangu Road', TRUE, 1),
('Post-Race Aerobics', 'Cool-down session and recovery exercises', '2025-10-11', '12:00:00', '13:00:00', 'Main Event Area', TRUE, 1),
('Prize Giving Ceremony', 'Award ceremony for category winners', '2025-10-11', '13:30:00', '15:00:00', 'Main Stage', TRUE, 1),
('Live Entertainment', 'Zambia Army Pop Band and special guest artists', '2025-10-11', '08:00:00', '17:00:00', 'Entertainment Zone', TRUE, 1);

-- Insert sample announcements
INSERT INTO announcements (title, content, excerpt, is_featured, created_by) VALUES
('Welcome to Buffalo Marathon 2025!', 
'We are excited to announce the Buffalo Marathon 2025, taking place on Saturday, 11 October 2025 at Buffalo Park Recreation Centre. This year promises to be our biggest and best event yet, with amazing prizes, entertainment, and experiences for all participants.

## What\'s Included in Your Registration:
- **Branded Marathon T-Shirt** - High-quality moisture-wicking fabric
- **Finisher\'s Medal** - Beautiful commemorative medal for all finishers  
- **Race Number Bib** - Official race identification
- **Free Drink Voucher** - Refreshments during and after the race

## Event Highlights:
- **Pre & Post-Race Aerobics** - Professional warm-up and cool-down sessions
- **Live Entertainment** - Zambia Army Pop Band and special guest artists
- **Food & Drinks** - Braai packs, food zones, chill lounge with local drinks and wine
- **Kids Zone** - Fun activities for the little ones
- **Multiple Categories** - From 1KM kids run to 42KM full marathon

Join us for an unforgettable day of running, community, and celebration!', 
'Join us for Buffalo Marathon 2025 - an unforgettable day of running, entertainment, and community celebration at Buffalo Park Recreation Centre.', 
TRUE, 1),

('Race Pack Collection Information', 
'**Important: Race Pack Collection Details**

Your race pack includes:
- Branded marathon t-shirt (moisture-wicking fabric)
- Official race number bib with timing chip
- Finisher\'s medal (given upon completion)
- Free drink voucher for race day refreshments
- Event information booklet
- Sponsor goodies and samples

**Collection Dates:**
- **October 9, 2025:** 9:00 AM - 5:00 PM
- **October 10, 2025:** 9:00 AM - 7:00 PM  
- **October 11, 2025:** 6:00 AM - 6:45 AM (Race Day - Limited time only)

**Collection Location:** Buffalo Park Recreation Centre, Chalala-Along Joe Chibangu Road

**What to Bring:**
- Government-issued ID
- Registration confirmation email
- Payment confirmation (if paid online)

**Important:** Race packs not collected by 6:45 AM on race day will be forfeited. No exceptions.',
'Essential information about collecting your race pack, including dates, times, and required documents.',
TRUE, 1);