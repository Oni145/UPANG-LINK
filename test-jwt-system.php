<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the JWT helper
require_once 'UPANG LINK API/helpers/jwt_helper.php';

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>JWT Authentication System Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
        code {
            font-family: monospace;
        }
        .token-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            margin: 10px 0;
        }
        .nav {
            margin-bottom: 20px;
        }
        .nav a {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav a:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>';

echo "<h1>JWT Authentication System Test</h1>";

echo "<div class='nav'>
    <a href='generate-token.php'>Generate Token</a>
    <a href='test-protected-endpoint.php?token=" . urlencode(JWT::generate(3)) . "'>Test Protected Endpoint</a>
    <a href='UPANG LINK API/docs/jwt-authentication.md'>View Documentation</a>
</div>";

// Test JWT generation
echo "<div class='section'>";
echo "<h2>1. JWT Token Generation Test</h2>";

try {
    // Generate a token for user ID 3
    $token = JWT::generate(3, [
        'email' => 'test@example.com',
        'role' => 'student'
    ]);
    
    echo "<div class='test-result success'>Token generated successfully!</div>";
    echo "<h3>Generated Token:</h3>";
    echo "<div class='token-box'>" . $token . "</div>";
    
    echo "<h3>Token Structure:</h3>";
    $token_parts = explode('.', $token);
    
    echo "<table>
            <tr>
                <th>Part</th>
                <th>Content</th>
                <th>Decoded Value</th>
            </tr>
            <tr>
                <td>Header</td>
                <td><code>" . $token_parts[0] . "</code></td>
                <td><pre>" . json_encode(json_decode(base64_decode(strtr($token_parts[0], '-_', '+/'))), JSON_PRETTY_PRINT) . "</pre></td>
            </tr>
            <tr>
                <td>Payload</td>
                <td><code>" . $token_parts[1] . "</code></td>
                <td><pre>" . json_encode(json_decode(base64_decode(strtr($token_parts[1], '-_', '+/'))), JSON_PRETTY_PRINT) . "</pre></td>
            </tr>
            <tr>
                <td>Signature</td>
                <td colspan='2'><code>" . $token_parts[2] . "</code></td>
            </tr>
        </table>";
    
} catch (Exception $e) {
    echo "<div class='test-result error'>Error generating token: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test JWT decoding
echo "<div class='section'>";
echo "<h2>2. JWT Token Decoding Test</h2>";

try {
    // Decode the token we just generated
    $decoded = JWT::decode($token);
    
    echo "<div class='test-result success'>Token decoded successfully!</div>";
    echo "<h3>Decoded Token Data:</h3>";
    echo "<pre>" . print_r($decoded, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<div class='test-result error'>Error decoding token: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test token validation
echo "<div class='section'>";
echo "<h2>3. JWT Token Validation Tests</h2>";

// Test 1: Valid token
echo "<h3>3.1 Valid Token</h3>";
if (JWT::validate($token)) {
    echo "<div class='test-result success'>Valid token test passed!</div>";
} else {
    echo "<div class='test-result error'>Valid token test failed!</div>";
}

// Test 2: Invalid format token
echo "<h3>3.2 Invalid Format Token</h3>";
$invalid_token = "invalid.token.format";
if (!JWT::validate($invalid_token)) {
    echo "<div class='test-result success'>Invalid format token test passed!</div>";
} else {
    echo "<div class='test-result error'>Invalid format token test failed!</div>";
}

// Test 3: Tampered token
echo "<h3>3.3 Tampered Token</h3>";
$token_parts = explode('.', $token);
$tampered_token = $token_parts[0] . '.' . $token_parts[1] . '.tampered_signature';
if (!JWT::validate($tampered_token)) {
    echo "<div class='test-result success'>Tampered token test passed!</div>";
} else {
    echo "<div class='test-result error'>Tampered token test failed!</div>";
}

echo "</div>";

// Test JWT in API requests
echo "<div class='section'>";
echo "<h2>4. JWT in API Requests</h2>";

echo "<h3>Example API Request with Token:</h3>";
echo "<pre>GET /api/example-protected.php HTTP/1.1
Host: " . $_SERVER['HTTP_HOST'] . "
Authorization: Bearer " . $token . "</pre>";

echo "<p>You can test this by clicking the link below:</p>";
echo "<p><a href='UPANG LINK API/api/example-protected.php?token=" . urlencode($token) . "' target='_blank'>Test Protected API Endpoint</a></p>";

echo "</div>";

// Documentation and next steps
echo "<div class='section'>";
echo "<h2>5. How to Use JWT Authentication</h2>";

echo "<h3>In your API endpoints:</h3>";
echo "<pre>
&lt;?php
// Include the JWT authentication middleware
require_once __DIR__ . '/../middleware/jwt_auth.php';

// Require authentication (this will exit if authentication fails)
\$user_id = requireAuth();

// If we get here, the user is authenticated
// Now you can use \$user_id in your code
</pre>";

echo "<h3>In your frontend code:</h3>";
echo "<pre>
// Making an authenticated request
fetch('/api/your-endpoint', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('token')
    }
})
.then(response => response.json())
.then(data => {
    console.log(data);
});
</pre>";

echo "</div>";

echo "</body></html>";
?> 