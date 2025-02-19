<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Test endpoint working',
    'request_uri' => $_SERVER['REQUEST_URI'],
    'request_method' => $_SERVER['REQUEST_METHOD']
]); 