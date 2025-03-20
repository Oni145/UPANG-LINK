<?php
// Include the JWT helper
require_once 'UPANG LINK API/helpers/jwt_helper.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test token from logs
$test_token = "3862714438c7d5c2f9854f27ca303ddbad81108919d1aecd8a31f2d24ac81ccb";

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Legacy Token Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #333;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .token-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            margin: 10px 0;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .info {
            background-color: #e7f3fe;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>';

echo "<h1>Legacy Token Test</h1>";

echo "<div class='section'>";
echo "<h2>Testing Token Format</h2>";

// Display the test token
echo "<p>Testing with token from logs:</p>";
echo "<div class='token-box'>" . $test_token . "</div>";

echo "<div class='info'>
This token is in the legacy format used by the UPANG LINK API. It's a simple hex string, 
not in the standard JWT format (header.payload.signature).
</div>";

echo "</div>";

echo "<div class='section'>";
echo "<h2>Decoding Legacy Token</h2>";

try {
    // Try to decode the token
    $decoded = JWT::decode($test_token);
    
    echo "<div class='test-result success'>Token decoded successfully!</div>";
    echo "<h3>Decoded Data:</h3>";
    echo "<pre>" . print_r($decoded, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<div class='test-result error'>Error decoding token: " . $e->getMessage() . "</div>";
    
    echo "<p>If you're seeing an error, it could be because:</p>";
    echo "<ul>
        <li>The token is no longer valid in the database</li>
        <li>The token has expired</li>
        <li>The database connection failed</li>
        <li>The token format is different than expected</li>
    </ul>";
    
    echo "<div class='info'>
    For testing purposes, you may want to get a fresh token by logging in through the mobile app 
    or using the login API directly, then update this test file with the new token.
    </div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>JWT System Status</h2>";

echo "<p>The JWT authentication system has been updated to handle both formats:</p>";
echo "<ol>
    <li><strong>Legacy Format:</strong> Simple hex string like <code>3862714438c7d5c2f9854f27ca303ddbad81108919d1aecd8a31f2d24ac81ccb</code></li>
    <li><strong>Standard JWT Format:</strong> Three-part token like <code>header.payload.signature</code></li>
</ol>";

echo "<p>The system will automatically detect which format is being used and handle it appropriately.</p>";

echo "<div class='info'>
For the legacy format, the token is validated by checking the <code>user_sessions</code> table in the database.
For the standard JWT format, the token is validated by checking the signature and expiration.
</div>";

echo "</div>";

echo "</body></html>";
?> 