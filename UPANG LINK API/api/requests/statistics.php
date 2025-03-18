<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$statistics = [
    "total" => 5,
    "pending" => 2,
    "completed" => 2,
    "rejected" => 1,
    "byType" => [
        [
            "type" => "Academic Documents",
            "count" => 2
        ],
        [
            "type" => "Student ID",
            "count" => 2
        ],
        [
            "type" => "Uniforms",
            "count" => 1
        ]
    ],
    "byMonth" => [
        [
            "month" => "March",
            "count" => 5
        ]
    ]
];

echo json_encode([
    'status' => 'success',
    'data' => $statistics
]); 