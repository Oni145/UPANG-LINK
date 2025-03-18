<?php
/**
 * Request Details API Endpoint
 * 
 * This endpoint provides detailed information about a specific request,
 * including its type, requirements, and status.
 * GET: View request details with type-specific information
 */

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

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
error_log("Starting request details endpoint. Response ID: " . $responseId);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if tracking number is provided
$tracking_number = null;

// Extract tracking number from URL path
$request_uri = $_SERVER['REQUEST_URI'];
error_log("Full request URI: " . $request_uri);

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
// Check if tracking number is provided in query parameters
else if (isset($_GET['id'])) {
    $tracking_number = $_GET['id'];
    error_log("Got tracking number from query parameter: " . $tracking_number);
} else {
    error_log("No tracking number found in request URI or query parameters");
}

if (!$tracking_number) {
    error_log("No tracking number found in request");
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Request ID is required',
        'code' => 400
    ]);
    exit;
}

// Validate tracking number format - accept both old and new formats
if (!preg_match('/^REQ-\d{8}-\d{4}$/', $tracking_number) && !preg_match('/^REQ-\d{4}-\d{3}$/', $tracking_number)) {
    error_log("Invalid tracking number format: " . $tracking_number);
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request ID format. Expected format: REQ-YYYYMMDD-XXXX or REQ-YYYY-XXX',
        'code' => 400
    ]);
    exit;
}

// For testing purposes, hardcode the user ID
$user_id = 2;
error_log("Using hardcoded user ID: " . $user_id);

// Create request object
$request = new Request($pdo);

// Handle request based on method
$method = $_SERVER['REQUEST_METHOD'];
error_log("Processing request method: " . $method);

if ($method === 'GET') {
    // Get request details
    try {
        error_log("Getting request details for tracking number: " . $tracking_number . " and user ID: " . $user_id);
        $result = $request->getRequestDetailsWithType($tracking_number, $user_id, false);
        
        if (!$result) {
            error_log("Request not found or does not belong to user: " . $user_id);
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Request not found or does not belong to you',
                'code' => 404
            ]);
            exit;
        }
        
        // Check if request is pending to determine if it can be edited
        $canEdit = $result['status'] === 'PENDING';
        error_log("Request found. Status: " . $result['status'] . ", Can edit: " . ($canEdit ? 'true' : 'false'));
        
        echo json_encode([
            'status' => 'success',
            'data' => $result,
            'can_edit' => $canEdit,
            'code' => 200
        ]);
    } catch (Exception $e) {
        error_log("Error getting request details: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch request details: ' . $e->getMessage(),
            'code' => 500
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'code' => 405
    ]);
} 