<?php

// Enable error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define environment
define('ENVIRONMENT', 'development');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load required files
require_once 'config/Database.php';
require_once 'middleware/ErrorHandler.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/RequestController.php';
require_once 'models/User.php';
require_once 'utils/FileHandler.php';

// Set up error handling
set_exception_handler([ErrorHandler::class, 'handleError']);

try {
    // Connect to database
    $database = new Database();
    $db = $database->connect();

    // Parse request URI
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/'));

    // Remove 'api' from the beginning if present
    if ($uri[0] === 'api') {
        array_shift($uri);
    }

    if (empty($uri[0])) {
        throw new NotFoundException('Endpoint not found');
    }

    // Route request to appropriate controller
    switch ($uri[0]) {
        case 'auth':
            $controller = new AuthController($db);
            array_shift($uri); // Remove 'auth' from uri array
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $uri);
            break;

        case 'requests':
            $controller = new RequestController($db);
            array_shift($uri); // Remove 'requests' from uri array
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $uri);
            break;

        default:
            throw new NotFoundException('Endpoint not found');
    }
} catch (PDOException $e) {
    // Handle database errors
    throw new Exception('Database error: ' . $e->getMessage());
} catch (Throwable $e) {
    // Handle all other errors
    ErrorHandler::handleError($e);
} 