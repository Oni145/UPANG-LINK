<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Staff.php';

class StaffAuthController {
    private $db;
    private $staffModel;

    public function __construct($db) {
        $this->db = $db;
        $this->staffModel = new Staff($db);
    }

    /**
     * Routes:
     * POST /staff/register
     * POST /staff/login
     * POST /staff/logout
     * GET  /staff/staffs         => fetch all staff
     * GET  /staff/staffs/{id}    => fetch one staff by id
     *
     * If no sub-route is provided in a GET request, it defaults to "staffs".
     */
    public function handleRequest($method, $uri) {
        try {
            // If no sub-route is provided for GET, default to fetching all staff.
            if ($method === 'GET' && empty($uri)) {
                $uri[0] = 'staffs';
            }
            
            if ($method === 'POST' && isset($uri[0])) {
                switch ($uri[0]) {
                    case 'register':
                        $this->register();
                        break;
                    case 'login':
                        $this->login();
                        break;
                    case 'logout':
                        $this->logout();
                        break;
                    default:
                        $this->sendError("Invalid endpoint for POST /staff/{$uri[0]}", 400);
                }
            } else if ($method === 'GET' && isset($uri[0]) && $uri[0] === 'staffs') {
                $staffId = isset($uri[1]) ? $uri[1] : null;
                $this->getStaffs($staffId);
            } else {
                $this->sendError("Invalid endpoint or method", 400);
            }
        } catch (Exception $e) {
            $this->sendError("Server error: " . $e->getMessage(), 500);
        }
    }

    // POST /staff/register
    // Expected JSON: { "username": "...", "name": "...", "email": "...", "password": "..." }
    private function register() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->username) || empty($data->name) || empty($data->email) || empty($data->password)) {
            $this->sendError("Please provide 'username', 'name', 'email', and 'password'.", 400);
            return;
        }
        // Check if staff already exists
        $existingStaff = $this->staffModel->getByUsername($data->username);
        if ($existingStaff) {
            $this->sendError("Staff already exists", 400);
            return;
        }
        $this->staffModel->username = $data->username;
        $this->staffModel->name     = $data->name;
        $this->staffModel->email    = $data->email;
        $this->staffModel->password = password_hash($data->password, PASSWORD_DEFAULT);
        if ($this->staffModel->create()) {
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Staff registered successfully'
            ]);
        } else {
            $this->sendError("Unable to create staff", 500);
        }
    }

    // POST /staff/login
    // Expected JSON: { "username": "...", "password": "..." }
    private function login() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->username) || empty($data->password)) {
            $this->sendError("Please provide 'username' and 'password'.", 400);
            return;
        }
        $staff = $this->staffModel->getByUsername($data->username);
        if (!$staff) {
            $this->sendError("Staff not found.", 404);
            return;
        }
        if (!password_verify($data->password, $staff['password'])) {
            $this->sendError("Invalid password.", 401);
            return;
        }
        // Invalidate existing tokens
        $stmt = $this->db->prepare("DELETE FROM staff_tokens WHERE staff_id = ?");
        $stmt->execute([$staff['staff_id']]);
        // Generate new token
        $token     = bin2hex(random_bytes(16)); // 32-character hex token
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // Token expires in 24 hours
        // Insert the token into the staff_tokens table
        $stmt = $this->db->prepare("INSERT INTO staff_tokens (token, staff_id, login_time, expires_at) VALUES (?, ?, NOW(), ?)");
        if (!$stmt->execute([$token, $staff['staff_id'], $expiresAt])) {
            $this->sendError("Could not generate token", 500);
            return;
        }
        // Remove password from response data
        unset($staff['password']);
        http_response_code(200);
        echo json_encode([
            'status'     => 'success',
            'message'    => 'Staff login successful',
            'data'       => $staff,
            'token'      => $token,
            'expires_at' => $expiresAt
        ]);
    }

    // POST /staff/logout
    private function logout() {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader) {
            $this->sendError("Authorization token not provided", 401);
            return;
        }
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->sendError("Invalid Authorization header format", 400);
            return;
        }
        $token = $matches[1];
        if (empty($token)) {
            $this->sendError("Token is empty", 401);
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM staff_tokens WHERE token = ?");
        $stmt->execute([$token]);
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Staff logged out successfully'
            ]);
        } else {
            $this->sendError("Invalid token or already logged out", 401);
        }
    }

    // GET /staff/staffs or /staff/staffs/{id}
    // Requires a valid token in the Authorization header.
    private function getStaffs($staffId = null) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader) {
            $this->sendError("Authorization token not provided", 401);
            return;
        }
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->sendError("Invalid Authorization header format", 400);
            return;
        }
        $token = $matches[1];
        if (empty($token)) {
            $this->sendError("Token is empty", 401);
            return;
        }
        // Validate token in staff_tokens
        $stmt = $this->db->prepare("SELECT * FROM staff_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tokenData) {
            $this->sendError("Invalid or expired token", 401);
            return;
        }
        if ($staffId !== null) {
            if (method_exists($this->staffModel, 'getById')) {
                $staff = $this->staffModel->getById($staffId);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM staffs WHERE staff_id = ?");
                $stmt->execute([$staffId]);
                $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$staff) {
                $this->sendError("Staff not found", 404);
                return;
            }
            unset($staff['password']);
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Staff details retrieved successfully',
                'data'    => $staff
            ]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM staffs");
            $stmt->execute();
            $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($staffs as &$s) {
                unset($s['password']);
            }
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Staff list retrieved successfully',
                'data'    => $staffs
            ]);
        }
    }

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
