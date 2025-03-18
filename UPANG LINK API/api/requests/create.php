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
require_once API_ROOT . '/middleware/AuthMiddleware.php';
require_once API_ROOT . '/models/User.php';

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
$authHeader = $headers['Authorization'] ?? '';

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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage(),
        'code' => 401
    ]);
    exit;
}

// Handle POST request to create a new request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $type_id = $_POST['type_id'] ?? null;
    $purpose = $_POST['purpose'] ?? null;
    $student_id = $_POST['student_id'] ?? null;

    // Get the request type name to check if it's a special type
    $isSpecialType = false;
    $typeNameQuery = "SELECT name FROM request_types WHERE type_id = ?";
    $typeStmt = $pdo->prepare($typeNameQuery);
    $typeStmt->execute([$type_id]);
    $typeResult = $typeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($typeResult) {
        $typeName = $typeResult['name'];
        // Check if it's a special type that doesn't need additional fields
        if (stripos($typeName, 'Course Module') !== false || 
            stripos($typeName, 'Enrollment Certificate') !== false ||
            stripos($typeName, 'ID Replacement') !== false ||
            stripos($typeName, 'New Student ID') !== false ||
            stripos($typeName, 'Uniform') !== false ||
            stripos($typeName, 'Transcript of Records') !== false) {
            $isSpecialType = true;
        }
    }

    // Validate required fields based on request type
    if (!$type_id || !$purpose) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields',
            'code' => 400
        ]);
        exit;
    }
    
    // Only validate student_id if it's not a special type
    if (!$isSpecialType && !$student_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Student ID is required for this request type',
            'code' => 400
        ]);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Generate tracking number
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $tracking_number = "REQ-{$date}-{$random}";

        // Insert the request
        $query = "INSERT INTO requests (tracking_number, user_id, type_id, purpose) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tracking_number, $user_id, $type_id, $purpose]);
        $request_id = $pdo->lastInsertId();

        // Add request details
        $query = "INSERT INTO request_notes (request_id, user_id, note) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        
        $noteData = ['purpose' => $purpose];
        
        // Only include student_id in the note if available
        if ($student_id) {
            $noteData['student_id'] = $student_id;
        }
        
        // For special types, get student details from user profile
        if ($isSpecialType) {
            // Get the student details from the user profile
            $userQuery = "SELECT current_year FROM users WHERE user_id = ?";
            $userStmt = $pdo->prepare($userQuery);
            $userStmt->execute([$user_id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData && isset($userData['current_year'])) {
                $noteData['year_level'] = $userData['current_year'];
            } else {
                // Provide a default value if the year level is not found
                $noteData['year_level'] = 'Not specified';
                // Log this for debugging
                error_log("User details not found or current_year not set for user_id: $user_id");
            }
        }
        
        $stmt->execute([
            $request_id,
            $user_id,
            json_encode($noteData)
        ]);

        // Handle file uploads if any
        $upload_dir = dirname(API_ROOT) . '/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Debug logging
        error_log("Processing file uploads. POST data: " . json_encode($_POST));
        error_log("Files data: " . json_encode($_FILES));

        $uploaded_files = [];
        foreach ($_FILES as $field_name => $file_info) {
            error_log("Processing file field: $field_name");
            if ($file_info['error'] === UPLOAD_ERR_OK) {
                // Generate unique filename
                $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $unique_filename;
                
                error_log("Attempting to move uploaded file from {$file_info['tmp_name']} to $upload_path");
                
                // Move uploaded file
                if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                    error_log("File uploaded successfully: $unique_filename");
                    
                    // Save file info to database
                    $query = "INSERT INTO request_files (request_id, field_name, original_name, file_path) 
                              VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        $request_id,
                        $field_name,
                        $file_info['name'],
                        $unique_filename
                    ]);
                    
                    $uploaded_files[$field_name] = $unique_filename;
                } else {
                    error_log("Failed to move uploaded file: " . error_get_last()['message']);
                }
            } else {
                error_log("File upload error for $field_name: " . $file_info['error']);
            }
        }

        // Commit transaction
        $pdo->commit();

        // Return success response with tracking number
        echo json_encode([
            'status' => 'success',
            'message' => 'Request created successfully',
            'data' => [
                'request_id' => $request_id,
                'tracking_number' => $tracking_number,
                'token' => $token,
                'status' => 'PENDING',
                'submitted_at' => date('Y-m-d H:i:s'),
                'uploaded_files' => $uploaded_files
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Log more details about the error
        error_log("Error creating request: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        error_log("Request data - type_id: $type_id, user_id: $user_id, is_special_type: " . ($isSpecialType ? 'true' : 'false'));
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create request: ' . $e->getMessage(),
            'debug_info' => [
                'type_id' => $type_id,
                'special_type' => $isSpecialType,
                'request_params' => $_POST
            ],
            'code' => 500
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'code' => 405
    ]);
} 