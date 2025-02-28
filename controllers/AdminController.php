<?php
header('Content-Type: application/json');
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
                    case 'forgot_password':
                        $this->forgotPassword();
                        break;
                    case 'reset_password':
                        $this->resetPassword();
                        break;
                    default:
                        $this->sendError("Invalid endpoint or method", 400);
                }
            } else if ($method === 'GET') {
                if (isset($uri[2]) && $uri[2] === 'users') {
                    $adminId = isset($uri[3]) ? $uri[3] : null;
                    $this->getUsers($adminId);
                } else {
                    $this->sendError("Invalid endpoint or method", 400);
                }
            } else {
                $this->sendError("Invalid endpoint or method", 400);
            }
        } catch (Exception $e) {
            $this->sendError("Server error: " . $e->getMessage(), 500);
        }
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
        
        $missing = $this->checkMissingFields($data, ['username', 'password']);
        if (!empty($missing)) {
            $this->sendError("Missing field(s): " . implode(", ", $missing), 400);
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
        
        // Invalidate any existing tokens for this admin
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
        $stmt = $this->db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tokenData) {
            $this->sendError("Invalid or expired token", 401);
            return;
        }

        if ($adminId !== null) {
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
        
        // Retrieve admin by email using getByEmail method
        $admin = $this->adminModel->getByEmail($data->email);
        if (!$admin) {
            $this->sendError("Admin not found", 404);
            return;
        }
        
        // Generate a reset token and set expiry (1 hour from now)
        $resetToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        
        // Update the admins table with the reset token and expiry
        $stmt = $this->db->prepare("UPDATE admins SET password_reset_token = ?, password_reset_expires = ? WHERE admin_id = ?");
        if (!$stmt->execute([$resetToken, $expiresAt, $admin['admin_id']])) {
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
            $mail->addAddress($admin['email'], $admin['username']);

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
     * and clears the token fields in the admins table.
     */
    private function resetPassword() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->token) || empty($data->new_password)) {
            $this->sendError("Token and new password are required", 400);
            return;
        }
        
        $stmt = $this->db->prepare("SELECT admin_id, password_reset_expires FROM admins WHERE password_reset_token = ?");
        $stmt->execute([$data->token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) {
            $this->sendError("Invalid reset token", 400);
            return;
        }
        
        if (new DateTime() > new DateTime($admin['password_reset_expires'])) {
            $this->sendError("Reset token has expired", 400);
            return;
        }
        
        $newPasswordHashed = password_hash($data->new_password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE admins SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE admin_id = ?");
        if ($stmt->execute([$newPasswordHashed, $admin['admin_id']])) {
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
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
        exit();
    }
}
?>
