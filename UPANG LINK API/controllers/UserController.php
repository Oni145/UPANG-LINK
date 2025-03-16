<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $db;
    private $user;

    public function __construct($db = null) {
        try {
            error_log("Initializing UserController");
            if ($db) {
                $this->db = $db;
                error_log("Using provided database connection");
            } else {
                $database = new Database();
                $this->db = $database->getConnection();
                error_log("Created new database connection");
            }
            $this->user = new User($this->db);
            error_log("UserController initialized successfully");
        } catch (Exception $e) {
            error_log("Error initializing UserController: " . $e->getMessage());
            throw $e;
        }
    }

    public function handleRequest($method, $endpoint = '') {
        error_log("Handling user request: Method=$method, Endpoint=" . (is_array($endpoint) ? implode('/', $endpoint) : $endpoint));
        
        try {
            // Convert endpoint to string if it's an array
            $endpointStr = is_array($endpoint) ? end($endpoint) : $endpoint;
            error_log("Normalized endpoint: " . $endpointStr);
            
            switch ($method) {
                case 'GET':
                    if (empty($endpointStr) || $endpointStr === 'users') {
                        // Get all users
                        $result = $this->getAllUsers();
                    } else {
                        // Get user by ID
                        $result = $this->getUser($endpointStr);
                    }
                    break;
                case 'POST':
                    // Create user
                    $result = $this->createUser();
                    break;
                case 'PUT':
                    // Update user
                    $result = $this->updateUser($endpointStr);
                    break;
                case 'DELETE':
                    // Delete user
                    $result = $this->deleteUser($endpointStr);
                    break;
                default:
                    throw new Exception("Method not allowed", 405);
            }
            
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Error in UserController::handleRequest: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code($e->getCode() ?: 500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode() ?: 500
            ]);
        }
    }

    private function getAllUsers() {
        try {
            error_log("Getting all users");
            $users = $this->user->getAll();
            return [
                'status' => 'success',
                'message' => 'Users retrieved successfully',
                'data' => $users
            ];
        } catch (Exception $e) {
            error_log("Error getting all users: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Exception("Failed to retrieve users", 500);
        }
    }

    private function getUser($id) {
        try {
            error_log("Getting user with ID: $id");
            $user = $this->user->getById($id);
            if (!$user) {
                throw new Exception("User not found", 404);
            }
            return [
                'status' => 'success',
                'message' => 'User retrieved successfully',
                'data' => $user
            ];
        } catch (Exception $e) {
            error_log("Error getting user: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }

    private function createUser() {
        try {
            error_log("Creating new user");
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) {
                throw new Exception("Invalid request data", 400);
            }
            
            $required_fields = ['name', 'email', 'password', 'role'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: $field", 400);
                }
            }
            
            $user_id = $this->user->create($data);
            return [
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => ['user_id' => $user_id]
            ];
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }

    private function updateUser($id) {
        try {
            error_log("Updating user with ID: $id");
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) {
                throw new Exception("Invalid request data", 400);
            }
            
            $success = $this->user->update($id, $data);
            if (!$success) {
                throw new Exception("User not found or no changes made", 404);
            }
            
            return [
                'status' => 'success',
                'message' => 'User updated successfully'
            ];
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }

    private function deleteUser($id) {
        try {
            error_log("Deleting user with ID: $id");
            $success = $this->user->delete($id);
            if (!$success) {
                throw new Exception("User not found", 404);
            }
            
            return [
                'status' => 'success',
                'message' => 'User deleted successfully'
            ];
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
} 