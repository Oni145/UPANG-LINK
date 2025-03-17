<?php
// Turn off all error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../config/database.php';
include_once '../models/User.php';
include_once '../middleware/auth.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);

// Check if user is authenticated
$auth = new Auth($db);
$user_data = $auth->validateToken();

if (!$user_data['is_valid']) {
    http_response_code(401);
    echo json_encode(array("status" => "error", "message" => "Unauthorized"));
    exit();
}

// Set user ID
$user->user_id = $user_data['user_id'];

// Get user data
$userData = $user->readOne();

if ($userData) {
    // Create response array with safe defaults for optional fields
    $response = array(
        "status" => "success",
        "data" => array(
            "user_id" => $userData['user_id'],
            "email" => $userData['email'],
            "first_name" => $userData['first_name'],
            "last_name" => $userData['last_name'],
            "role" => $userData['role'],
            "student_number" => isset($userData['student_number']) ? $userData['student_number'] : null,
            "birthdate" => isset($userData['birthdate']) ? $userData['birthdate'] : null,
            "emergency_contact" => isset($userData['emergency_contact']) ? $userData['emergency_contact'] : null,
            "course" => isset($userData['course']) ? $userData['course'] : null,
            "current_year" => isset($userData['current_year']) ? $userData['current_year'] : null,
            "email_verified" => $userData['email_verified'],
            "created_at" => $userData['created_at'],
            "updated_at" => $userData['updated_at']
        )
    );

    // Check if student details are complete (safely check for empty values)
    $isComplete = !empty($userData['student_number']) && 
                  !empty($userData['birthdate']) && 
                  !empty($userData['emergency_contact']) && 
                  !empty($userData['course']) && 
                  !empty($userData['current_year']);
    
    $response['data']['details_complete'] = $isComplete;

    // Set response code - 200 OK
    http_response_code(200);
    
    // Return the data
    echo json_encode($response);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user
    echo json_encode(array("status" => "error", "message" => "User not found"));
} 