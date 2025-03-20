<?php
// Test script for email functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils/EmailHandler.php';

use App\Utils\EmailHandler;

try {
    echo "<h1>Email Test Script</h1>";
    
    // Test the EmailHandler
    echo "<h2>Testing EmailHandler</h2>";
    $emailHandler = new EmailHandler();
    $result = $emailHandler->sendVerificationEmail('jerickogarcia0@gmail.com', 'test-token-12345');
    
    if ($result) {
        echo "<p>Email sent successfully via EmailHandler!</p>";
    } else {
        echo "<p>Failed to send email via EmailHandler.</p>";
    }
    
    // Log the config values (without showing password)
    echo "<h2>Current Configuration</h2>";
    $config = require __DIR__ . '/config/config.php';
    echo "<pre>";
    echo "Host: " . ($config['email']['host'] ?? 'Not set') . "\n";
    echo "Port: " . ($config['email']['port'] ?? 'Not set') . "\n";
    echo "Username: " . ($config['email']['username'] ?? 'Not set') . "\n";
    echo "From Name: " . ($config['email']['from_name'] ?? 'Not set') . "\n";
    echo "From Email: " . ($config['email']['from_email'] ?? 'Not set') . "\n";
    
    // Check PHP mail() function availability
    echo "\nPHP mail() function: ";
    echo function_exists('mail') ? "Available" : "Not available";
    
    // Check PHPMailer class availability
    echo "\nPHPMailer class: ";
    echo class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? "Available" : "Not available";
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
} 