<?php
return [
    'app' => [
        'name' => 'UPANG LINK',
        'version' => '1.0.0',
        'frontend_url' => 'http://192.168.1.7/UPANG-LINK/UPANG%20LINK%20API/pages',
        'api_url' => 'http://192.168.1.7/UPANG-LINK/UPANG%20LINK%20API',
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'upang_link',
        'username' => 'root',
        'password' => ''
    ],
    'email' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'jerickogarcia0@gmail.com',
        'password' => 'laht squw emyi ggix',
        'from_name' => 'UPANG LINK',
        'from_email' => 'jerickogarcia0@gmail.com'
    ],
    'security' => [
        'token_expiry' => 24, // hours
        'verification_expiry' => 24, // hours
        'password_min_length' => 8,
        'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        'max_file_size' => 5 * 1024 * 1024 // 5MB
    ],
    'cors' => [
        'allowed_origins' => ['http://192.168.1.7', 'http://localhost', 'http://192.168.1.7/UPANG-LINK'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
    ]
]; 