<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include the required files
include_once __DIR__ . '/../config/Database.php';
include_once __DIR__ . '/../models/Admin.php';

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
    
    // Create Admin model
    $admin = new Admin($db);
    
    // Test getting admin by username
    $testUsername = 'admin@admin.com';
    $result = $admin->getByUsername($testUsername);
    
    // Output the result
    echo json_encode([
        'status' => 'success',
        'message' => 'Admin test completed',
        'database_connected' => true,
        'admin_test' => [
            'username_tested' => $testUsername,
            'admin_found' => $result ? true : false,
            'admin_details' => $result ? [
                'user_id' => $result['user_id'] ?? null,
                'email' => $result['email'] ?? null,
                'role' => $result['role'] ?? null,
                'first_name' => $result['first_name'] ?? null,
                'last_name' => $result['last_name'] ?? null
            ] : null
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage()
    ]);
}
?> 