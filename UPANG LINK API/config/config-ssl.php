<?php
// Alternative config file using SSL on port 465 for Gmail
// If this works better, rename this file to config.php

// Define base URL
$base_url = 'http://192.168.1.13';

$config = [
    'app' => [
        'name' => 'UPANG LINK',
        'version' => '1.0.0',
        'base_url' => $base_url,
        'environment' => 'development', // or 'production' when ready
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'upang_link',
        'username' => 'root',
        'password' => ''
    ],
    'email' => [
        'host' => 'smtp.gmail.com',
        'port' => 465, // Using 465 instead of 587
        'username' => 'jerickogarcia0@gmail.com',
        'password' => 'laht squw emyi ggix', // Must be an App Password from Google Account
        'from_name' => 'UPANG LINK',
        'from_email' => 'jerickogarcia0@gmail.com',
        'smtp_auth' => true,
        'smtp_secure' => 'ssl', // Using 'ssl' instead of 'tls'
        'smtp_debug' => 2, // 0 = off, 1 = client, 2 = client/server, 3 = client/server + connection, 4 = low-level data output
        'smtp_options' => [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ],
        'timeout' => 30 // Increase timeout to 30 seconds
    ],
    'security' => [
        'token_expiry' => 24, // hours
        'verification_expiry' => 24, // hours
        'password_min_length' => 8,
        'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        'max_file_size' => 5 * 1024 * 1024 // 5MB
    ],
    'cors' => [
        'allowed_origins' => [
            $base_url,
            $base_url . '/UPANG-LINK',
            $base_url . '/UPANG-LINK/UPANG%20LINK%20API'
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
    ]
];

$config['app']['frontend_url'] = $base_url . '/UPANG-LINK/UPANG%20LINK%20API/pages';
$config['app']['api_url'] = $base_url . '/UPANG-LINK/UPANG%20LINK%20API';

return $config; 