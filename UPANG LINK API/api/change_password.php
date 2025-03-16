<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_log("Starting change_password.php script");

try {
    include_once '../config/database.php';
    include_once '../models/User.php';
    include_once '../middleware/AuthMiddleware.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    error_log("Database connection established");

    // Instantiate user object
    $user = new User($db);
    error_log("User object created");

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Debug: Log the received data
    error_log("Received data: " . print_r($data, true));

    // Check if token is provided
    $headers = getallheaders();
    error_log("Headers: " . print_r($headers, true));
    
    $auth_middleware = new AuthMiddleware($db);
    error_log("AuthMiddleware object created");

    // Validate token
    $is_valid_token = $auth_middleware->validateToken($headers);
    error_log("Token validation result: " . print_r($is_valid_token, true));

    if (!$is_valid_token['is_valid']) {
        // Set response code - 401 Unauthorized
        http_response_code(401);
        
        // Tell the user access denied
        echo json_encode(array(
            "status" => "error",
            "message" => "Access denied. " . $is_valid_token['message']
        ));
        exit();
    }

    // Check if all required fields are provided
    if (
        isset($data->currentPassword) && !empty($data->currentPassword) &&
        isset($data->newPassword) && !empty($data->newPassword) &&
        isset($data->confirmPassword) && !empty($data->confirmPassword)
    ) {
        // Debug: Log that all fields are present
        error_log("All required fields are present");
        
        // Set user properties
        $user_id = $is_valid_token['user_id']; // Get user ID from token
        error_log("Set user_id to: " . $user_id);
        
        // Check if new password and confirm password match
        if ($data->newPassword !== $data->confirmPassword) {
            // Set response code - 400 Bad Request
            http_response_code(400);
            
            // Tell the user
            echo json_encode(array(
                "status" => "error",
                "message" => "New password and confirm password do not match."
            ));
            exit();
        }
        
        // Debug: Log before verifying password
        error_log("About to verify password for user_id: " . $user_id);
        
        // Verify current password directly
        $query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $is_valid_password = password_verify($data->currentPassword, $row['password']);
            
            if (!$is_valid_password) {
                // Set response code - 400 Bad Request
                http_response_code(400);
                
                // Tell the user
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Current password is incorrect."
                ));
                exit();
            }
        } else {
            // Set response code - 404 Not Found
            http_response_code(404);
            
            // Tell the user
            echo json_encode(array(
                "status" => "error",
                "message" => "User not found."
            ));
            exit();
        }
        
        // Update password directly
        $hashed_password = password_hash($data->newPassword, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":password", $hashed_password);
        $update_stmt->bindParam(":user_id", $user_id);
        
        if ($update_stmt->execute()) {
            // Set response code - 200 OK
            http_response_code(200);
            
            // Tell the user
            echo json_encode(array(
                "status" => "success",
                "message" => "Password was changed successfully."
            ));
        } else {
            // Set response code - 503 Service Unavailable
            http_response_code(503);
            
            // Tell the user
            echo json_encode(array(
                "status" => "error",
                "message" => "Unable to change password."
            ));
        }
    } else {
        // Debug: Log which fields are missing
        error_log("Missing fields: " . 
                "currentPassword=" . (isset($data->currentPassword) ? "set" : "not set") . ", " .
                "newPassword=" . (isset($data->newPassword) ? "set" : "not set") . ", " .
                "confirmPassword=" . (isset($data->confirmPassword) ? "set" : "not set"));
        
        // Set response code - 400 Bad Request
        http_response_code(400);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to change password. Data is incomplete."
        ));
    }
} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "An error occurred: " . $e->getMessage()
    ));
}
?> 