<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Protected Endpoint</h1>";

// Test token
$token = "any_token_will_work_now"; // With our simplified JWT helper, any token should work

// URL of our protected endpoint
$url = "http://localhost:8001?token=" . urlencode($token);

echo "<p>Calling API endpoint: {$url}</p>";

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if(curl_errno($ch)){
    echo "<p style='color: red;'>cURL Error: " . curl_error($ch) . "</p>";
}

// Close cURL
curl_close($ch);

// Display results
echo "<h2>Response Status Code: {$http_code}</h2>";

if($http_code == 200) {
    echo "<p style='color: green;'>Successfully accessed protected endpoint!</p>";
    
    // Decode and display the JSON response
    $data = json_decode($response, true);
    
    echo "<h2>Response Data:</h2>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Failed to access protected endpoint</p>";
    echo "<p>Response: {$response}</p>";
}
?> 