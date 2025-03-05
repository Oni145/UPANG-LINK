<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Database connection
$db_host = 'localhost';
$db_name = 'upang_link';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Get user ID from Authorization header (you should implement proper auth)
$user_id = 2; // For testing, we'll use Jericko's ID

// Fetch requests with all related information
$query = "
    SELECT 
        r.request_id,
        CONCAT('REQ-', DATE_FORMAT(r.submitted_at, '%Y-'), LPAD(r.request_id, 3, '0')) as id,
        r.user_id,
        r.type_id,
        rt.name as document_type,
        r.status,
        r.submitted_at,
        r.updated_at,
        rt.name as request_type,
        rt.processing_time,
        u.first_name,
        u.last_name,
        c.name as category_name,
        rt.requirements,
        rn.note as remarks
    FROM requests r
    JOIN request_types rt ON r.type_id = rt.type_id
    JOIN categories c ON rt.category_id = c.category_id
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN request_notes rn ON r.request_id = rn.request_id
    WHERE r.user_id = :user_id
    ORDER BY r.submitted_at DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formatted_requests = array_map(function($request) {
        // Convert status to uppercase to match Android app
        $request['status'] = strtoupper($request['status']);
        
        // Parse requirements JSON
        $requirements = json_decode($request['requirements'], true);
        
        // Create type object with properly formatted requirements
        $request['type'] = [
            'type_id' => $request['type_id'],
            'name' => $request['request_type'],
            'description' => $request['document_type'],
            'requirements' => $requirements,
            'processing_time' => $request['processing_time'],
            'category_name' => $request['category_name']
        ];

        return $request;
    }, $requests);

    echo json_encode([
        'status' => 'success',
        'data' => $formatted_requests
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch requests'
    ]);
} 