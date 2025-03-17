USE upang_link;

-- Create admin account if not exists
INSERT INTO users (
    email,
    password,
    first_name,
    last_name,
    role,
    email_verified
) 
SELECT 
    'admin@admin.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is 'password'
    'Admin',
    'User',
    'admin',
    1  -- Setting email as verified
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@admin.com'
); 