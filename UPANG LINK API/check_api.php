<?php
// Include database and CORS headers
require_once 'config/Database.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the Course Module Request type
    $query = "SELECT * FROM request_types WHERE name = 'Course Module Request'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $requestType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$requestType) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Course Module Request type not found'
        ]);
        exit;
    }
    
    // Parse the requirements JSON
    $requirements = json_decode($requestType['requirements'], true);
    
    // Output the request type and requirements
    echo json_encode([
        'status' => 'success',
        'request_type' => [
            'type_id' => $requestType['type_id'],
            'name' => $requestType['name'],
            'description' => $requestType['description'],
            'requirements' => $requirements,
            'processing_time' => $requestType['processing_time']
        ],
        'requirements_raw' => $requestType['requirements'],
        'requirements_parsed' => $requirements
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 