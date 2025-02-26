<?php
require_once '../config/Database.php';
require_once '../models/User.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create User instance
$user = new User($db);

// Get Jericko's account
$jericko = $user->getByEmail('jerickogarcia0@gmail.com');

if ($jericko) {
    echo "Found Jericko's account:\n";
    echo "Password hash in DB: " . $jericko['password'] . "\n\n";
    
    // Test password verification
    $test_password = 'password';
    $verification_result = password_verify($test_password, $jericko['password']);
    
    echo "Testing password 'password':\n";
    echo "Verification result: " . ($verification_result ? "SUCCESS" : "FAILED") . "\n\n";
    
    // Generate a new hash for comparison
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    echo "New hash generated for 'password': " . $new_hash . "\n";
} else {
    echo "Could not find Jericko's account";
} 