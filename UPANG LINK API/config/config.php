<?php
return [
    'app' => [
        'name' => 'UPANG LINK',
        'version' => '1.0.0',
        'frontend_url' => 'http://your-frontend-url',
        'api_url' => 'http://your-api-url',
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'upang_link',
        'username' => 'root',
        'password' => ''
    ],
    'email' => [
        'host' => 'smtp.your-email-provider.com',
        'port' => 587,
        'username' => 'your-email@domain.com',
        'password' => 'your-email-password',
        'from_name' => 'UPANG LINK',
        'from_email' => 'noreply@upang-link.com'
    ],
    'security' => [
        'token_expiry' => 24, // hours
        'verification_expiry' => 24, // hours
        'password_min_length' => 8,
        'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        'max_file_size' => 5 * 1024 * 1024 // 5MB
    ],
    'cors' => [
        'allowed_origins' => ['http://localhost:3000', 'http://localhost:8080'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
    ]
]; 