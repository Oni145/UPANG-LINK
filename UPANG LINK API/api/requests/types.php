<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$types = [
    [
        "type_id" => 1,
        "category_id" => 1,
        "name" => "Transcript of Records",
        "description" => "Official academic transcript",
        "requirements" => json_encode([
            "fields" => [[
                "name" => "clearance_form",
                "label" => "Clearance Form",
                "type" => "file",
                "required" => true,
                "allowed_types" => "pdf,jpg,png",
                "description" => "Fully accomplished clearance form"
            ], [
                "name" => "request_letter",
                "label" => "Request Letter",
                "type" => "file",
                "required" => true,
                "allowed_types" => "pdf,doc,docx",
                "description" => "Formal letter stating the purpose of requesting TOR"
            ]]
        ]),
        "processing_time" => "5-7 working days",
        "is_active" => 1,
        "category_name" => "Academic Documents"
    ],
    [
        "type_id" => 2,
        "category_id" => 1,
        "name" => "Enrollment Certificate",
        "description" => "Proof of enrollment document",
        "requirements" => json_encode([
            "required_docs" => ["Valid student ID"]
        ]),
        "processing_time" => "2-3 working days",
        "is_active" => 1,
        "category_name" => "Academic Documents"
    ],
    [
        "type_id" => 3,
        "category_id" => 2,
        "name" => "New Student ID",
        "description" => "First time ID request",
        "requirements" => json_encode([
            "required_docs" => [
                "1x1 ID Picture (white background, formal attire)",
                "Registration Form"
            ]
        ]),
        "processing_time" => "5-7 working days",
        "is_active" => 1,
        "category_name" => "Student ID"
    ]
];

echo json_encode([
    'status' => 'success',
    'data' => $types
]); 