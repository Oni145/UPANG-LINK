<?php
// Include Composer's autoloader once at the top.
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    
    private $db;
    private $user;
    
    public function __construct($db) {
        $this->db   = $db;
        $this->user = new User($db);
    }
    
    public function handleRequest($method, $uri) {
        switch ($method) {
            case 'POST':
                if (isset($uri[0])) {
                    switch ($uri[0]) {
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
                        case 'resend_verification':
                            $this->resendVerification();
                            break;
                        default:
                            $this->sendError('Invalid endpoint');
                    }
                } else {
                    $this->sendError('Invalid endpoint');
                }
                break;
            case 'GET':
                if (isset($uri[0])) {
                    if ($uri[0] === 'verify') {
                        $this->verifyEmail();
                    } elseif ($uri[0] === 'users') {
                        // Require a valid token for fetching user data.
                        $this->requireToken();
                        // If a user ID is provided, fetch that specific user; otherwise, fetch all users.
                        if (isset($uri[1]) && !empty($uri[1])) {
                            $this->getUser($uri[1]);
                        } else {
                            $this->getAllUsers();
                        }
                    } else {
                        $this->sendError('Invalid endpoint');
                    }
                } else {
                    $this->sendError('Invalid endpoint');
                }
                break;
            default:
                $this->sendError('Method not allowed');
        }
    }
    
    // ----- LOGIN FUNCTIONALITY -----
    private function login() {
        $data = json_decode(file_get_contents("php://input"));
        $missing = [];
        if (empty($data->student_number)) { $missing[] = "student_number"; }
        if (empty($data->password)) { $missing[] = "password"; }
        if (!empty($missing)) {
            $this->sendError("Missing fields: " . implode(', ', $missing), 400);
            return;
        }
        $user = $this->user->getByStudentNumber($data->student_number);
        if ($user && password_verify($data->password, $user['password'])) {
            if (!$user['is_verified']) {
                $this->sendError('Email not verified. Please verify your email before logging in.', 403);
                return;
            }
            unset($user['password']);
            // Invalidate existing tokens and generate a new one.
            $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            $token = $this->generateToken();
            $expiresAt = date('Y-m-d H:i:s', time() + 86400);
            $stmt = $this->db->prepare("INSERT INTO auth_tokens (token, user_id, login_time, expires_at) VALUES (?, ?, NOW(), ?)");
            if (!$stmt->execute([$token, $user['user_id'], $expiresAt])) {
                $this->sendError('Could not generate token', 500);
                return;
            }
            http_response_code(200);
            echo json_encode([
                'status'     => 'success',
                'message'    => 'Login successful',
                'data'       => $user,
                'token'      => $token,
                'expires_at' => $expiresAt
            ]);
        } else {
            $this->sendError('Invalid credentials', 401);
        }
    }
    
    // ----- REGISTRATION FUNCTIONALITY -----
    private function register() {
        // Optionally block registration if a staff token is provided.
        $headers = apache_request_headers();
        if (isset($headers['Authorization']) || isset($headers['authorization'])) {
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
                $tokenData = $this->validateToken($token);
                if ($tokenData && $tokenData['type'] === 'staff') {
                    $this->sendError('Staff tokens are only allowed for fetching users', 403);
                    return;
                }
            }
        }
        
        $data = json_decode(file_get_contents("php://input"));
        $missing = [];
        if (empty($data->student_number)) { $missing[] = "student_number"; }
        if (empty($data->email)) { $missing[] = "email"; }
        if (empty($data->password)) { $missing[] = "password"; }
        if (empty($data->first_name)) { $missing[] = "first_name"; }
        if (empty($data->last_name)) { $missing[] = "last_name"; }
        if (empty($data->course)) { $missing[] = "course"; }
        if (empty($data->year_level)) { $missing[] = "year_level"; }
        if (empty($data->block)) { $missing[] = "block"; }
        if (empty($data->admission_year)) { $missing[] = "admission_year"; }
        if (!empty($missing)) {
            $this->sendError("Missing fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Duplicate check.
        if ($this->user->getByStudentNumber($data->student_number)) {
            $this->sendError("Student number already exists", 409);
            return;
        }
        if ($this->user->getByEmail($data->email)) {
            $this->sendError("Email already exists", 409);
            return;
        }
        
        // Set user properties (is_verified defaults to 0 in DB).
        $this->user->student_number = $data->student_number;
        $this->user->email = $data->email;
        $this->user->password = password_hash($data->password, PASSWORD_DEFAULT);
        $this->user->first_name = $data->first_name;
        $this->user->last_name = $data->last_name;
        $this->user->course = $data->course;
        $this->user->year_level = $data->year_level;
        $this->user->block = $data->block;
        $this->user->admission_year = $data->admission_year;
        
        if ($this->user->create()) {
            // Generate a verification token.
            $verifyToken = $this->generateToken(16);
            // Update the user record with the verification token.
            $stmt = $this->db->prepare("UPDATE users SET email_verification_token = ? WHERE student_number = ?");
            $stmt->execute([$verifyToken, $data->student_number]);
            
            // Prepare verification email that simply sends the token.
            $subject = "Verify Your Email Address";
            $body = "Your verification token is: " . $verifyToken . "\n\n" .
                    "Use this token in Postman to verify your email by sending a GET request to: \n" .
                    "http://localhost:8000/auth/verify?token=" . $verifyToken;
            
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'librariansystem1@gmail.com';
                $mail->Password   = 'fyii qywz sobr wfks';
                $mail->SMTPSecure = 'TLS';
                $mail->Port       = 587;
                $mail->setFrom('no-reply@UpangLink.com', 'UPANG LINK');
                $mail->addAddress($data->email, $data->first_name . ' ' . $data->last_name);
                $mail->isHTML(false); // plain text email
                $mail->Subject  = $subject;
                $mail->Body     = $body;
                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
            
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Registered successfully. Please check your email for the verification token.'
            ]);
        } else {
            $this->sendError('Unable to create user', 500);
        }
    }
    
    // ----- RESEND VERIFICATION FUNCTIONALITY -----
    private function resendVerification() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->email)) {
            $this->sendError("Email is required", 400);
            return;
        }
        $user = $this->user->getByEmail($data->email);
        if (!$user) {
            $this->sendError("No user found with that email", 404);
            return;
        }
        if ($user['is_verified']) {
            $this->sendError("Email is already verified", 400);
            return;
        }
        // Use existing token or generate a new one.
        if (empty($user['email_verification_token'])) {
            $verifyToken = $this->generateToken(16);
            $stmt = $this->db->prepare("UPDATE users SET email_verification_token = ? WHERE user_id = ?");
            $stmt->execute([$verifyToken, $user['user_id']]);
        } else {
            $verifyToken = $user['email_verification_token'];
        }
        
        $subject = "Verify Your Email Address";
        $body = "Your verification token is: " . $verifyToken . "\n\n" .
                "Use this token in Postman to verify your email by sending a GET request to: \n" .
                "http://localhost:8000/auth/verify?token=" . $verifyToken;
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'librariansystem1@gmail.com';
            $mail->Password   = 'fyii qywz sobr wfks';
            $mail->SMTPSecure = 'TLS';
            $mail->Port       = 587;
            $mail->setFrom('no-reply@UpangLink.com', 'UPANG LINK');
            $mail->addAddress($data->email, $user['first_name'] . ' ' . $user['last_name']);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            echo json_encode([
                'status'  => 'success',
                'message' => 'Verification token resent successfully.'
            ]);
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            $this->sendError("Failed to send verification token", 500);
        }
    }
    
    // ----- EMAIL VERIFICATION -----
    private function verifyEmail() {
        if (!isset($_GET['token'])) {
            $this->sendError("Verification token missing", 400);
            return;
        }
        $token = $_GET['token'];
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $stmt = $this->db->prepare("UPDATE users SET is_verified = 1, email_verification_token = NULL WHERE user_id = ?");
            if ($stmt->execute([$user['user_id']])) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Email verified successfully.'
                ]);
            } else {
                $this->sendError("Could not update verification status", 500);
            }
        } else {
            $this->sendError("Invalid verification token", 400);
        }
    }
    
    // ----- FORGOT PASSWORD FUNCTIONALITY -----
    private function forgotPassword() {
        // Expect JSON input.
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->email)) {
            $this->sendError("Email is required", 400);
            return;
        }
        $user = $this->user->getByEmail($data->email);
        if (!$user) {
            $this->sendError("No user found with that email", 404);
            return;
        }
        // Generate reset token and expiry (1 hour valid).
        $resetToken = $this->generateToken(16);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $this->db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE user_id = ?");
        $stmt->execute([$resetToken, $expiresAt, $user['user_id']]);
        
        // Build a plain text email body that just sends the token.
        $subject = "Password Reset Request";
        $body = "Password Reset Request\n\n" .
                "Please use the token below to reset your password. This token is valid for one hour.\n\n" .
                "\"" . $resetToken . "\"\n\n" .
                "If you did not request a password reset, please ignore this email.";
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'librariansystem1@gmail.com';
            $mail->Password   = 'fyii qywz sobr wfks';
            $mail->SMTPSecure = 'TLS';
            $mail->Port       = 587;
            $mail->setFrom('no-reply@UpangLink.com', 'UPANG LINK');
            $mail->addAddress($data->email);
            $mail->isHTML(false); // send as plain text
            $mail->Subject  = $subject;
            $mail->Body     = $body;
            $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
        
        echo json_encode([
            'status'  => 'success',
            'message' => 'Password reset token has been sent to your email.'
        ]);
    }
    
    // ----- RESET PASSWORD FUNCTIONALITY (POST) -----
    private function resetPassword() {
        // Expect JSON input.
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->token) || empty($data->new_password)) {
            $this->sendError("Token and new password are required", 400);
            return;
        }
        $stmt = $this->db->prepare("SELECT user_id, password_reset_expires FROM users WHERE password_reset_token = ?");
        $stmt->execute([$data->token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $this->sendError("Invalid reset token", 400);
            return;
        }
        if (new DateTime() > new DateTime($user['password_reset_expires'])) {
            $this->sendError("Reset token has expired", 400);
            return;
        }
        $newPasswordHashed = password_hash($data->new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE user_id = ?");
        if ($stmt->execute([$newPasswordHashed, $user['user_id']])) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Password has been reset successfully.'
            ]);
        } else {
            $this->sendError("Unable to reset password", 500);
        }
    }
    
    private function logout() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
        if (!$authHeader) {
            $this->sendError('Authorization token not provided', 401);
            return;
        }
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $this->sendError('Invalid Authorization header format', 400);
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
        if ($stmt->execute([$token])) {
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Logout successful'
            ]);
        } else {
            $this->sendError('Unable to logout');
        }
    }
    
    private function getAllUsers() {
        $stmt = $this->user->read();
        $num = $stmt->rowCount();
        if ($num > 0) {
            $users_arr = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                unset($row['password']);
                array_push($users_arr, $row);
            }
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data'   => $users_arr
            ]);
        } else {
            $this->sendError('No users found');
        }
    }
    
    private function getUser($id) {
        $this->user->user_id = $id;
        $user = $this->user->readOne();
        if ($user) {
            unset($user['password']);
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data'   => $user
            ]);
        } else {
            $this->sendError('User not found');
        }
    }
    
    private function updateUser($id) {
        $data = json_decode(file_get_contents("php://input"));
        $missing = [];
        if (empty($data->email)) { $missing[] = "email"; }
        if (empty($data->first_name)) { $missing[] = "first_name"; }
        if (empty($data->last_name)) { $missing[] = "last_name"; }
        if (empty($data->course)) { $missing[] = "course"; }
        if (empty($data->year_level)) { $missing[] = "year_level"; }
        if (empty($data->block)) { $missing[] = "block"; }
        if (empty($data->admission_year)) { $missing[] = "admission_year"; }
        if (!empty($missing)) {
            $this->sendError("Missing fields for update: " . implode(', ', $missing), 400);
            return;
        }
        $this->user->user_id = $id;
        $this->user->email = $data->email;
        $this->user->first_name = $data->first_name;
        $this->user->last_name = $data->last_name;
        $this->user->course = $data->course;
        $this->user->year_level = $data->year_level;
        $this->user->block = $data->block;
        $this->user->admission_year = $data->admission_year;
    
        if ($this->user->update()) {
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User updated successfully'
            ]);
        } else {
            $this->sendError('Unable to update user');
        }
    }
    
    private function deleteUser($id) {
        $this->user->user_id = $id;
        if ($this->user->delete()) {
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User deleted successfully'
            ]);
        } else {
            $this->sendError('Unable to delete user');
        }
    }
    
    public function validateToken($token) {
        $stmt = $this->db->prepare("SELECT admin_id AS id, expires_at FROM admin_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $currentTime = new DateTime();
            $expiresAt = new DateTime($row['expires_at']);
            if ($currentTime > $expiresAt) {
                $del = $this->db->prepare("DELETE FROM admin_tokens WHERE token = ?");
                $del->execute([$token]);
                return false;
            }
            $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
            $updateStmt = $this->db->prepare("UPDATE admin_tokens SET expires_at = ? WHERE token = ?");
            $updateStmt->execute([$newExpiresAt, $token]);
            return ['id' => $row['id'], 'type' => 'admin'];
        }
        
        $stmt = $this->db->prepare("SELECT staff_id AS id, expires_at FROM staff_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $currentTime = new DateTime();
            $expiresAt = new DateTime($row['expires_at']);
            if ($currentTime > $expiresAt) {
                $del = $this->db->prepare("DELETE FROM staff_tokens WHERE token = ?");
                $del->execute([$token]);
                return false;
            }
            $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
            $updateStmt = $this->db->prepare("UPDATE staff_tokens SET expires_at = ? WHERE token = ?");
            $updateStmt->execute([$newExpiresAt, $token]);
            return ['id' => $row['id'], 'type' => 'staff'];
        }
        
        $stmt = $this->db->prepare("SELECT user_id AS id, expires_at FROM auth_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $currentTime = new DateTime();
            $expiresAt = new DateTime($row['expires_at']);
            if ($currentTime > $expiresAt) {
                $del = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
                $del->execute([$token]);
                return false;
            }
            $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
            $updateStmt = $this->db->prepare("UPDATE auth_tokens SET expires_at = ? WHERE token = ?");
            $updateStmt->execute([$newExpiresAt, $token]);
            return ['id' => $row['id'], 'type' => 'user'];
        }
        return false;
    }
    
    private function generateToken($length = 16) {
        return bin2hex(random_bytes($length));
    }
    
    private function requireToken() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
        if (!$authHeader) {
            $this->sendError('Authorization token not provided', 401);
            exit;
        }
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->sendError('Invalid Authorization header format', 400);
            exit;
        }
        $token = $matches[1];
        $tokenData = $this->validateToken($token);
        if (!$tokenData) {
            $this->sendError('Token invalid', 401);
            exit;
        }
        return $tokenData;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }
}
?>
