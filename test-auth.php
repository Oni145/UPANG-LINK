<?php
// Include the JWT helper
require_once 'UPANG LINK API/helpers/jwt_helper.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>JWT Token Test</h1>";

// A test token
$test_token = "test_token_string";

try {
    // Try to decode the token
    echo "<p>Attempting to decode token: {$test_token}</p>";
    
    $decoded = JWT::decode($test_token);
    
    echo "<p style='color: green;'>Token decoded successfully!</p>";
    echo "<p>User ID: " . $decoded->user_id . "</p>";
    
    // Print the full response object for inspection
    echo "<h2>Complete Response:</h2>";
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 