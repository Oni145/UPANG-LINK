<?php
/**
 * Request Requirement Notes API Endpoint
 * 
 * This endpoint provides notes from the request_requirement_notes table for a specific request.
 * GET: View notes for a specific request
 */

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include necessary files
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/RequirementNote.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Request.php';

// Generate a unique response ID for logging
$responseId = uniqid('resp_');
error_log("Starting requirement notes endpoint. Response ID: " . $responseId);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

// Extract request ID from URL path
$request_uri = $_SERVER['REQUEST_URI'];
error_log("Full request URI: " . $request_uri);

// First try to match the new format: REQ-YYYYMMDD-XXXX
if (preg_match('/requests\/(REQ-\d{8}-\d{4})\/requirement_notes/', $request_uri, $matches)) {
    $request_id = $matches[1];
    error_log("Extracted request ID from URL (new format): " . $request_id);
} 
// Then try to match the old format: REQ-YYYY-XXX
else if (preg_match('/requests\/(REQ-\d{4}-\d{3})\/requirement_notes/', $request_uri, $matches)) {
    $request_id = $matches[1];
    error_log("Extracted request ID from URL (old format): " . $request_id);
} 
// Try to match numeric request ID
else if (preg_match('/requests\/(\d+)\/requirement_notes/', $request_uri, $matches)) {
    $request_id = $matches[1];
    error_log("Extracted numeric request ID from URL: " . $request_id);
}
// Check if request ID is provided in query parameters
else if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    error_log("Got request ID from query parameter: " . $request_id);
} else {
    error_log("No request ID found in request URI or query parameters");
    $request_id = null;
}

if (!$request_id) {
    error_log("No request ID found in request");
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Request ID is required',
        'code' => 400
    ]);
    exit;
}

// Check for authentication token
$headers = getallheaders();
error_log("Request headers: " . json_encode($headers));

// Extract user ID from JWT token
$user_id = null;
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
    $token = $matches[1];
    error_log("Extracted token: " . $token);
    
    try {
        $user = new User($pdo);
        $decoded = $user->validateToken($token);
        if ($decoded) {
            $user_id = $decoded->data->user_id;
            error_log("Validated user ID from token: " . $user_id);
        } else {
            error_log("Token validation failed");
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid token',
                'code' => 401
            ]);
            exit;
        }
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid token: ' . $e->getMessage(),
            'code' => 401
        ]);
        exit;
    }
} else {
    error_log("No valid authorization token provided");
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authorization token required',
        'code' => 401
    ]);
    exit;
}

error_log("Using user ID from token: " . $user_id);

// Create requirementNote object
$requirementNote = new RequirementNote($pdo);

// Handle request based on method
$method = $_SERVER['REQUEST_METHOD'];
error_log("Processing request method: " . $method);

if ($method === 'GET') {
    // Get notes for this request
    try {
        // Convert request ID to numeric ID if it's a tracking number
        if (preg_match('/^REQ-/', $request_id)) {
            error_log("Converting tracking number to request_id: " . $request_id);
            // Get the request_id from the tracking number
            $request = new Request($pdo);
            $requestData = $request->getByTrackingNumber($request_id);
            if (!$requestData) {
                error_log("Request not found for tracking number: " . $request_id);
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Request not found',
                    'code' => 404
                ]);
                exit;
            }
            
            // IMPORTANT: Use the numeric request_id from the database, not the tracking number
            $numeric_request_id = $requestData['request_id'];
            error_log("Converted tracking number " . $request_id . " to numeric request_id: " . $numeric_request_id);
            
            // Replace the tracking number with the numeric ID for database queries
            $request_id = $numeric_request_id;
        }

        error_log("Getting requirement notes for numeric request_id: " . $request_id);
        
        // Debug the table structure
        try {
            $checkTableQuery = "DESCRIBE request_requirement_notes";
            $tableStmt = $pdo->prepare($checkTableQuery);
            $tableStmt->execute();
            $tableColumns = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Table structure for request_requirement_notes: " . json_encode($tableColumns));
            
            // Check ALL notes in the table for debugging
            $allNotesQuery = "SELECT * FROM request_requirement_notes";
            $allNotesStmt = $pdo->prepare($allNotesQuery);
            $allNotesStmt->execute();
            $allNotes = $allNotesStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("All notes in the table: " . json_encode($allNotes));
        } catch (Exception $e) {
            error_log("Error checking table structure: " . $e->getMessage());
        }
        
        // Debug - check if notes exist in the table with direct query
        try {
            $checkNotesQuery = "SELECT * FROM request_requirement_notes WHERE request_id = ?";
            $checkStmt = $pdo->prepare($checkNotesQuery);
            $checkStmt->bindParam(1, $request_id, PDO::PARAM_INT); // Ensure it's treated as integer
            $checkStmt->execute();
            $noteCount = $checkStmt->rowCount();
            error_log("Direct query found " . $noteCount . " notes for numeric request_id " . $request_id);
            
            if ($noteCount > 0) {
                // Fetch all notes directly to return to the client
                $directNotes = [];
                while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                    error_log("Found note with ID: " . $row['note_id'] . " and content: " . $row['note']);
                    $directNotes[] = [
                        'note_id' => $row['note_id'],
                        'request_id' => $row['request_id'],  // This is now the numeric ID (2), not the tracking number
                        'admin_id' => $row['admin_id'],
                        'requirement_name' => isset($row['requirement_name']) ? $row['requirement_name'] : 'General',
                        'note' => $row['note'],
                        'created_at' => $row['created_at'],
                        'admin_name' => 'Admin' // Default since we didn't join with users table
                    ];
                }
                
                // Log the direct notes data for debugging
                error_log("Returning notes data: " . json_encode($directNotes));
                
                // Return the notes directly
                echo json_encode([
                    'status' => 'success',
                    'data' => $directNotes,
                    'code' => 200
                ]);
                exit; // Exit after sending response
            } else {
                error_log("No notes found with direct query for request_id: " . $request_id);
            }
        } catch (Exception $e) {
            error_log("Error with direct notes query: " . $e->getMessage());
        }
        
        // If we got here, then the direct query didn't find any notes, try using the model
        error_log("Trying to get notes using RequirementNote model for request_id: " . $request_id);
        $notesStmt = $requirementNote->getByRequest($request_id);
        $notesCount = $notesStmt->rowCount();
        error_log("RequirementNote model found " . $notesCount . " requirement notes");
        
        if ($notesCount > 0) {
            $notes = [];
            while ($row = $notesStmt->fetch(PDO::FETCH_ASSOC)) {
                // Format the note data
                $notes[] = [
                    'note_id' => $row['note_id'],
                    'request_id' => $row['request_id'],
                    'admin_id' => $row['admin_id'],
                    'requirement_name' => $row['requirement_name'],
                    'note' => $row['note'],
                    'created_at' => $row['created_at'],
                    'admin_name' => isset($row['first_name']) && isset($row['last_name']) 
                        ? $row['first_name'] . ' ' . $row['last_name'] 
                        : 'Admin'
                ];
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $notes,
                'code' => 200
            ]);
        } else {
            error_log("No notes found for request ID: " . $request_id);
            echo json_encode([
                'status' => 'success',
                'data' => [],
                'message' => 'No notes found for this request',
                'code' => 200
            ]);
        }
    } catch (Exception $e) {
        error_log("Error getting requirement notes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch requirement notes: ' . $e->getMessage(),
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