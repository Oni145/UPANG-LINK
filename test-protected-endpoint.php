<?php
// Include the JWT helper
require_once 'UPANG LINK API/helpers/jwt_helper.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get bearer token from Authorization header
function getBearerToken() {
    $headers = getallheaders();
    
    // Check if the Authorization header exists
    if (isset($headers['Authorization'])) {
        // Extract the token
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    
    // Check if token is passed as a GET parameter (for testing)
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }
    
    return null;
}

// Function to authenticate user
function authenticateUser() {
    $token = getBearerToken();
    
    if (!$token) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'No token provided']);
        exit();
    }
    
    try {
        // Decode the token
        $decoded = JWT::decode($token);
        return $decoded;
    } catch (Exception $e) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
        exit();
    }
}

// Get the request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Simulate a simple protected API endpoint
if ($request_method === 'GET') {
    // Authenticate the user
    $user = authenticateUser();
    
    // If we get here, the user is authenticated
    $response = [
        'status' => 'success',
        'message' => 'This is protected data',
        'user_id' => $user->user_id,
        'authenticated' => true,
        'token_data' => [
            'user_id' => $user->user_id,
            'email' => $user->email ?? 'not provided',
            'role' => $user->role ?? 'not provided',
            'exp' => $user->exp ?? 'not provided'
        ],
        'protected_data' => [
            'item_1' => 'Confidential information',
            'item_2' => 'More secret data',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Return the response
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    // Method not allowed
    header('HTTP/1.0 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
}
?> 