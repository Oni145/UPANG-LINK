<?php
// Simple, focused test for forgot password email functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file we can check
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error-log.txt');

// Load necessary files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/utils/EmailHandler.php';

// Connect to database - similar to how it happens in the API
$config = require_once __DIR__ . '/config/config.php';
$host = $config['database']['host'];
$db_name = $config['database']['name'];
$username = $config['database']['username'];
$password = $config['database']['password'];

try {
    echo "<h1>Simple Email Test for Forgot Password</h1>";
    
    // Connect to the database
    echo "<h2>Database Connection</h2>";
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p>Database connection successful!</p>";
    } catch(PDOException $e) {
        echo "<p>Database connection failed: " . $e->getMessage() . "</p>";
        error_log("Database connection failed: " . $e->getMessage());
        die();
    }

    // Test User Model Functions
    echo "<h2>Testing User Model</h2>";
    $email = "jerickogarcia0@gmail.com"; // The email you're trying to reset
    
    // Create User model
    $user = new User($conn);
    
    // Get user by email
    $userData = $user->getByEmail($email);
    if ($userData) {
        echo "<p>User found: ID {$userData['user_id']}, Name: {$userData['first_name']} {$userData['last_name']}</p>";
        
        // Set user_id and test generating reset token
        $user->user_id = $userData['user_id'];
        $tokenResult = $user->generateResetToken();
        
        if ($tokenResult) {
            echo "<p>Reset token generated successfully: " . substr($user->reset_password_token, 0, 10) . "...</p>";
            
            // Now test sending the email
            echo "<h2>Testing Email Handler</h2>";
            try {
                $emailHandler = new \App\Utils\EmailHandler();
                $result = $emailHandler->sendResetPasswordEmail($email, $user->reset_password_token);
                
                if ($result) {
                    echo "<p>Email sent successfully!</p>";
                } else {
                    echo "<p>Failed to send email.</p>";
                }
            } catch (Exception $e) {
                echo "<p>Email handler exception: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
                error_log("Email handler exception: " . $e->getMessage());
            }
        } else {
            echo "<p>Failed to generate reset token</p>";
            error_log("Failed to generate reset token for user ID: " . $userData['user_id']);
        }
    } else {
        echo "<p>User not found with email: $email</p>";
        error_log("User not found with email: $email");
    }
    
    // Check the current configuration
    echo "<h2>Current Email Configuration</h2>";
    echo "<pre>";
    echo "Base URL: " . $config['app']['base_url'] . "\n";
    echo "Frontend URL: " . $config['app']['frontend_url'] . "\n";
    echo "API URL: " . $config['app']['api_url'] . "\n";
    echo "Host: " . $config['email']['host'] . "\n";
    echo "Port: " . $config['email']['port'] . "\n";
    echo "Username: " . $config['email']['username'] . "\n";
    echo "SMTP Auth: " . ($config['email']['smtp_auth'] ? 'true' : 'false') . "\n";
    echo "SMTP Secure: " . $config['email']['smtp_secure'] . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    error_log("General exception in simple email test: " . $e->getMessage());
}
?> 