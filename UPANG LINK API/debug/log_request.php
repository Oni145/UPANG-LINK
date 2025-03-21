<?php
// Debug logging script
header('Content-Type: application/json');

// Enable error logging
error_log("Debug log_request.php started");

// Log request details
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$query_string = $_SERVER['QUERY_STRING'];
$request_headers = getallheaders();

// Log to PHP error log
error_log("Debug request details:");
error_log("Method: " . $request_method);
error_log("URI: " . $request_uri);
error_log("Query string: " . $query_string);
error_log("Headers: " . json_encode($request_headers));

// Output for browser
echo json_encode([
    'status' => 'debug',
    'message' => 'Request details logged',
    'request' => [
        'method' => $request_method,
        'uri' => $request_uri,
        'query_string' => $query_string,
        'headers' => $request_headers
    ]
]); 