-- =====================================================
-- Buffalo Marathon 2025 - Database Update Script
-- Updated Schedule and Participant Limits
-- Date: August 13, 2025
-- =====================================================

-- Update participant limits for all categories to 1200
UPDATE categories SET max_participants = 1200 WHERE name IN ('Full Marathon', 'Half Marathon', 'Power Challenge', 'Family Fun Run', 'Kid Run', 'VIP Run');

-- Update race start times according to new schedule
UPDATE categories SET start_time = '05:30:00' WHERE name IN ('Full Marathon', 'Half Marathon');
UPDATE categories SET start_time = '06:00:00' WHERE name IN ('Power Challenge', 'Family Fun Run', 'Kid Run');
UPDATE categories SET start_time = '06:30:00' WHERE name = 'VIP Run';

-- Re-activate VIP Run category and update it
UPDATE categories SET is_active = TRUE WHERE name = 'VIP Run';
UPDATE categories SET max_participants = 1200 WHERE name = 'VIP Run';

-- Clear existing schedule events and add new ones
DELETE FROM schedules WHERE event_type IN ('race', 'registration', 'ceremony', 'activity') AND event_date >= '2025-10-01';

-- Insert updated schedule events
INSERT INTO schedules (event_name, event_description, event_date, event_time, end_time, location, event_type, display_order, is_active) VALUES
-- Race pack collection (October 1-5)
('Race Pack Collection Day 1', 'Collect your race materials and bib number', '2025-10-01', '08:30:00', '17:00:00', 'Buffalo Park Recreation Centre', 'registration', 10, TRUE),
('Race Pack Collection Day 2', 'Collect your race materials and bib number', '2025-10-02', '08:30:00', '17:00:00', 'Buffalo Park Recreation Centre', 'registration', 11, TRUE),
('Race Pack Collection Day 3', 'Collect your race materials and bib number', '2025-10-03', '08:30:00', '17:00:00', 'Buffalo Park Recreation Centre', 'registration', 12, TRUE),
('Race Pack Collection Day 4', 'Collect your race materials and bib number', '2025-10-04', '08:30:00', '17:00:00', 'Buffalo Park Recreation Centre', 'registration', 13, TRUE),
('Race Pack Collection Day 5', 'Collect your race materials and bib number', '2025-10-05', '08:30:00', '17:00:00', 'Buffalo Park Recreation Centre', 'registration', 14, TRUE),

-- Race day events (October 11)
('Marathon Start', 'Full Marathon and Half Marathon start', '2025-10-11', '05:30:00', '05:30:00', 'Start Line', 'race', 20, TRUE),
('Power Challenge Start', '10KM Power Challenge begins', '2025-10-11', '06:00:00', '06:00:00', 'Start Line', 'race', 21, TRUE),
('Family Fun Run Start', '5KM Family Fun Run begins', '2025-10-11', '06:00:00', '06:00:00', 'Start Line', 'race', 22, TRUE),
('Kid Run Start', '1KM Kid Run begins', '2025-10-11', '06:00:00', '06:00:00', 'Start Line', 'race', 23, TRUE),
('VIP Run Start', '5KM VIP Run begins', '2025-10-11', '06:30:00', '06:30:00', 'Start Line', 'race', 24, TRUE),
('Post-Race Celebration', 'Food, drinks, and celebration', '2025-10-11', '11:00:00', '12:30:00', 'Event Grounds', 'activity', 30, TRUE),
('Awards Ceremony', 'Prize giving and medal ceremony', '2025-10-11', '11:30:00', '13:00:00', 'Main Stage', 'ceremony', 31, TRUE),
('Live Entertainment', 'Zambia Army Pop Band performance', '2025-10-11', '13:00:00', '15:00:00', 'Main Stage', 'activity', 32, TRUE);

-- Update system settings to reflect new maximum registrations (6 categories × 1200 = 7200)
UPDATE settings SET setting_value = '7200' WHERE setting_key = 'max_registrations';

-- Add new settings for race pack collection period
INSERT INTO settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('race_pack_collection_start', '2025-10-01', 'date', 'Race pack collection start date', 'event', TRUE),
('race_pack_collection_end', '2025-10-05', 'date', 'Race pack collection end date', 'event', TRUE),
('race_pack_collection_hours', '08:30 - 17:00', 'string', 'Race pack collection hours', 'event', TRUE)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Commit the changes
COMMIT;

-- Display summary of changes
SELECT 'Updated Categories' as Action, COUNT(*) as Count FROM categories WHERE max_participants = 1200 AND is_active = TRUE;
SELECT 'Updated Schedule Events' as Action, COUNT(*) as Count FROM schedules WHERE event_date >= '2025-10-01';
SELECT 'Maximum Total Registrations' as Setting, setting_value as Value FROM settings WHERE setting_key = 'max_registrations';
