<?php
// Prevent PHP errors from being displayed as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Start session
session_start();

// Define the root path and use it for includes
define('API_ROOT', str_replace('\\', '/', realpath(dirname(dirname(dirname(__FILE__))))));
require_once API_ROOT . '/config/Database.php';
require_once API_ROOT . '/middleware/AuthMiddleware.php';
require_once API_ROOT . '/models/User.php';

// Handle errors gracefully
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server configuration error: ' . $errstr,
        'error_type' => 'CONFIG_ERROR',
        'code' => 500
    ]);
    exit;
});

// Handle fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Fatal error: ' . $error['message'],
            'error_type' => 'FATAL_ERROR',
            'code' => 500
        ]);
        exit;
    }
});

// Database connection
$db_host = 'localhost';
$db_name = 'upang_link';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'code' => 500
    ]);
    exit;
}

// Get the authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

// Validate token
if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'No token provided',
        'code' => 401
    ]);
    exit;
}

$token = substr($authHeader, 7);
$user = new User($pdo);

try {
    $session = $user->validateSession($token);
    if (!$session['valid']) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired token',
            'code' => 401
        ]);
        exit;
    }
    $user_id = $session['user_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage(),
        'code' => 401
    ]);
    exit;
}

// Fetch requests with all related information
$query = "
    SELECT 
        r.request_id,
        CONCAT('REQ-', DATE_FORMAT(r.submitted_at, '%Y-'), LPAD(r.request_id, 3, '0')) as id,
        r.user_id,
        r.type_id,
        rt.name as document_type,
        r.status,
        r.submitted_at,
        r.updated_at,
        rt.name as request_type,
        rt.processing_time,
        u.first_name,
        u.last_name,
        c.name as category_name,
        rt.requirements,
        rn.note as remarks
    FROM requests r
    JOIN request_types rt ON r.type_id = rt.type_id
    JOIN categories c ON rt.category_id = c.category_id
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN request_notes rn ON r.request_id = rn.request_id
    WHERE r.user_id = :user_id
    ORDER BY r.submitted_at DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($requests)) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'No requests found',
            'code' => 404
        ]);
        exit;
    }

    // Format the response
    $formatted_requests = array_map(function($request) {
        // Convert status to uppercase to match Android app
        $request['status'] = strtoupper($request['status']);
        
        // Parse requirements JSON
        $requirements = json_decode($request['requirements'], true);
        
        // Create type object with properly formatted requirements
        $request['type'] = [
            'type_id' => $request['type_id'],
            'name' => $request['request_type'],
            'description' => $request['document_type'],
            'requirements' => $requirements,
            'processing_time' => $request['processing_time'],
            'category_name' => $request['category_name']
        ];

        return $request;
    }, $requests);

    echo json_encode([
        'status' => 'success',
        'data' => $formatted_requests
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch requests',
        'code' => 500
    ]);
} 