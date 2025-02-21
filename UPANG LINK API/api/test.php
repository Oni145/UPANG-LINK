<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../utils/EmailHandler.php';

try {
    $emailHandler = new EmailHandler();
    
    // Send a test email using the verification email method
    $to_email = isset($_GET['email']) ? $_GET['email'] : 'jerickogarcia0@gmail.com';
    $test_token = 'test_' . time(); // Create a dummy token for testing
    
    $result = $emailHandler->sendVerificationEmail($to_email, $test_token);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Test verification email sent successfully to ' . $to_email
        ]);
    } else {
        throw new Exception('Failed to send email');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error sending test email: ' . $e->getMessage()
    ]);
} 