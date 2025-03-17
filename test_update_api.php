<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// First, let's create a valid token by logging in
echo "Step 1: Creating a valid token by logging in...\n";

// Include database configuration
include_once 'UPANG LINK API/config/database.php';
include_once 'UPANG LINK API/models/User.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Get a user from the database
$stmt = $db->query("SELECT user_id, email FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "No users found in the database. Cannot proceed with testing.\n";
    exit;
}

echo "Using user: " . $user['email'] . " (ID: " . $user['user_id'] . ")\n";

// Create a token manually
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));
$device_info = 'Test Script';
$ip_address = '127.0.0.1';

// Insert token into user_sessions table
$stmt = $db->prepare("INSERT INTO user_sessions (user_id, token, device_info, ip_address, expires_at) 
                     VALUES (:user_id, :token, :device_info, :ip_address, :expires_at)");
$stmt->bindParam(':user_id', $user['user_id']);
$stmt->bindParam(':token', $token);
$stmt->bindParam(':device_info', $device_info);
$stmt->bindParam(':ip_address', $ip_address);
$stmt->bindParam(':expires_at', $expires_at);

if ($stmt->execute()) {
    echo "Token created successfully: " . $token . "\n\n";
} else {
    echo "Failed to create token.\n";
    exit;
}

// Now test the update_student_details.php endpoint
echo "Step 2: Testing update_student_details.php endpoint...\n";

// Test configuration
$api_url = "http://localhost/UPANG-LINK/UPANG%20LINK%20API/api/update_student_details.php";

// Test data
$data = array(
    "student_number" => "2023-12345",
    "birthdate" => "2000-01-01",
    "emergency_contact" => "John Doe - 09123456789",
    "course" => "BS Computer Science",
    "current_year" => "3rd Year"
);

// Initialize cURL session
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $token,
    "Content-Type: application/json"
]);

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit;
}

// Close cURL session
curl_close($ch);

// Output response
echo "HTTP Status Code: " . $http_code . "\n\n";
echo "Raw Response:\n" . $response . "\n\n";

// Parse JSON response
$response_data = json_decode($response, true);

// Check if JSON parsing was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Parsing Error: " . json_last_error_msg() . "\n";
    
    // Try to clean the response and parse again
    $cleaned_response = preg_replace('/^[^{]*/', '', $response);
    echo "\nAttempting to clean response and parse again...\n";
    $response_data = json_decode($cleaned_response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Still failed to parse JSON after cleaning: " . json_last_error_msg() . "\n";
        exit;
    } else {
        echo "Successfully parsed JSON after cleaning.\n\n";
    }
}

// Display parsed data
echo "Parsed Response:\n";
print_r($response_data);

// Step 3: Verify the update by getting student details
echo "\nStep 3: Verifying the update by getting student details...\n";

// Test configuration for get_student_details
$get_api_url = "http://localhost/UPANG-LINK/UPANG%20LINK%20API/api/get_student_details.php";

// Initialize cURL session
$ch = curl_init($get_api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $token,
    "Content-Type: application/json"
]);

// Execute cURL request
$get_response = curl_exec($ch);
$get_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL session
curl_close($ch);

// Output response
echo "HTTP Status Code: " . $get_http_code . "\n\n";
echo "Raw Response:\n" . $get_response . "\n\n";

// Parse JSON response
$get_data = json_decode($get_response, true);

// Display parsed data
echo "Parsed Response:\n";
print_r($get_data);

// Clean up - remove the test token
$stmt = $db->prepare("DELETE FROM user_sessions WHERE token = :token");
$stmt->bindParam(':token', $token);
$stmt->execute();
echo "\nTest token removed from database.\n";
?> 