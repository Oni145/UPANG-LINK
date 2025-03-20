<?php
/**
 * Example Protected API Endpoint
 * 
 * This is an example of how to secure an API endpoint with JWT authentication
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

// Include the JWT authentication middleware
require_once __DIR__ . '/../middleware/jwt_auth.php';

// Generate a unique response ID for logging
$responseId = uniqid('resp_');
error_log("Starting example protected endpoint. Response ID: " . $responseId);

// Require authentication (this will exit if authentication fails)
$user_id = requireAuth();

// If we get here, the user is authenticated
// Optionally get full user data from token
$user_data = getAuthUser();

// Handle request based on method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Return protected data
    echo json_encode([
        'status' => 'success',
        'message' => 'Authentication successful',
        'data' => [
            'user_id' => $user_id,
            'email' => $user_data->email ?? 'Not available',
            'role' => $user_data->role ?? 'Not available',
            'protected_data' => [
                'item_1' => 'This is protected data only accessible to authenticated users',
                'item_2' => 'User ID ' . $user_id . ' has accessed this endpoint',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ],
        'code' => 200
    ]);
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'code' => 405
    ]);
}
?> 