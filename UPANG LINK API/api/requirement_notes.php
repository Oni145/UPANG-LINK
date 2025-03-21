<?php
// Enable display of errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Direct Request Requirement Notes API Endpoint
 * 
 * This endpoint directly provides notes from the request_requirement_notes table for a specific request ID.
 * GET: View notes for a specific request ID
 */

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files - use absolute paths
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/RequirementNote.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Request.php';

// Check if we need jwt utils
if (file_exists(__DIR__ . '/../config/jwt_utils.php')) {
    require_once __DIR__ . '/../config/jwt_utils.php';
} else {
    error_log("JWT utils file not found");
    // Simple validation function if jwt_utils.php doesn't exist
    function is_jwt_valid($jwt) {
        // For testing, just return true
        return true;
    }
}

// Generate a unique ID for this response (for logging purposes)
$response_id = uniqid();

// Initialize response array
$response = array(
    "status" => "error",
    "message" => "Unknown error occurred",
    "data" => null,
    "response_id" => $response_id
);

// Check if request_id parameter exists
if (!isset($_GET['request_id'])) {
    $response["message"] = "Missing request_id parameter";
    echo json_encode($response);
    exit;
}

$request_id = $_GET['request_id'];
error_log("[$response_id] Direct notes endpoint called for request_id: $request_id");

// Connect to database
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize RequirementNote object
    $note = new RequirementNote($db);
    
    // Check if request_id is numeric
    if (is_numeric($request_id)) {
        // Request ID is already numeric
        $requestIdInt = intval($request_id);
        error_log("[$response_id] Using numeric request_id: $requestIdInt");
    } else {
        // If it's a tracking number, convert to numeric ID using direct SQL query
        error_log("[$response_id] Tracking number provided: $request_id, attempting to convert");
        try {
            // Direct query to find request_id by tracking_number
            $query = "SELECT request_id FROM requests WHERE tracking_number = :tracking_number";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':tracking_number', $request_id, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $requestData = $stmt->fetch(PDO::FETCH_ASSOC);
                $requestIdInt = intval($requestData['request_id']);
                error_log("[$response_id] Converted tracking number to ID: $requestIdInt");
            } else {
                $response["message"] = "Invalid tracking number";
                echo json_encode($response);
                exit;
            }
        } catch (PDOException $e) {
            error_log("[$response_id] Database error: " . $e->getMessage());
            $response["message"] = "Database error: " . $e->getMessage();
            echo json_encode($response);
            exit;
        }
    }
    
    // Direct debug of the table contents
    try {
        // Check table structure
        $tableCheckStmt = $db->prepare("DESCRIBE request_requirement_notes");
        $tableCheckStmt->execute();
        $tableStructure = $tableCheckStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("[$response_id] Table structure: " . json_encode($tableStructure));
        
        // Get all notes for debugging
        $allNotesStmt = $db->prepare("SELECT * FROM request_requirement_notes");
        $allNotesStmt->execute();
        $allNotes = $allNotesStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("[$response_id] All notes in database: " . json_encode($allNotes));
        
        // Get specific notes for this request
        $notesStmt = $db->prepare("SELECT * FROM request_requirement_notes WHERE request_id = :request_id");
        $notesStmt->bindParam(":request_id", $requestIdInt, PDO::PARAM_INT);
        $notesStmt->execute();
        $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($notes);
        error_log("[$response_id] Found $count notes for request ID $requestIdInt: " . json_encode($notes));
        
        if ($count > 0) {
            // Format the notes for the response
            $formattedNotes = array();
            foreach ($notes as $note) {
                $formattedNote = array(
                    "note_id" => isset($note['id']) ? $note['id'] : (isset($note['note_id']) ? $note['note_id'] : "0"),
                    "request_id" => $requestIdInt,
                    "admin_id" => $note['admin_id'],
                    "admin_name" => isset($note['admin_name']) ? $note['admin_name'] : "",
                    "note" => $note['note'],
                    "created_at" => $note['created_at']
                );
                $formattedNotes[] = $formattedNote;
            }
            
            $response["status"] = "success";
            $response["message"] = "Notes retrieved successfully";
            $response["data"] = $formattedNotes;
        } else {
            $response["status"] = "success";
            $response["message"] = "No notes found for this request";
            $response["data"] = [];
        }
    } catch (PDOException $e) {
        error_log("[$response_id] Database error: " . $e->getMessage());
        $response["message"] = "Database error: " . $e->getMessage();
    }
} catch (Exception $e) {
    $response["message"] = "Database connection error: " . $e->getMessage();
}

// Return response
echo json_encode($response);
?> 