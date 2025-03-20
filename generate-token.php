<?php
// Include the JWT helper
require_once 'UPANG LINK API/helpers/jwt_helper.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 3; // Default to user ID 3

// Generate a token
$token = JWT::generate($user_id, [
    'email' => 'test@example.com',
    'role' => 'student'
]);

// HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>JWT Token Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #333;
        }
        .token-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            margin: 20px 0;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .info {
            color: blue;
        }
        .code {
            font-family: monospace;
            background-color: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
        }
        hr {
            margin: 30px 0;
            border: 0;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>';

echo "<h1>JWT Token Generator</h1>";

echo "<h2>Generated Token</h2>";
echo "<p>The following token has been generated for user ID: <strong>{$user_id}</strong></p>";
echo "<div class='token-box'>" . $token . "</div>";

echo "<h2>Generate for Different User ID</h2>";
echo "<form method='get'>
    <label for='user_id'>User ID:</label>
    <input type='number' name='user_id' id='user_id' value='{$user_id}'>
    <input type='submit' value='Generate Token'>
</form>";

echo "<h2>Test the Generated Token</h2>";
echo "<p>Now let's verify that the token works properly:</p>";

try {
    // Try to decode the token we just generated
    $decoded = JWT::decode($token);
    
    echo "<p class='success'>Token verified successfully!</p>";
    echo "<h3>Decoded Token Data:</h3>";
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>How to Use This Token</h2>";
echo "<p>You can use this token in your API requests by including it in the Authorization header:</p>";
echo "<p class='code'>Authorization: Bearer " . $token . "</p>";

echo "<p>Or for testing, you can append it as a URL parameter:</p>";
echo "<p class='code'>?token=" . urlencode($token) . "</p>";

echo "<h2>Example API Call</h2>";
echo "<p>Try this URL to test the token with the protected endpoint:</p>";
echo "<p><a href='test-protected-endpoint.php?token=" . urlencode($token) . "' target='_blank'>test-protected-endpoint.php?token=" . urlencode($token) . "</a></p>";

echo '</body></html>';
?> 