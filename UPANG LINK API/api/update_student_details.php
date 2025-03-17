<?php
// Turn off all error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if (
    !empty($data->student_number) &&
    !empty($data->birthdate) &&
    !empty($data->emergency_contact) &&
    !empty($data->course) &&
    !empty($data->current_year)
) {
    // Set user property values
    $user->user_id = $user_data['user_id'];
    $user->student_number = $data->student_number;
    $user->birthdate = $data->birthdate;
    $user->emergency_contact = $data->emergency_contact;
    $user->course = $data->course;
    $user->current_year = $data->current_year;

    // Update the student details
    if ($user->updateStudentDetails()) {
        // Set response code - 200 OK
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array("status" => "success", "message" => "Student details updated successfully"));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array("status" => "error", "message" => "Unable to update student details"));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("status" => "error", "message" => "Unable to update student details. Data is incomplete."));
} 