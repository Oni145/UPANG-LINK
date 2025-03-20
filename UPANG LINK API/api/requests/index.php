<?php
// Prevent PHP errors from being displayed as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include necessary files
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Request.php';
require_once __DIR__ . '/../../models/User.php';

// Generate a unique response ID for logging
$responseId = uniqid('resp_');
error_log("Starting requests endpoint. Response ID: " . $responseId);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

// Check for authentication token
$headers = getallheaders();
error_log("Request headers: " . json_encode($headers));

// Debug: Log all headers
foreach ($headers as $name => $value) {
    error_log("Header: $name = $value");
}

// Extract user ID from JWT token
$user_id = null;
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (strpos($authorization, 'Bearer ') === 0) {
    $token = substr($authorization, 7);
    
    // Include JWT helper
    $jwt_helper_path = __DIR__ . '/../../helpers/jwt_helper.php';
    if (!file_exists($jwt_helper_path)) {
        error_log("JWT helper file not found at: " . $jwt_helper_path);
        $absolute_path = realpath(dirname(__FILE__) . '/../../helpers/jwt_helper.php');
        error_log("Absolute path: " . $absolute_path);
        if ($absolute_path && file_exists($absolute_path)) {
            require_once $absolute_path;
        } else {
            error_log("Could not find JWT helper with absolute path either.");
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: JWT helper not found'
            ]);
            exit;
        }
    } else {
        require_once $jwt_helper_path;
    }
    
    try {
        // Decode the token and extract user ID
        $decoded = JWT::decode($token);
        if (isset($decoded->user_id)) {
            $user_id = $decoded->user_id;
            error_log("Extracted user ID from token: " . $user_id);
        } else {
            error_log("Token does not contain user_id");
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid token: missing user_id'
            ]);
            exit;
        }
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired token'
        ]);
        exit;
    }
} else {
    error_log("No valid authorization token provided");
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authorization token required'
    ]);
    exit;
}

error_log("Using user ID from token: " . $user_id);

// Create request object
$request = new Request($pdo);

// Check if this is a request for a specific request by ID
$request_uri = $_SERVER['REQUEST_URI'];
$tracking_number = null;

// First try to match the new format: REQ-YYYYMMDD-XXXX
if (preg_match('/REQ-\d{8}-\d{4}/', $request_uri, $matches)) {
    $tracking_number = $matches[0];
    error_log("Extracted tracking number from URL (new format): " . $tracking_number);
} 
// Then try to match the old format: REQ-YYYY-XXX
else if (preg_match('/REQ-\d{4}-\d{3}/', $request_uri, $matches)) {
    $tracking_number = $matches[0];
    error_log("Extracted tracking number from URL (old format): " . $tracking_number);
}

// If a tracking number was found, get the details for that request
if ($tracking_number) {
    error_log("Getting request details for tracking number: " . $tracking_number . " and user ID: " . $user_id);
    try {
        $result = $request->getDetailsByTrackingNumber($tracking_number, $user_id);
        
        if (!$result) {
            error_log("Request not found or does not belong to user: " . $user_id);
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Request not found or does not belong to you'
            ]);
            exit;
        }
        
        // Check if request is pending to determine if it can be edited
        $canEdit = $result['status'] === 'PENDING';
        error_log("Request found. Status: " . $result['status'] . ", Can edit: " . ($canEdit ? 'true' : 'false'));
        
        echo json_encode([
            'status' => 'success',
            'data' => $result,
            'can_edit' => $canEdit
        ]);
    } catch (Exception $e) {
        error_log("Error getting request details: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch request details: ' . $e->getMessage()
        ]);
    }
} else {
    // If no tracking number was found, get all requests for the user
    try {
        $requests = $request->getAll($user_id);
        
        echo json_encode([
            'status' => 'success',
            'data' => $requests
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch requests: ' . $e->getMessage()
        ]);
    }
} 