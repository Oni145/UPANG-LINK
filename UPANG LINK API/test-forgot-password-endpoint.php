<?php
// Test script to call the forgot password API endpoint

// Set up error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API endpoint URL
$url = 'http://localhost/UPANG-LINK/UPANG%20LINK%20API/api/auth/student/forgot-password';

// Test data 
$data = [
    'email' => 'jerickogarcia0@gmail.com'
];

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);

// Execute cURL request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Close cURL
curl_close($ch);

// Display results
echo "<h1>Test Results</h1>";
echo "<h2>Request</h2>";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

echo "<h2>Response (HTTP Code: $httpCode)</h2>";
if ($error) {
    echo "<p>Error: $error</p>";
} else {
    echo "<pre>" . (is_string($response) ? json_encode(json_decode($response), JSON_PRETTY_PRINT) : print_r($response, true)) . "</pre>";
}

// Also log to the error log for debugging
error_log("API Test - Request: " . json_encode($data));
error_log("API Test - Response: " . $response);
?> 