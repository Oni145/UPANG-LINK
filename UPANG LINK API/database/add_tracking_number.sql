-- Add tracking_number column if it doesn't exist
ALTER TABLE requests
ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(20) UNIQUE NOT NULL DEFAULT '';

-- Update existing rows with a generated tracking number
UPDATE requests 
SET tracking_number = CONCAT('REQ-', DATE_FORMAT(submitted_at, '%Y%m'), '-', LPAD(request_id, 4, '0'))
WHERE tracking_number = '';

-- Remove the default value constraint
ALTER TABLE requests
ALTER COLUMN tracking_number DROP DEFAULT; 