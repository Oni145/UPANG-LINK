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
    $type_id = $_POST['type_id'] ?? null;
    $purpose = $_POST['purpose'] ?? null;
    $student_id = $_POST['student_id'] ?? null;

    // Fetch the request type name
    $requestTypeQuery = "SELECT name FROM request_types WHERE type_id = ?";
    $requestTypeStmt = $pdo->prepare($requestTypeQuery);
    $requestTypeStmt->execute([$type_id]);
    $typeResult = $requestTypeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$typeResult) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request type',
            'code' => 400
        ]);
        exit;
    }

    $requestTypeName = $typeResult['name'];

    // Validate required fields
    if (!$type_id || !$purpose) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields',
            'code' => 400
        ]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $tracking_number = "REQ-{$date}-{$random}";

        $query = "INSERT INTO requests (tracking_number, user_id, type_id, purpose) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tracking_number, $user_id, $type_id, $purpose]);
        $request_id = $pdo->lastInsertId();

        // Insert notification for the user who made the request
        $notificationQuery = "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, ?, NOW())";
        $notificationStmt = $pdo->prepare($notificationQuery);
        $notificationTitle = "New Request Submitted";
        $notificationMessage = $requestTypeName; // Set message as request type name
        $notificationStmt->execute([$user_id, $notificationTitle, $notificationMessage, 0]);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Request created successfully',
            'data' => [
                'request_id' => $request_id,
                'tracking_number' => $tracking_number,
                'token' => $token,
                'status' => 'PENDING',
                'submitted_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();

        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create request: ' . $e->getMessage(),
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
