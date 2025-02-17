<?php
// Set CORS and content-type headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Platform");

// Handle preflight OPTIONS requests early
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    exit(0);
}

include_once __DIR__ . '/../config/Database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/Request.php';
include_once __DIR__ . '/../models/RequestType.php';
include_once __DIR__ . '/../models/Notification.php';
include_once __DIR__ . '/../controllers/AdminController.php';

$database = new Database();
$db = $database->getConnection();

// Get the request URI and parse it
$request_uri = urldecode($_SERVER['REQUEST_URI']);
$uri_parts = parse_url($request_uri);
$path = $uri_parts['path'];

// Define your base path exactly as deployed (ensure no trailing slash issues)
$base_path = '/UPANG LINK/api/';  // Adjust if necessary
$endpoint = str_replace($base_path, '', $path);
$uri = explode('/', trim($endpoint, '/'));

// Debug information (check your error log to verify these values)
error_log("Request URI: " . $request_uri);
error_log("Path: " . $path);
error_log("Endpoint: " . $endpoint);
error_log("URI: " . print_r($uri, true));

// If no specific endpoint is requested, return API info
if (empty($uri[0])) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Welcome to UPANG LINK API',
        'endpoints' => [
            'auth'    => '/auth',
            'admin'   => '/admin',
            'requests'=> '/requests',
            'users'   => '/users'
        ]
    ]);
    exit();
}

// Route the request to the appropriate controller
$requestMethod = $_SERVER["REQUEST_METHOD"];
$controller = null;

switch($uri[0]) {
    case 'admin':
        $controller = new AdminAuthController($db);
        array_unshift($uri, 'auth');
        break;
    case 'auth':
        include_once __DIR__ . '/../controllers/AuthController.php';
        $controller = new AuthController($db);
        array_shift($uri);
        break;
    case 'requests':
        include_once __DIR__ . '/../controllers/RequestController.php';
        $controller = new RequestController($db);
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found',
            'debug' => [
                'request_uri' => $request_uri,
                'path'        => $path,
                'endpoint'    => $endpoint,
                'uri'         => $uri
            ]
        ]);
        exit();
}

// Let the controller handle the request with the (possibly modified) URI
$controller->handleRequest($requestMethod, $uri);
?>
