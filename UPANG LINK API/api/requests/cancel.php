<?php
// Prevent PHP errors from being displayed as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Start session
session_start();

// Define the root path and use it for includes
define('API_ROOT', str_replace('\\', '/', realpath(dirname(dirname(dirname(__FILE__))))));
require_once API_ROOT . '/config/Database.php';
require_once API_ROOT . '/models/User.php';
require_once API_ROOT . '/models/Request.php';

// Handle errors gracefully
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server configuration error: ' . $errstr,
        'error_type' => 'CONFIG_ERROR',
        'code' => 500
    ]);
    exit;
});

// Handle fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Fatal error: ' . $error['message'],
            'error_type' => 'FATAL_ERROR',
            'code' => 500
        ]);
        exit;
    }
});

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Only POST requests are accepted.',
        'code' => 405
    ]);
    exit;
}

// Database connection
$db_host = 'localhost';
$db_name = 'upang_link';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'code' => 500
    ]);
    exit;
}

// Get the authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Validate token
if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'No token provided',
        'code' => 401
    ]);
    exit;
}

$token = substr($authHeader, 7);
$user = new User($pdo);

try {
    $session = $user->validateSession($token);
    if (!$session['valid']) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired token',
            'code' => 401
        ]);
        exit;
    }
    $user_id = $session['user_id'];
    error_log("User authenticated with ID: " . $user_id);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage(),
        'code' => 401
    ]);
    exit;
}

// Get the request ID from the URL
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', $request_uri);
$tracking_number = null;

// Extract the tracking number from the URL - support both old and new formats
foreach ($uri_parts as $i => $part) {
    // Check for new format: REQ-YYYYMMDD-XXXX
    if (preg_match('/^REQ-\d{8}-\d{4}$/', $part)) {
        $tracking_number = $part;
        error_log("Found new format tracking number: $tracking_number");
        break;
    }
    // Check for old format: REQ-YYYY-XXX
    else if (preg_match('/^REQ-\d{4}-\d{3}$/', $part)) {
        $tracking_number = $part;
        error_log("Found old format tracking number: $tracking_number");
        break;
    }
}

if (!$tracking_number) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request ID format. Expected format: REQ-YYYYMMDD-XXXX or REQ-YYYY-XXX',
        'code' => 400
    ]);
    exit;
}

error_log("Attempting to cancel request: " . $tracking_number . " for user: " . $user_id);

// Update the request status and set the handled_by field
try {
    // First, check if the request exists and belongs to the user
    // Support both tracking number formats
    if (preg_match('/^REQ-\d{8}-\d{4}$/', $tracking_number)) {
        // New format: REQ-YYYYMMDD-XXXX
        $query = "SELECT request_id FROM requests 
                  WHERE tracking_number = ? 
                  AND user_id = ?";
    } else {
        // Old format: REQ-YYYY-XXX
        $query = "SELECT request_id FROM requests 
                  WHERE CONCAT('REQ-', DATE_FORMAT(submitted_at, '%Y-'), LPAD(request_id, 3, '0')) = ? 
                  AND user_id = ?";
    }
    
    error_log("Executing query: " . $query);
    error_log("With parameters: tracking_number=$tracking_number, user_id=$user_id");
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(1, $tracking_number);
    $stmt->bindParam(2, $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Request not found or does not belong to you',
            'code' => 404
        ]);
        exit;
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $request_id = $row['request_id'];
    error_log("Found request with ID: $request_id");
    
    // Check if the handled_by column exists in the requests table
    $checkColumnQuery = "SHOW COLUMNS FROM requests LIKE 'handled_by'";
    $checkColumnStmt = $pdo->prepare($checkColumnQuery);
    $checkColumnStmt->execute();
    $handledByExists = $checkColumnStmt->rowCount() > 0;
    
    // Prepare the update query based on column existence
    if ($handledByExists) {
        $query = "UPDATE requests 
                  SET status = 'CANCELLED', updated_at = NOW(), handled_by = ? 
                  WHERE request_id = ?";
        
        error_log("Executing update query with handled_by: " . $query);
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $request_id);
    } else {
        $query = "UPDATE requests 
                  SET status = 'CANCELLED', updated_at = NOW()
                  WHERE request_id = ?";
        
        error_log("Executing update query without handled_by: " . $query);
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $request_id);
    }
    
    if ($stmt->execute()) {
        // Check if the request_status_history table exists
        $checkTableQuery = "SHOW TABLES LIKE 'request_status_history'";
        $checkTableStmt = $pdo->prepare($checkTableQuery);
        $checkTableStmt->execute();
        $historyTableExists = $checkTableStmt->rowCount() > 0;
        
        // If the history table exists, insert a record
        if ($historyTableExists) {
            // Check if the changed_by column exists in the history table
            $checkColumnQuery = "SHOW COLUMNS FROM request_status_history LIKE 'changed_by'";
            $checkColumnStmt = $pdo->prepare($checkColumnQuery);
            $checkColumnStmt->execute();
            $changedByExists = $checkColumnStmt->rowCount() > 0;
            
            if ($changedByExists) {
                $query = "INSERT INTO request_status_history 
                          (request_id, status, changed_by, reason) 
                          VALUES (?, 'CANCELLED', ?, 'Cancelled by user')";
                
                error_log("Inserting into history table with changed_by: " . $query);
                
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(1, $request_id);
                $stmt->bindParam(2, $user_id);
                $stmt->execute();
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Request cancelled successfully',
            'code' => 200
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to cancel request',
            'code' => 500
        ]);
    }
} catch (Exception $e) {
    error_log("Error cancelling request: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to cancel request: ' . $e->getMessage(),
        'code' => 500
    ]);
} 