-- Buffalo Marathon 2025 - Database Migration 001
-- Fix Categories Schema and Add Missing Columns
-- Created: 2025-01-09

USE buffalo_marathon;

-- Add missing columns to categories table
ALTER TABLE categories 
ADD COLUMN max_participants INT NOT NULL DEFAULT 6000 AFTER description,
ADD COLUMN min_age INT NOT NULL DEFAULT 18 AFTER max_participants,
ADD COLUMN max_age INT NOT NULL DEFAULT 99 AFTER min_age;

-- Rename fee to price for consistency with code
ALTER TABLE categories 
CHANGE COLUMN fee price DECIMAL(10,2) NOT NULL;

-- Update categories with proper values
UPDATE categories SET 
    max_participants = 6000,
    min_age = CASE 
        WHEN name = 'Kid Run' THEN 8
        WHEN name = 'Family Fun Run' THEN 12
        ELSE 18
    END,
    max_age = CASE 
        WHEN name = 'Kid Run' THEN 17
        WHEN name = 'Family Fun Run' THEN 99
        ELSE 99
    END
WHERE id > 0;

-- Add performance indexes for better query optimization
CREATE INDEX idx_categories_active_sort ON categories (is_active, sort_order);
CREATE INDEX idx_categories_participants ON categories (max_participants, is_active);

-- Add index for registration counts optimization
CREATE INDEX idx_registrations_category_status ON registrations (category_id, payment_status);
CREATE INDEX idx_registrations_created_at ON registrations (created_at);
CREATE INDEX idx_registrations_user_status ON registrations (user_id, payment_status);

-- Add index for better user queries
CREATE INDEX idx_users_email_verified ON users (email_verified, role);
CREATE INDEX idx_users_created_at ON users (created_at);

-- Log this migration
INSERT INTO activity_logs (user_id, action, description, ip_address) 
VALUES (1, 'database_migration', 'Applied migration 001: Fix categories schema and add performance indexes', 'system');

SELECT 'Migration 001 completed successfully - Categories schema updated and indexes added' as status;