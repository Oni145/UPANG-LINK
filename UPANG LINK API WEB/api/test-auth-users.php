<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include the required files
include_once __DIR__ . '/../config/Database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../controllers/AuthController.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if database connection is successful
    if (!$db) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    // Mock authorization header with the token from the request
    $token = isset($_GET['token']) ? $_GET['token'] : null;
    if (!$token) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Token is required as a query parameter'
        ]);
        exit;
    }
    
    // Set the mock authorization header
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    
    // Create controller and call getUsers
    $controller = new AuthController($db);
    $controller->handleRequest('GET', ['users']);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage()
    ]);
}
?> 