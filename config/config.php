<?php
$base_url = 'http://192.168.18.138:8000';

return [
    'app' => [
        'name'         => 'UPANG LINK',
        'version'      => '1.0.0',
        'base_url'     => $base_url,
        'frontend_url' => $base_url . '/UPANG%20LINK',
        'api_url'      => $base_url . '/UPANG%20LINK'
    ],
    'database' => [
        'host'     => 'localhost',
        'name'     => 'upang_link',
        'username' => 'root',
        'password' => ''
    ],
    'email' => [
        'smtp_host'     => 'smtp.gmail.com',
        'smtp_port'     => 587,
        'smtp_username' => 'librariansystem1@gmail.com',
        'smtp_password' => 'fyii qywz sobr wfks',
        'smtp_secure'   => 'TLS',
        'from_email'    => 'no-reply@UpangLink.com',
        'from_name'     => 'UPANG LINK'
    ],
    'security' => [
        'token_expiry'        => 24, // hours
        'verification_expiry' => 24, // hours
        'password_min_length' => 8,
        'allowed_file_types'  => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        'max_file_size'       => 5 * 1024 * 1024 // 5MB
    ],
    'cors' => [
        'allowed_origins' => [
            $base_url,
            $base_url . '/UPANG%20LINK'
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
    ]
];
