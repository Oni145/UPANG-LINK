<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/DatabaseHandler.php';

try {
    $db = new DatabaseHandler();
    $conn = $db->getConnection();
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email = "jerickogarcia0@gmail.com";
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User already exists'
        ]);
        exit;
    }
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (email, first_name, last_name, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $firstName = "Jericko";
    $lastName = "Garcia";
    $password = password_hash("test123", PASSWORD_DEFAULT); // Sample password: test123
    
    $stmt->bind_param("ssss", $email, $firstName, $lastName, $password);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Test user created successfully',
            'data' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName
            ]
        ]);
    } else {
        throw new Exception('Failed to insert user');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 