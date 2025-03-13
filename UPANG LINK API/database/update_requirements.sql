-- Update all request types to use the correct JSON format for requirements
-- This fixes the issue where requirements aren't appearing in the Android app

-- First, update all request types to have an empty fields array if they have null requirements
UPDATE request_types
SET requirements = JSON_OBJECT('fields', JSON_ARRAY())
WHERE requirements IS NULL OR requirements = 'null' OR requirements = '';

-- Update Transcript of Records
UPDATE request_types
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'clearance_form',
            'label', 'Clearance Form',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf,jpg,png',
            'description', 'Fully accomplished clearance form'
        ),
        JSON_OBJECT(
            'name', 'request_letter',
            'label', 'Request Letter',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf,doc,docx',
            'description', 'Formal letter stating the purpose of requesting TOR'
        )
    )
)
WHERE name = 'Transcript of Records';

-- Update Enrollment Certificate to have empty fields
UPDATE request_types
SET requirements = JSON_OBJECT('fields', JSON_ARRAY())
WHERE name = 'Enrollment Certificate';

-- Update New Student ID
UPDATE request_types
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'id_picture',
            'label', '1x1 ID Picture',
            'type', 'file',
            'required', true,
            'allowed_types', 'jpg,png',
            'description', 'White background, formal attire'
        ),
        JSON_OBJECT(
            'name', 'registration_form',
            'label', 'Registration Form',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf',
            'description', 'Completed registration form'
        )
    )
)
WHERE name = 'New Student ID';

-- Update ID Replacement
UPDATE request_types
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'affidavit_loss',
            'label', 'Affidavit of Loss',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf',
            'description', 'Notarized affidavit of loss'
        ),
        JSON_OBJECT(
            'name', 'id_picture',
            'label', '1x1 ID Picture',
            'type', 'file',
            'required', true,
            'allowed_types', 'jpg,png',
            'description', 'White background, formal attire'
        )
    )
)
WHERE name = 'ID Replacement';

-- Update PE Uniform Request
UPDATE request_types
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'student_id',
            'label', 'Valid Student ID',
            'type', 'file',
            'required', true,
            'allowed_types', 'jpg,png,pdf',
            'description', 'Image or scan of your valid student ID'
        )
    )
)
WHERE name = 'PE Uniform Request';

-- Update School Uniform Request
UPDATE request_types
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'student_id',
            'label', 'Valid Student ID',
            'type', 'file',
            'required', true,
            'allowed_types', 'jpg,png,pdf',
            'description', 'Image or scan of your valid student ID'
        )
    )
)
WHERE name = 'School Uniform Request';

-- Update Course Module Request
UPDATE request_types
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        JSON_OBJECT(
            'name', 'student_id',
            'label', 'Valid Student ID',
            'type', 'file',
            'required', true,
            'allowed_types', 'jpg,png,pdf',
            'description', 'Image or scan of your valid student ID'
        ),
        JSON_OBJECT(
            'name', 'professor_approval',
            'label', 'Professor Approval',
            'type', 'file',
            'required', true,
            'allowed_types', 'pdf,jpg,png',
            'description', 'Approval document from your professor'
        )
    )
)
WHERE name = 'Course Module Request';

-- Print a message to verify which rows were updated
SELECT 
    name, 
    JSON_CONTAINS_PATH(requirements, 'one', '$.fields') as has_fields_property,
    JSON_LENGTH(JSON_EXTRACT(requirements, '$.fields')) as field_count
FROM request_types; 