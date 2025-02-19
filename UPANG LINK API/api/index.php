<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Set headers
header("Access-Control-Allow-Origin: " . implode(', ', $config['cors']['allowed_origins']));
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: " . implode(', ', $config['cors']['allowed_methods']));
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: " . implode(', ', $config['cors']['allowed_headers']));

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load required files
include_once '../config/Database.php';
include_once '../models/User.php';
include_once '../models/Request.php';
include_once '../models/RequestType.php';
include_once '../models/Notification.php';
include_once '../middleware/AuthMiddleware.php';
include_once '../utils/EmailHandler.php';
include_once '../utils/FileHandler.php';
include_once '../utils/RateLimiter.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize rate limiter
$rateLimiter = new RateLimiter($db);

// Get the request URI and parse it
$request_uri = urldecode($_SERVER['REQUEST_URI']);
$uri_parts = parse_url($request_uri);
$path = $uri_parts['path'];

// Remove the base path if it exists
$base_path = '/UPANG LINK/api/';
$endpoint = str_replace($base_path, '', $path);
$uri = explode('/', trim($endpoint, '/'));

// Get client IP address
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

// Check rate limit
if (!$rateLimiter->checkLimit($ip_address, $endpoint)) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Rate limit exceeded. Please try again later.',
        'remaining_requests' => 0,
        'retry_after' => 3600 // 1 hour
    ]);
    exit();
}

// Add remaining requests to response headers
$remaining_requests = $rateLimiter->getRemainingRequests($ip_address, $endpoint);
header('X-RateLimit-Remaining: ' . $remaining_requests);

// If no specific endpoint is requested, return API info
if (empty($uri[0])) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Welcome to UPANG LINK API',
        'version' => $config['app']['version'],
        'endpoints' => [
            'auth' => [
                'admin' => [
                    'login' => '/auth/admin/login',
                    'register' => '/auth/admin/register',
                    'logout' => '/auth/admin/logout'
                ],
                'student' => [
                    'login' => '/auth/student/login',
                    'register' => '/auth/student/register',
                    'verify-email' => '/auth/student/verify-email',
                    'resend-verification' => '/auth/student/resend-verification',
                    'logout' => '/auth/student/logout'
                ]
            ],
            'requests' => '/requests',
            'users' => '/users'
        ]
    ]);
    exit();
}

// Get request method
$requestMethod = $_SERVER["REQUEST_METHOD"];

// Initialize auth middleware
$authMiddleware = new AuthMiddleware($db);

try {
    // Basic routing
    switch($uri[0]) {
        case 'auth':
            include_once '../controllers/AuthController.php';
            $controller = new AuthController($db);
            break;
        case 'requests':
            // Check authentication before proceeding
            if(!$authMiddleware->handle($requestMethod, $uri)) {
                exit();
            }
            include_once '../controllers/RequestController.php';
            $controller = new RequestController($db);
            break;
        case 'users':
            // Check authentication before proceeding
            if(!$authMiddleware->handle($requestMethod, $uri)) {
                exit();
            }
            include_once '../controllers/UserController.php';
            $controller = new UserController($db);
            break;
        default:
            throw new Exception('Endpoint not found');
    }

    // Handle the request
    $controller->handleRequest($requestMethod, $uri);
} catch (Exception $e) {
    $status_code = $e->getCode() ?: 500;
    http_response_code($status_code);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $status_code
    ]);
    
    // Log error
    error_log("Error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
} 