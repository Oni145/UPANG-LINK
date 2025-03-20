<?php
// Simple test script to verify the modified EmailHandler class

// Include required files
require_once __DIR__ . '/utils/EmailHandler.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/User.php';

// Set up error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create EmailHandler instance
    $emailHandler = new App\Utils\EmailHandler();
    
    // Test sending email
    $result = $emailHandler->sendResetPasswordEmail('test@example.com', 'test-token-123');
    
    echo "Email test result: " . ($result ? "Success" : "Failed") . "\n";
    echo "Check the error logs for more details.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
    
    if ($e->getPrevious()) {
        echo "Previous error: " . $e->getPrevious()->getMessage() . "\n";
    }
}

echo "Test completed.\n";
?> 