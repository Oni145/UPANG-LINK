<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once __DIR__ . '/../models/Admin.php';
// Include PHPMailer autoloader (adjust the path if necessary)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AdminController {
    private $db;
    private $adminModel;

    public function __construct($db) {
        $this->db = $db;
        $this->adminModel = new Admin($db);
    }

    public function handleRequest($method, $uri) {
        try {
            error_log("Request Method: $method");
            error_log("Request URI: " . implode('/', $uri));
    
            if (!isset($uri[0]) || $uri[0] !== 'admin') {
                $this->sendError("Invalid admin route", 400);
                return;
            }
    
            if ($method === 'POST' && isset($uri[1])) {
                switch ($uri[1]) {
                    case 'login':
                        $this->login();
                        break;
                    case 'register':
                        $this->register();
                        break;
                    case 'logout':
                        $this->logout();
                        break;
                    case 'forgot_password':
                        $this->forgotPassword();
                        break;
                    case 'reset_password':
                        $this->resetPassword();
                        break;
                    default:
                        $this->sendError("Invalid endpoint or method", 400);
                }
                return;
            }
    
            if ($method === 'GET' && isset($uri[1])) {
                switch ($uri[1]) {
                    case 'users':
                        // Check if an ID is provided in the URI (like /admin/users/{id})
                        if (isset($uri[2])) {
                            // Fetch a specific user by ID
                            $this->getUserById($uri[2]);
                        } else {
                            $this->getUsers();  // Fetch all admin users
                        }
                        break;
                    default:
                        $this->sendError("Invalid endpoint or method", 400);
                }
                return;
            }
    
            $this->sendError("Invalid endpoint or method", 400);
        } catch (Exception $e) {
            $this->sendError("Server error: " . $e->getMessage(), 500);
        }
    }    
    public function getUsers() {
        try {
            // Query to fetch all users with the role 'admin', excluding the password
            $query = "SELECT user_id, first_name, last_name, email, created_at, updated_at FROM users WHERE role = 'admin'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Check if users exist
            if ($users) {
                $this->sendResponse($users);  // Return the list of users
            } else {
                $this->sendError("No admin users found", 404);  // If no users are found
            }
        } catch (Exception $e) {
            $this->sendError("Server error: " . $e->getMessage(), 500);  // Handle exceptions
        }
    }

    public function getUserById($id) {
        try {
            // Query to fetch the user by ID and ensure the role is 'admin'
            $query = "SELECT user_id, first_name, last_name, email, created_at, updated_at FROM users WHERE user_id = :user_id AND role = 'admin' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Check if the user exists and if their role is 'admin'
            if ($user) {
                $this->sendResponse($user);  // Send the response
            } else {
                $this->sendError("Admin user not found", 404);  // Send error if no admin user
            }
        } catch (Exception $e) {
            $this->sendError("Server error: " . $e->getMessage(), 500);  // Handle exceptions
        }
    }

    private function sendResponse($data) {
        echo json_encode(["status" => "success", "data" => $data]);
    }

    private function sendError($message, $code) {
        echo json_encode(["status" => "error", "message" => $message, "code" => $code]);
    }
    // ✅ Extract Token from Request Headers
    private function getBearerToken() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return null;
        }
    
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    
        return null;
    }
    
  
    /**
     * Login: Validates required fields, checks credentials, generates a token,
     * and returns a success response along with token details.
     */
    
     private function login() {
        $data = json_decode(file_get_contents("php://input"));
        if (!$data) {
            $this->sendError("Invalid JSON data", 400);
            return;
        }
        
        // Check for required fields
        $missing = $this->checkMissingFields($data, ['email', 'password']);
        if (!empty($missing)) {
            $this->sendError("Missing field(s): " . implode(", ", $missing), 400);
            return;
        }
        
        // Fetch user by email
        $user = $this->adminModel->getByEmail($data->email);
        
        if (!$user) {
            $this->sendError("User not found", 404);
            return;
        }
    
        // Ensure 'user_id' exists
        if (!isset($user['user_id'])) {
            $this->sendError("Invalid user data", 500);
            return;
        }
    
        // Verify password
        if (!password_verify($data->password, $user['password'])) {
            $this->sendError("Password is incorrect", 401);
            return;
        }
    
        // Invalidate any existing session tokens for this user
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
    
        // Generate new token
        $token = bin2hex(random_bytes(32)); // 64-character hex token
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // Token expires in 24 hours
    
        // Get device info and IP address
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
        // Insert the session into user_sessions table
        $stmt = $this->db->prepare("INSERT INTO user_sessions (user_id, token, device_info, ip_address, last_activity, expires_at, is_active, created_at) 
                                    VALUES (?, ?, ?, ?, NOW(), ?, 1, NOW())");
        if (!$stmt->execute([$user['user_id'], $token, $deviceInfo, $ipAddress, $expiresAt])) {
            $this->sendError("Could not generate session token", 500);
            return;
        }
        
        // Remove password from response
        unset($user['password']);
    
        http_response_code(200);
        echo json_encode([
            'status'       => 'success',
            'message'      => 'User login successful',
            'data'         => $user,
            'token'        => $token,
            'expires_at'   => $expiresAt,
            'device_info'  => $deviceInfo,
            'ip_address'   => $ipAddress
        ]);
    }
    
    
    


    /**
     * Register: Validates required fields and registers a new admin.
     * Now requires: username, password, first_name, last_name, and email.
     */
    private function register() {
        $data = json_decode(file_get_contents("php://input"));
        if (!$data) {
            $this->sendError("Invalid JSON data", 400);
            return;
        }
        
        // Check for required fields
        $requiredFields = ['password', 'first_name', 'last_name', 'email'];
        $missing = $this->checkMissingFields($data, $requiredFields);
        if (!empty($missing)) {
            $this->sendError("Missing field(s): " . implode(", ", $missing), 400);
            return;
        }
        
        // Check if a user with this email already exists
        $existingUser = $this->adminModel->getByEmail($data->email);
        if ($existingUser) {
            $this->sendError("User already exists", 400);
            return;
        }
        
        // Assign user properties
        $this->adminModel->email = $data->email;
        $this->adminModel->first_name = $data->first_name;
        $this->adminModel->last_name = $data->last_name;
        $this->adminModel->password = password_hash($data->password, PASSWORD_DEFAULT);
    
        if ($this->adminModel->create()) {
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User registered successfully'
            ]);
        } else {
            $this->sendError("Unable to create user", 500);
        }
    }
    

    /**
     * Logout: Retrieves the token from the Authorization header and deletes it.
     */
    private function logout() {
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
        
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE token = ?");
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
    
    
        // Validate the token exists and has not expired
        $stmt = $this->db->prepare("SELECT * FROM user_sessions WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$tokenData) {
            $this->sendError("Invalid or expired token", 401);
            return;
        }
    
        if ($userId !== null) {
            if (method_exists($this->userModel, 'getById')) {
                $user = $this->userModel->getById($userId);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
    
            if (!$user) {
                $this->sendError("User not found", 404);
                return;
            }
            if (isset($user['password'])) {
                unset($user['password']);
            }
    
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User details retrieved successfully',
                'data'    => $user
            ]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($users as &$user) {
                if (isset($user['password'])) {
                    unset($user['password']);
                }
            }
    
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Users list retrieved successfully',
                'data'    => $users
            ]);
        }
    }
    
    /**
     * forgotPassword: Generates a reset token, stores it in the admins table,
     * and sends a plain text email containing only the token using PHPMailer.
     */
    private function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"));
        if (!$data) {
            $this->sendError("Invalid JSON data", 400);
            return;
        }
        
        // Validate that the email field is provided
        $missing = $this->checkMissingFields($data, ['email']);
        if (!empty($missing)) {
            $this->sendError("Missing field(s): " . implode(", ", $missing), 400);
            return;
        }
        
        // Retrieve user by email using getByEmail method
        $user = $this->adminModel->getByEmail($data->email);
        if (!$user) {
            $this->sendError("User not found", 404);
            return;
        }
        
        // Generate a reset token and set expiry (1 hour from now)
        $resetToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        
        // Update the users table with the reset token and expiry
        $stmt = $this->db->prepare("UPDATE users SET reset_password_token = ?, reset_token_expiry = ? WHERE user_id = ?");
        if (!$stmt->execute([$resetToken, $expiresAt, $user['user_id']])) {
            $this->sendError("Could not set reset token", 500);
            return;
        }
        
        // Construct the plain text email content with the token only
        $subject = "Password Reset Request";
        $body = "Password Reset Request\n\n" .
                "Please use the token below to reset your password. This token is valid for one hour.\n\n" .
                "\"" . $resetToken . "\"\n\n" .
                "If you did not request a password reset, please ignore this email.";
        
        // Send the reset email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'librariansystem1@gmail.com';
            $mail->Password   = 'tyjq vblg ekex nivi';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
    
            $mail->isHTML(false); // Send as plain text
            $mail->setFrom('your-email@example.com', 'Admin Support');
            $mail->addAddress($user['email'], $user['first_name']); // Use first_name instead of username
    
            $mail->Subject = $subject;
            $mail->Body    = $body;
    
            $mail->send();
        } catch (Exception $e) {
            $this->sendError("Mailer Error: " . $mail->ErrorInfo, 500);
            return;
        }
        
        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Password reset email sent successfully'
        ]);
    }
    
    /**
     * resetPassword: Validates the reset token, updates the password,
     * and clears the token fields in the users table.
     */
    private function resetPassword() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->token) || empty($data->new_password)) {
            $this->sendError("Token and new password are required", 400);
            return;
        }
        
        $stmt = $this->db->prepare("SELECT user_id, reset_token_expiry FROM users WHERE reset_password_token = ?");
        $stmt->execute([$data->token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->sendError("Invalid reset token", 400);
            return;
        }
        
        if (new DateTime() > new DateTime($user['reset_token_expiry'])) {
            $this->sendError("Reset token has expired", 400);
            return;
        }
        
        $newPasswordHashed = password_hash($data->new_password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_password_token = NULL, reset_token_expiry = NULL WHERE user_id = ?");
        if ($stmt->execute([$newPasswordHashed, $user['user_id']])) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Password has been reset successfully.'
            ]);
        } else {
            $this->sendError("Unable to reset password", 500);
        }
    }
    

    private function checkMissingFields($data, array $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data->{$field}) || trim($data->{$field}) === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}
?>