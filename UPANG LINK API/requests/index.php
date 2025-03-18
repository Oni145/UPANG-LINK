<?php
// Generate a unique response ID for debugging
$responseId = uniqid();

// Allow from any origin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// Add cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
// Add the response ID to headers
header("X-Response-ID: " . $responseId);

require_once '../controllers/RequestController.php';
require_once '../middleware/AuthMiddleware.php';
require_once '../config/Database.php';

// Start session
session_start();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get the authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

// Validate token
if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No token provided']);
    exit();
}

$token = substr($authHeader, 7);
$authMiddleware = new AuthMiddleware($db);

try {
    $session = $authMiddleware->validateSession($token);
    if (!$session['valid']) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit();
    }
    $_SESSION['user_id'] = $session['user_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit();
}

$controller = new RequestController($db);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the endpoint from the URL
$requestUri = urldecode($_SERVER['REQUEST_URI']);

// Define possible base URLs
$possibleBaseUrls = [
    '/UPANG-LINK/UPANG LINK API/requests',
    '/UPANG-LINK/UPANG LINK API/api/requests',
    '/api/requests'
];

$endpoint = '';
$matchedBaseUrl = '';

// Find which base URL matches
foreach ($possibleBaseUrls as $baseUrl) {
    if (strpos($requestUri, $baseUrl) !== false) {
        $matchedBaseUrl = $baseUrl;
        break;
    }
}

error_log("Request URI: " . $requestUri);
error_log("Matched Base URL: " . $matchedBaseUrl);

// Extract the endpoint
if ($matchedBaseUrl && strpos($requestUri, $matchedBaseUrl) !== false) {
    $path = substr($requestUri, strpos($requestUri, $matchedBaseUrl) + strlen($matchedBaseUrl));
    $path = trim($path, '/');
    if (!empty($path)) {
        $endpoint = $path;
    }
}

error_log("Extracted endpoint: " . $endpoint);

// Handle the request
$result = $controller->handleRequest($method, $endpoint);

// Add debug information
if (is_array($result)) {
    $result['debug'] = [
        'response_id' => $responseId,
        'timestamp' => time(),
        'method' => $method,
        'endpoint' => $endpoint
    ];
}

// Log the response
error_log("Response ID: " . $responseId . ", Method: " . $method . ", Endpoint: " . $endpoint);

// Send the response
echo json_encode($result); 