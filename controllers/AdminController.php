<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Admin.php';

class AdminAuthController {
    private $db;
    private $adminModel;

    public function __construct($db) {
        $this->db = $db;
        $this->adminModel = new Admin($db);
    }

    public function handleRequest($method, $uri) {
        try {
            if ($method === 'POST' && isset($uri[2])) {
                switch ($uri[2]) {
                    case 'login':
                        $this->login();
                        break;
                    case 'register':
                        $this->register();
                        break;
                    case 'logout':
                        $this->logout();
                        break;
                    default:
                        $this->sendError("Invalid endpoint or method", 400);
                }
            } else if ($method === 'GET' && isset($uri[2]) && $uri[2] === 'users') {
                $adminId = isset($uri[3]) ? $uri[3] : null;
                $this->getUsers($adminId);
            } else {
                $this->sendError("Invalid endpoint or method", 400);
            }
        } catch (Exception $e) {
            $this->sendError("Server error: " . $e->getMessage(), 500);
        }
    }

    private function login() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->username) || empty($data->password)) {
            $this->sendError("Incomplete data", 400);
            return;
        }
        $admin = $this->adminModel->getByUsername($data->username);
        if (!$admin) {
            $this->sendError("Admin not found", 404);
            return;
        }
        if (!password_verify($data->password, $admin['password'])) {
            $this->sendError("Password is incorrect", 401);
            return;
        }
        
        // Invalidate any existing tokens for this admin from the admin_tokens table
        $stmt = $this->db->prepare("DELETE FROM admin_tokens WHERE admin_id = ?");
        $stmt->execute([$admin['admin_id']]);

        // Generate a new token for admin access
        $token = bin2hex(random_bytes(16)); // 32-character hex token
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // Token expires in 24 hours

        // Insert the token into the admin_tokens table
        $stmt = $this->db->prepare("INSERT INTO admin_tokens (token, admin_id, login_time, expires_at) VALUES (?, ?, NOW(), ?)");
        if (!$stmt->execute([$token, $admin['admin_id'], $expiresAt])) {
            $this->sendError("Could not generate token", 500);
            return;
        }
        
        // Remove password from response data
        unset($admin['password']);

        http_response_code(200);
        echo json_encode([
            'status'     => 'success',
            'message'    => 'Admin login successful',
            'data'       => $admin,
            'token'      => $token,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Admin Registration: Registers a new admin using only a username and password.
     */
    private function register() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->username) || empty($data->password)) {
            $this->sendError("Incomplete data", 400);
            return;
        }
        // Check if an admin with this username already exists
        $existingAdmin = $this->adminModel->getByUsername($data->username);
        if ($existingAdmin) {
            $this->sendError("Admin already exists", 400);
            return;
        }
        $this->adminModel->username = $data->username;
        $this->adminModel->password = password_hash($data->password, PASSWORD_DEFAULT);

        if ($this->adminModel->create()) {
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Admin registered successfully'
            ]);
        } else {
            $this->sendError("Unable to create admin", 500);
        }
    }

    /**
     * Admin Logout: Retrieves the token from the Authorization header and deletes it from the admin_tokens table.
     * If no valid token is provided or deletion fails, returns an error.
     */
    private function logout() {
        // Retrieve the token from the Authorization header
        $headers = apache_request_headers();
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            $this->sendError("Authorization token not provided", 401);
            return;
        }
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $this->sendError("Invalid Authorization header format", 400);
            return;
        }
        
        if (empty($token)) {
            $this->sendError("Token is empty", 401);
            return;
        }
        
        // Delete the token from the admin_tokens table
        $stmt = $this->db->prepare("DELETE FROM admin_tokens WHERE token = ?");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Admin logged out successfully'
            ]);
        } else {
            $this->sendError("Invalid token or already logged out", 401);
        }
    }


    private function getUsers($adminId = null) {
        // Retrieve the token from the Authorization header
        $headers = apache_request_headers();
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            $this->sendError("Authorization token not provided", 401);
            return;
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $this->sendError("Invalid Authorization header format", 400);
            return;
        }

        if (empty($token)) {
            $this->sendError("Token is empty", 401);
            return;
        }

        // Validate the token exists and has not expired
        $stmt = $this->db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tokenData) {
            $this->sendError("Invalid or expired token", 401);
            return;
        }

        // If a specific admin ID is provided, fetch that admin's details
        if ($adminId !== null) {
            // Use the Admin model if it has getById; otherwise, query directly from the admins table.
            if (method_exists($this->adminModel, 'getById')) {
                $admin = $this->adminModel->getById($adminId);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM admins WHERE admin_id = ?");
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if (!$admin) {
                $this->sendError("Admin not found", 404);
                return;
            }
            if (isset($admin['password'])) {
                unset($admin['password']);
            }
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Admin details retrieved successfully',
                'data'    => $admin
            ]);
        } else {
            // No specific ID provided; list all admins.
            $stmt = $this->db->prepare("SELECT * FROM admins");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($admins as &$admin) {
                if (isset($admin['password'])) {
                    unset($admin['password']);
                }
            }
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Admins list retrieved successfully',
                'data'    => $admins
            ]);
        }
    }

    /**
     * Sends a JSON error response and exits.
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
        exit();
    }
}
?>
