<?php
/**
 * Login API Endpoint
 * 
 * This endpoint authenticates users and issues JWT tokens
 */

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include necessary files
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/jwt_helper.php';

// Generate a unique response ID for logging
$responseId = uniqid('resp_');
error_log("Starting login endpoint. Response ID: " . $responseId);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'code' => 405
    ]);
    exit;
}

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

// Get the request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required',
        'code' => 400
    ]);
    exit;
}

// Create User object
$user = new User($pdo);

try {
    // Attempt to authenticate the user
    $user_data = $user->authenticate($data['email'], $data['password']);
    
    if (!$user_data) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password',
            'code' => 401
        ]);
        exit;
    }
    
    // User is authenticated, generate a JWT token
    $token_data = [
        'email' => $user_data['email'],
        'role' => $user_data['role'] ?? 'student',
        'name' => $user_data['first_name'] . ' ' . $user_data['last_name']
    ];
    
    $token = JWT::generate($user_data['user_id'], $token_data);
    
    // Return the token along with user info
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user_data['user_id'],
                'email' => $user_data['email'],
                'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
                'role' => $user_data['role'] ?? 'student'
            ]
        ],
        'code' => 200
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error: ' . $e->getMessage(),
        'code' => 500
    ]);
}
?> 