<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/Database.php';
include_once '../models/User.php';
include_once '../models/Request.php';
include_once '../models/RequestType.php';
include_once '../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

// Get the request URI and parse it
$request_uri = urldecode($_SERVER['REQUEST_URI']);
$uri_parts = parse_url($request_uri);
$path = $uri_parts['path'];

// Remove the base path if it exists
$base_path = '/UPANG LINK/api/';  // Changed from URL-encoded version
$endpoint = str_replace($base_path, '', $path);
$uri = explode('/', trim($endpoint, '/'));

// Debug information
error_log("Request URI: " . $request_uri);
error_log("Path: " . $path);
error_log("Endpoint: " . $endpoint);
error_log("URI[0]: " . (isset($uri[0]) ? $uri[0] : 'empty'));

// If no specific endpoint is requested, return API info
if (empty($uri[0])) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Welcome to UPANG LINK API',
        'endpoints' => [
            'auth' => '/auth',
            'requests' => '/requests',
            'users' => '/users'
        ]
    ]);
    exit();
}

// Route the request to the appropriate handler
$requestMethod = $_SERVER["REQUEST_METHOD"];

// Basic routing
switch($uri[0]) {
    case 'auth':
        include_once '../controllers/AuthController.php';
        $controller = new AuthController($db);
        break;
    case 'requests':
        include_once '../controllers/RequestController.php';
        $controller = new RequestController($db);
        break;
    case 'users':
        include_once '../controllers/UserController.php';
        $controller = new UserController($db);
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found',
            'debug' => [
                'request_uri' => $request_uri,
                'path' => $path,
                'endpoint' => $endpoint,
                'uri' => $uri
            ]
        ]);
        exit();
}

// Handle the request
$controller->handleRequest($requestMethod, $uri); 