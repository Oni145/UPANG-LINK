<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once '../config/Database.php';
require_once '../controllers/AuthController.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$controller = new AuthController($db);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the URI segments
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$baseUrl = '/UPANG-LINK/UPANG LINK API/auth';

// Debug logging
error_log("Request URI (decoded): " . $requestUri);
error_log("Base URL: " . $baseUrl);

// Normalize paths by replacing URL-encoded spaces with actual spaces
$requestUri = str_replace('%20', ' ', $requestUri);
$path = '';

if (strpos($requestUri, $baseUrl) !== false) {
    $path = substr($requestUri, strpos($requestUri, $baseUrl) + strlen($baseUrl));
}

// Debug logging
error_log("Path after base URL: " . $path);

// Split the path into segments and remove empty values
$uri = array_values(array_filter(explode('/', $path), function($segment) {
    return $segment !== '';
}));

// Debug logging
error_log("URI segments: " . print_r($uri, true));

// Handle the request
$controller->handleRequest($method, $uri); 