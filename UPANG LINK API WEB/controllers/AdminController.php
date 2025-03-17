<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
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
        // Check if database connection is valid
        if (!$db) {
            // Return a proper JSON error response
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection failed'
            ]);
            exit();
        }
        
        $this->db = $db;
        $this->adminModel = new Admin($db);
    }

    public function handleRequest($method, $request = []) {
        // Check if we're at the base endpoint (/admin)
        if (empty($request)) {
            // Check the method to determine the action
            switch ($method) {
                case 'GET':
                    $this->getAdminInfo();
                    break;
                case 'POST':
                    $this->createAdmin();
                    break;
                default:
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Method not allowed'
                    ]);
                    http_response_code(405);
                    break;
            }
            return;
        }

        // Handle other endpoints
        switch ($request[0]) {
            case 'login':
                if ($method === 'POST') {
                    $this->login();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
                    http_response_code(405);
                }
                break;
                
            case 'check':
                if ($method === 'GET') {
                    $this->checkAuthentication();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
                    http_response_code(405);
                }
                break;

            case 'logout':
                if ($method === 'POST') {
                    $this->logout();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
                    http_response_code(405);
                }
                break;

            case 'register':
                if (isset($request[1])) {
                    switch ($request[1]) {
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
                        case 'notifications':
                            if (isset($request[2]) && $request[2] === 'mark_all_as_read') {
                                $this->markAllNotificationsAsRead();
                            } else {
                                $this->sendError("Invalid endpoint for notifications", 400);
                            }
                            break;
                        default:
                            $this->sendError("Invalid endpoint or method", 400);
                    }
                } else {
                    $this->sendError("Invalid endpoint or method", 400);
                }
                break;

            case 'users':
                if ($method === 'GET') {
                    $this->getUsers();
                } else {
                    $this->sendError("Invalid endpoint or method", 400);
                }
                break;

            case 'notifications':
                if (isset($request[1])) {
                    switch ($request[1]) {
                        case 'read':
                            if ($method === 'GET') {
                                $this->getReadNotifications();
                            } else {
                                $this->sendError("Invalid endpoint or method", 400);
                            }
                            break;
                        case 'unread':
                            if ($method === 'GET') {
                                $this->getUnreadNotifications();
                            } else {
                                $this->sendError("Invalid endpoint or method", 400);
                            }
                            break;
                        case 'notifications':
                            if ($method === 'GET') {
                                $this->getNotifications();
                            } else {
                                $this->sendError("Invalid endpoint or method", 400);
                            }
                            break;
                        default:
                            $this->sendError("Invalid endpoint or method", 400);
                    }
                } else {
                    $this->sendError("Invalid endpoint or method", 400);
                }
                break;

            case 'notification':
                if (isset($request[1])) {
                    if ($method === 'GET') {
                        $this->getNotificationById($request[1]);
                    } else {
                        $this->sendError("Invalid endpoint or method", 400);
                    }
                } else {
                    $this->sendError("Notification ID is required", 400);
                }
                break;

            default:
                $this->sendError("Invalid endpoint or method", 400);
        }
    }
    
    // ✅ Mark All Notifications as Read
    public function markAllNotificationsAsRead() {
        try {
            $adminId = $this->validateToken();
            if (!$adminId) return;
    
            // Update all notifications for the admin to 'read'
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$adminId]);
    
            echo json_encode([
                "status" => "success",
                "message" => "All notifications marked as read successfully"
            ]);
        } catch (Exception $e) {
            $this->sendError("Error marking all notifications as read: " . $e->getMessage(), 500);
        }
    }
    
    // ✅ Get All Notifications
    public function getNotifications() {
        try {
            $adminId = $this->validateToken();
            if (!$adminId) return;
    
            $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ?");
            $stmt->execute([$adminId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($notifications)) {
                echo json_encode(["status" => "success", "message" => "No notifications found"]);
                return;
            }
    
            echo json_encode(["status" => "success", "notifications" => $notifications]);
        } catch (Exception $e) {
            $this->sendError("Error fetching notifications: " . $e->getMessage(), 500);
        }
    }
    
    // ✅ Get Only Read Notifications
    public function getReadNotifications() {
        try {
            $adminId = $this->validateToken();
            if (!$adminId) return;
    
            $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 1");
            $stmt->execute([$adminId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($notifications)) {
                echo json_encode(["status" => "success", "message" => "No read notifications found"]);
                return;
            }
    
            echo json_encode(["status" => "success", "notifications" => $notifications]);
        } catch (Exception $e) {
            $this->sendError("Error fetching read notifications: " . $e->getMessage(), 500);
        }
    }
    
    // ✅ Get Only Unread Notifications
    public function getUnreadNotifications() {
        try {
            $adminId = $this->validateToken();
            if (!$adminId) return;
    
            $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$adminId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($notifications)) {
                echo json_encode(["status" => "success", "message" => "No unread notifications found"]);
                return;
            }
    
            echo json_encode(["status" => "success", "notifications" => $notifications]);
        } catch (Exception $e) {
            $this->sendError("Error fetching unread notifications: " . $e->getMessage(), 500);
        }
    }
    
    // ✅ Toggle Read/Unread Status
    public function toggleNotificationReadStatus($notificationId) {
        $adminId = $this->validateToken();
        if (!$adminId) return;
    
        $stmt = $this->db->prepare("SELECT is_read FROM notifications WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $adminId]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$notification) {
            $this->sendError("Notification not found", 404);
            return;
        }
    
        $newStatus = ($notification['is_read'] == 1) ? 0 : 1;
    
        $updateStmt = $this->db->prepare("UPDATE notifications SET is_read = ? WHERE notification_id = ? AND user_id = ?");
        $updateStmt->execute([$newStatus, $notificationId, $adminId]);
    
        echo json_encode([
            "status" => "success",
            "message" => "Notification read status toggled successfully",
            "data" => ["notification_id" => $notificationId, "is_read" => $newStatus]
        ]);
    }
    
    // ✅ Validate Token and Return Admin ID
    private function validateToken() {
        $token = $this->getBearerToken();
    
        if (!$token) {
            $this->sendError("Token is required", 401);
            return false;
        }
    
        $stmt = $this->db->prepare("SELECT user_id FROM user_sessions WHERE token = ? AND expires_at > NOW() AND is_active = 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            $this->sendError("Invalid or expired token", 401);
            return false;
        }
    
        // Get admin user
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([$user['user_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            $this->sendError("User is not an admin", 403);
            return false;
        }
    
        return $admin['user_id'];
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
    public function login() {
        try {
            // Log the raw input for debugging
            $raw_input = file_get_contents('php://input');
            error_log("Raw login input: " . $raw_input);
            
            // Parse JSON input
            $data = json_decode($raw_input);
            
            // Check for JSON parsing errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->sendError("Invalid JSON data: " . json_last_error_msg(), 400);
                return;
            }
            
            // Validate required fields
            $missing = $this->checkMissingFields($data, ['username', 'password']);
            if (!empty($missing)) {
                $this->sendError("Missing field(s): " . implode(", ", $missing), 400);
                return;
            }
            
            // Get admin by username (which is actually the email in the users table)
            $admin = $this->adminModel->getByUsername($data->username);
            if (!$admin) {
                $this->sendError("Admin not found", 404);
                return;
            }
            
            // Verify password
            if (!password_verify($data->password, $admin['password'])) {
                $this->sendError("Password is incorrect", 401);
                return;
            }
            
            // Invalidate any existing tokens for this admin
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            if (!$stmt->execute([$admin['user_id']])) {
                $this->sendError("Failed to invalidate existing tokens", 500);
                return;
            }

            // Generate a new token for admin access
            $token = bin2hex(random_bytes(16)); // 32-character hex token
            $expiresAt = date('Y-m-d H:i:s', time() + 86400); // Token expires in 24 hours

            // Insert the token into the user_sessions table
            $stmt = $this->db->prepare("INSERT INTO user_sessions (user_id, token, ip_address, device_info, expires_at, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            if (!$stmt->execute([
                $admin['user_id'], 
                $token, 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 
                $expiresAt
            ])) {
                $this->sendError("Could not generate token", 500);
                return;
            }
            
            // Remove password from response data
            unset($admin['password']);

            // Set the content type to JSON
            header('Content-Type: application/json');
            
            // Return the success response
            http_response_code(200);
            echo json_encode([
                'status'     => 'success',
                'message'    => 'Admin login successful',
                'data'       => $admin,
                'token'      => $token,
                'expires_at' => $expiresAt
            ]);
        } catch (Exception $e) {
            // Log the exception
            error_log("Login error: " . $e->getMessage());
            
            // Return a proper error response
            $this->sendError("Login failed: " . $e->getMessage(), 500);
        }
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
        $requiredFields = ['username', 'password', 'first_name', 'last_name', 'email'];
        $missing = $this->checkMissingFields($data, $requiredFields);
        if (!empty($missing)) {
            $this->sendError("Missing field(s): " . implode(", ", $missing), 400);
            return;
        }
        
        // Check if an admin with this username already exists
        $existingAdmin = $this->adminModel->getByUsername($data->username);
        if ($existingAdmin) {
            $this->sendError("Admin already exists", 400);
            return;
        }
        $this->adminModel->username = $data->username;
        $this->adminModel->email = $data->email;
        $this->adminModel->first_name = $data->first_name;
        $this->adminModel->last_name = $data->last_name;
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
        
        $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = 0 WHERE token = ?");
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

    /**
     * GetUsers: Validates the token and retrieves admin details.
     */
    private function getUsers($adminId = null) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
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
        $stmt = $this->db->prepare("SELECT u.* FROM user_sessions s 
                                   JOIN users u ON s.user_id = u.user_id 
                                   WHERE s.token = ? AND s.expires_at > NOW() AND s.is_active = 1 AND u.role = 'admin'");
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            $this->sendError("Invalid or expired token", 401);
            return;
        }

        if ($adminId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            $stmt = $this->db->prepare("SELECT * FROM users WHERE role = 'admin'");
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
     * forgotPassword: Generates a reset token, stores it in the users table,
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
        
        // Retrieve admin by email using getByEmail method
        $admin = $this->adminModel->getByEmail($data->email);
        if (!$admin) {
            $this->sendError("Admin not found", 404);
            return;
        }
        
        // Generate a reset token and set expiry (1 hour from now)
        $resetToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        
        // Update the users table with the reset token and expiry
        $stmt = $this->db->prepare("UPDATE users SET reset_password_token = ?, reset_token_expiry = ? WHERE user_id = ?");
        if (!$stmt->execute([$resetToken, $expiresAt, $admin['user_id']])) {
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
            $mail->Username   = '';
            $mail->Password   = '';
            $mail->SMTPSecure = 'TLS';
            $mail->Port       = 587;

            $mail->isHTML(false); // Send as plain text
            $mail->setFrom('your-email@example.com', 'Admin Support');
            $mail->addAddress($admin['email'], $admin['email']);

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
        
        $stmt = $this->db->prepare("SELECT user_id, reset_token_expiry FROM users WHERE reset_password_token = ? AND role = 'admin'");
        $stmt->execute([$data->token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) {
            $this->sendError("Invalid reset token", 400);
            return;
        }
        
        if (new DateTime() > new DateTime($admin['reset_token_expiry'])) {
            $this->sendError("Reset token has expired", 400);
            return;
        }
        
        $newPasswordHashed = password_hash($data->new_password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_password_token = NULL, reset_token_expiry = NULL WHERE user_id = ?");
        if ($stmt->execute([$newPasswordHashed, $admin['user_id']])) {
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

    private function sendError($message, $code = 400) {
        // Make sure we set the content type to JSON
        header('Content-Type: application/json');
        
        // Set the HTTP response code
        http_response_code($code);
        
        // Log the error message
        error_log("API Error: " . $message . " (Code: " . $code . ")");
        
        // Send the error response as JSON
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
        
        // Exit the script
        exit();
    }

    // Method to check if the authentication token is valid
    private function checkAuthentication() {
        $headers = apache_request_headers();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($headers['authorization'])) {
            $token = str_replace('Bearer ', '', $headers['authorization']);
        }
        
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'No authentication token provided'
            ]);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid or expired token'
                ]);
                return;
            }
            
            // Token is valid
            echo json_encode([
                'status' => 'success',
                'message' => 'Authentication valid'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error checking authentication: ' . $e->getMessage()
            ]);
        }
    }
}
?>