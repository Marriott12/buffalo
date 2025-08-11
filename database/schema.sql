-- Buffalo Marathon 2025 Database Schema
CREATE DATABASE IF NOT EXISTS buffalo_marathon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buffalo_marathon;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Marathon categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    distance VARCHAR(20) NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Registrations table
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_phone VARCHAR(20) NOT NULL,
    tshirt_size ENUM('XS', 'S', 'M', 'L', 'XL', 'XXL') NOT NULL,
    medical_conditions TEXT,
    dietary_requirements TEXT,
    payment_status ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
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