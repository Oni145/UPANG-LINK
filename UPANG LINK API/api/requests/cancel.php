<?php
// Prevent PHP errors from being displayed as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Start session
session_start();

// Define the root path and use it for includes
define('API_ROOT', str_replace('\\', '/', realpath(dirname(dirname(dirname(__FILE__))))));
require_once API_ROOT . '/config/Database.php';
require_once API_ROOT . '/models/User.php';
require_once API_ROOT . '/models/Request.php';
require_once API_ROOT . '/helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit();
}

// Check for authentication token
$headers = getallheaders();
$user_id = null;
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (strpos($authorization, 'Bearer ') === 0) {
    $token = substr($authorization, 7);
    
    try {
        // Decode the token and extract user ID
        $decoded = JWT::decode($token);
        if (isset($decoded->user_id)) {
            $user_id = $decoded->user_id;
        } else {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid token: missing user_id'
            ]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired token'
        ]);
        exit;
    }
} else {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authorization token required'
    ]);
    exit;
}

try {
    // Get request data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->request_id)) {
        throw new Exception("Request ID is required", 400);
    }
    
    // Initialize request model
    $request = new Request($conn);
    
    // Cancel the request
    $result = $request->cancel($data->request_id, $user_id);
    
    if ($result) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Request cancelled successfully',
            'data' => $result
        ]);
    } else {
        throw new Exception("Failed to cancel request", 500);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 