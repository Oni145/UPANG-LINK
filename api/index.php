<?php
session_start(); // Start session to persist rate-limiter data

// Set CORS and content-type headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Platform");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Platform");
    exit(0);
}

// Include required files
include_once __DIR__ . '/../config/Database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/Request.php';
include_once __DIR__ . '/../models/RequestType.php';
include_once __DIR__ . '/../models/AdminNotifications.php';  // Ensure Notification model is included
include_once __DIR__ . '/../controllers/AdminController.php';
include_once __DIR__ . '/../controllers/AuthController.php';
include_once __DIR__ . '/../controllers/RequestController.php';
include_once __DIR__ . '/../controllers/RequirementNoteController.php';
include_once __DIR__ . '/../controllers/AdminNotificationsController.php';  // New Controller for Admin Notifications

// Include the middleware pipeline class
include_once __DIR__ . '/../middleware/MiddlewarePipeline.php';

// Define a RequestCounter class to enforce a limit of 1000 posts per hour using session storage.
if (!class_exists('RequestCounter')) {
    class RequestCounter {
        private static $limit = 1000;         // Maximum allowed posts per hour.
        private static $window = 3600;         // Time window in seconds (1 hour).

        public static function checkAndIncrement() {
            $currentTime = time();
            if (!isset($_SESSION['request_counter'])) {
                $_SESSION['request_counter'] = [
                    'startTime' => $currentTime,
                    'count'     => 0
                ];
            }
            if (($currentTime - $_SESSION['request_counter']['startTime']) >= self::$window) {
                $_SESSION['request_counter']['startTime'] = $currentTime;
                $_SESSION['request_counter']['count'] = 0;
            }
            if ($_SESSION['request_counter']['count'] >= self::$limit) {
                error_log("Rate limit exceeded. Total requests in the last hour: " . $_SESSION['request_counter']['count']);
                return false;
            }
            $_SESSION['request_counter']['count']++;
            error_log("Request count incremented. Total: " . $_SESSION['request_counter']['count']);
            return true;
        }
    }
}

// Setup Database
$database = new Database();
$db = $database->getConnection();

// Parse Request URI
$request_uri = urldecode($_SERVER['REQUEST_URI']);
$uri_parts   = parse_url($request_uri);
$path        = $uri_parts['path'];
$base_path   = '/UPANG LINK';
$endpoint    = str_replace($base_path, '', $path);
$uri         = explode('/', trim($endpoint, '/'));

// Debug logging
error_log("Request URI: " . $request_uri);
error_log("Path: " . $path);
error_log("Endpoint: " . $endpoint);
error_log("URI: " . print_r($uri, true));

// If no endpoint is provided, return API info
if (!isset($uri[0]) || empty($uri[0])) {
    echo json_encode([
        'status'    => 'success',
        'message'   => 'Welcome to UPANG LINK API',
        'endpoints' => [
            'admin'    => '/admin',
            'requests' => '/requests',
            'students' => [
                'register' => '/auth/student/register',
                'login'    => '/auth/student/login',
            ],
            'notes'    => '/notes or /requests/notes',
        ]
    ]);
    exit();
}

// Determine which controller to use based on the first URI segment
$requestMethod = $_SERVER["REQUEST_METHOD"];
$controller    = null;

switch ($uri[0]) {
    case 'admin':
        $controller = new AdminController($db);
        array_unshift($uri, 'auth');
        break;

    case 'auth':
        $controller = new AuthController($db);
        array_shift($uri);
        break;

    case 'requests':
        if (isset($uri[1]) && strtolower($uri[1]) === 'notes') {
            $controller = new RequirementNoteController($db);
            array_shift($uri);
            array_shift($uri);
        } else {
            $controller = new RequestController($db);
        }
        break;
    
    case 'notes':
        $controller = new RequirementNoteController($db);
        array_shift($uri);
        break;

    default:
        header("HTTP/1.1 404 Not Found");
        echo json_encode([
            'status'  => 'error',
            'message' => 'Endpoint not found',
            'debug'   => [
                'request_uri' => $request_uri,
                'path'        => $path,
                'endpoint'    => $endpoint,
                'uri'         => $uri
            ]
        ]);
        exit();
}

// Define the final handler that calls the controller's handleRequest method.
$finalHandler = function($request) use ($controller, $requestMethod) {
    $controller->handleRequest($requestMethod, $request['endpoint']);
};

// Create the middleware pipeline with the final handler.
$pipeline = new MiddlewarePipeline($finalHandler);

// Middleware 1: Log the request endpoint.
$pipeline->add(function($request, $next) {
    if (isset($request['endpoint'][0])) {
        error_log("Middleware Log: Processing endpoint " . implode('/', $request['endpoint']));
    }
    return $next($request);
});

// Middleware 2: Check for an Authorization header on protected endpoints (users and requests).
$pipeline->add(function($request, $next) {
    if (isset($request['endpoint'][0]) && in_array($request['endpoint'][0], ['users', 'requests'])) {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : getallheaders();
        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Authorization token not provided'
            ]);
            exit();
        }
    }
    return $next($request);
});

// Middleware 3: Rate-limit only POST requests for creating a request (excluding posting notes).
$pipeline->add(function($request, $next) {
    if (isset($request['endpoint'][0]) && $request['endpoint'][0] === 'requests' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($request['endpoint'][1]) || strtolower($request['endpoint'][1]) !== 'notes') {
            if (!RequestCounter::checkAndIncrement()) {
                http_response_code(429);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Rate limit exceeded. Maximum 1000 posts per hour allowed.'
                ]);
                exit();
            }
        }
    }
    return $next($request);
});

// Prepare request data for the middleware.
$requestData = [
    'endpoint' => $uri
];

// Run the middleware pipeline.
$pipeline->run($requestData);
?>
