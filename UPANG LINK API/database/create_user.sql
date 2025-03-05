USE upang_link;

-- Create test student account
INSERT INTO users (
    student_number,
    email,
    password,
    first_name,
    last_name,
    role,
    course,
    year_level,
    block,
    admission_year,
    email_verified
) VALUES (
    '0001-2024-00001',
    'jerickogarcia0@gmail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is 'password'
    'Jericko',
    'Garcia',
    'student',
    'BSIT',
    3,
    'A',
    '2024',
    1  -- Setting email as verified
);

-- Insert sample requests for the test account
INSERT INTO requests (user_id, type_id, status, submitted_at, updated_at) VALUES
((SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 1, 'pending', NOW(), NOW()),
((SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 2, 'pending', NOW(), NOW()),
((SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 3, 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
((SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 4, 'rejected', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
((SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 5, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW());

-- Insert sample request notes
INSERT INTO request_notes (request_id, user_id, note) VALUES
(1, (SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 'Please prepare 2nd year to 3rd year records'),
(2, (SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 'Processing your request'),
(3, (SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 'Your ID is ready for pickup'),
(4, (SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 'Missing affidavit of loss'),
(5, (SELECT user_id FROM users WHERE email = 'jerickogarcia0@gmail.com'), 'Uniform ready for pickup'); 