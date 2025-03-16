<?php
// Main entry point for the UPANG LINK API WEB application

// Set CORS headers for all requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Platform");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/UPANG-LINK/UPANG LINK API WEB';
$path = str_replace($base_path, '', $request_uri);

// Check if the request is for the API
if (strpos($path, '/api') === 0) {
    // Include the API index file
    include_once __DIR__ . '/api/index.php';
    exit;
}

// Check if a specific file is requested
$requested_file = __DIR__ . '/WEB' . $path;
if (file_exists($requested_file) && !is_dir($requested_file)) {
    // Determine the content type based on file extension
    $extension = pathinfo($requested_file, PATHINFO_EXTENSION);
    switch ($extension) {
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        case 'svg':
            header('Content-Type: image/svg+xml');
            break;
        case 'json':
            header('Content-Type: application/json');
            break;
    }
    
    // Output the file contents
    readfile($requested_file);
    exit;
}

// Default to the main index.html file
include_once __DIR__ . '/WEB/index.html';
?> 