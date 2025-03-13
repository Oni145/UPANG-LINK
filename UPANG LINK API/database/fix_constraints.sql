USE upang_link;

-- Fix requirements column to allow NULL and ensure it's JSON
ALTER TABLE request_types MODIFY COLUMN requirements JSON NULL;

-- Update enum values in requests table to match Android app
ALTER TABLE requests MODIFY COLUMN status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'REJECTED') DEFAULT 'PENDING';

-- Convert existing status values to uppercase and standardize
UPDATE requests SET status = UPPER(status);

-- Update requirements to proper JSON format for each type
UPDATE request_types SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'clearance_form',
            'label', 'Clearance Form',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf,jpg,png',
            'description', 'Fully accomplished clearance form'
        )
    )
) WHERE type_id = 1;

UPDATE request_types SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'student_id',
            'label', 'Student ID',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf,jpg,png',
            'description', 'Valid student ID'
        )
    )
) WHERE type_id = 2;

UPDATE request_types SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'id_photo',
            'label', '1x1 ID Photo',
            'type', 'file',
            'required', true,
            'allowed_types', 'jpg,png',
            'description', '1x1 ID Picture with white background'
        )
    )
) WHERE type_id = 3;

UPDATE request_types SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'affidavit',
            'label', 'Affidavit of Loss',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf',
            'description', 'Notarized affidavit of loss'
        )
    )
) WHERE type_id = 4;

UPDATE request_types SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'measurement',
            'label', 'Size Measurements',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf,jpg,png',
            'description', 'Size measurements form'
        )
    )
) WHERE type_id = 5;

-- Add purpose column if not exists
ALTER TABLE requests ADD COLUMN IF NOT EXISTS purpose VARCHAR(255);