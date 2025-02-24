<?php
class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'code' => $code
        ]);
    }

    public function handleRequest($method, $uri) {
        switch($method) {
            case 'POST':
                if(isset($uri[1])) {
                    switch($uri[1]) {
                        case 'admin':
                            if(isset($uri[2])) {
                                switch($uri[2]) {
                                    case 'login':
                                        $this->adminLogin();
                                        break;
                                    case 'register':
                                        $this->adminRegister();
                                        break;
                                    case 'logout':
                                        $this->logout();
                                        break;
                                    default:
                                        $this->sendError('Invalid admin endpoint');
                                }
                            }
                            break;
                        case 'student':
                            if(isset($uri[2])) {
                                switch($uri[2]) {
                                    case 'login':
                                        $this->studentLogin();
                                        break;
                                    case 'register':
                                        $this->simplifiedStudentRegister();
                                        break;
                                    case 'verify-email':
                                        $this->verifyEmail();
                                        break;
                                    case 'resend-verification':
                                        $this->resendVerification();
                                        break;
                                    case 'logout':
                                        $this->logout();
                                        break;
                                    case 'forgot-password':
                                        $this->forgotPassword();
                                        break;
                                    case 'reset-password':
                                        $this->resetPassword();
                                        break;
                                    default:
                                        $this->sendError('Invalid student endpoint');
                                }
                            }
                            break;
                        default:
                            $this->sendError('Invalid endpoint');
                    }
                } else {
                    $this->sendError('Invalid endpoint');
                }
                break;
            case 'GET':
                if(isset($uri[1])) {
                    $this->getUser($uri[1]);
                } else {
                    $this->getAllUsers();
                }
                break;
            case 'PUT':
                if(isset($uri[1])) {
                    $this->updateUser($uri[1]);
                } else {
                    $this->sendError('User ID required');
                }
                break;
            case 'DELETE':
                if(isset($uri[1])) {
                    $this->deleteUser($uri[1]);
                } else {
                    $this->sendError('User ID required');
                }
                break;
            default:
                $this->sendError('Method not allowed');
        }
    }

    private function adminLogin() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->email) && !empty($data->password)) {
            $user = $this->user->getByEmail($data->email);
            
            if($user && password_verify($data->password, $user['password'])) {
                if($user['role'] !== 'admin') {
                    $this->sendError('Access denied. Admin access only.', 403);
                    return;
                }

                // Create session
                $device_info = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $session = $this->user->createSession($user['user_id'], $device_info, $ip_address);

                if($session) {
                    // Remove password from response
                    unset($user['password']);
                    unset($user['email_verification_token']);
                    unset($user['email_token_expiry']);
                    
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'data' => [
                            'user' => $user,
                            'token' => $session['token'],
                            'expires_at' => $session['expires_at']
                        ]
                    ]);
                } else {
                    $this->sendError('Failed to create session');
                }
            } else {
                $this->sendError('Invalid credentials');
            }
        } else {
            $this->sendError('Incomplete data');
        }
    }

    private function studentLogin() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->email) && !empty($data->password)) {
            $user = $this->user->getByEmail($data->email);
            
            // Check if user exists
            if(!$user) {
                // Return generic error message
                $this->sendError('Wrong email or password.');
                return;
            }

            // Check password
            if(!password_verify($data->password, $user['password'])) {
                // Return generic error message
                $this->sendError('Wrong email or password.');
                return;
            }

            // Check user role
            if($user['role'] !== 'student') {
                $this->sendError('This account is not a student account. Please use the correct login page.');
                return;
            }

            // Check email verification
            if(!$user['email_verified']) {
                $this->sendError('Please verify your email address before logging in. Check your inbox for the verification link.');
                return;
            }

            // Create session
            $device_info = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $session = $this->user->createSession($user['user_id'], $device_info, $ip_address);

            if($session) {
                // Remove sensitive data from response
                unset($user['password']);
                unset($user['email_verification_token']);
                unset($user['email_token_expiry']);
                
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'data' => [
                        'user' => $user,
                        'token' => $session['token'],
                        'expires_at' => $session['expires_at']
                    ]
                ]);
            } else {
                $this->sendError('Unable to log in. Please try again.');
            }
        } else {
            $this->sendError('Please enter your email and password.');
        }
    }

    private function adminRegister() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(
            !empty($data->email) &&
            !empty($data->password) &&
            !empty($data->first_name) &&
            !empty($data->last_name)
        ) {
            // Check if email already exists
            if($this->user->getByEmail($data->email)) {
                $this->sendError('Email already exists');
                return;
            }

            $this->user->email = $data->email;
            $this->user->password = password_hash($data->password, PASSWORD_DEFAULT);
            $this->user->first_name = $data->first_name;
            $this->user->last_name = $data->last_name;
            $this->user->role = 'admin';
            
            if($this->user->create()) {
                // Admin accounts are automatically verified
                $this->user->verifyEmail($this->user->email_verification_token);
                
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Admin account created successfully'
                ]);
            } else {
                $this->sendError('Unable to create admin account');
            }
        } else {
            $this->sendError('Incomplete data');
        }
    }

    private function simplifiedStudentRegister() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(
            !empty($data->email) &&
            !empty($data->password) &&
            !empty($data->first_name) &&
            !empty($data->last_name)
        ) {
            // Check if email already exists
            if($this->user->getByEmail($data->email)) {
                $this->sendError('Email already exists');
                return;
            }

            $this->user->email = $data->email;
            $this->user->password = password_hash($data->password, PASSWORD_DEFAULT);
            $this->user->first_name = $data->first_name;
            $this->user->last_name = $data->last_name;
            $this->user->role = 'student';

            if($this->user->create()) {
                // Send verification email
                $emailHandler = new EmailHandler();
                $emailHandler->sendVerificationEmail($this->user->email, $this->user->email_verification_token);
                
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Account created successfully. Please check your email to verify your account.'
                ]);
            } else {
                $this->sendError('Unable to create account');
            }
        } else {
            $this->sendError('Please provide email, password, first name, and last name');
        }
    }

    private function verifyEmail() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->token)) {
            $result = $this->user->verifyEmail($data->token);
            
            if($result['status'] === 'success') {
                http_response_code(200);
                echo json_encode($result);
            } else {
                $this->sendError($result['message']);
            }
        } else {
            $this->sendError('Verification token is required');
        }
    }

    private function resendVerification() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->email)) {
            $user = $this->user->getByEmail($data->email);
            
            if($user && !$user['email_verified']) {
                $this->user->user_id = $user['user_id'];
                if($this->user->regenerateVerificationToken()) {
                    // Send new verification email
                    $this->sendVerificationEmail($user['email'], $this->user->email_verification_token);
                    
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Verification email sent successfully'
                    ]);
                } else {
                    $this->sendError('Failed to generate new verification token');
                }
            } else {
                $this->sendError('Invalid email or account already verified');
            }
        } else {
            $this->sendError('Email is required');
        }
    }

    private function logout() {
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        
        if($token) {
            if($this->user->logout($token)) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Logged out successfully'
                ]);
            } else {
                $this->sendError('Failed to logout');
            }
        } else {
            $this->sendError('Authorization token is required');
        }
    }

    private function sendVerificationEmail($email, $token) {
        // TODO: Implement email sending functionality
        // For now, we'll just log the verification link
        $verification_link = "http://your-frontend-url/verify-email?token=" . $token;
        error_log("Verification link for {$email}: {$verification_link}");
    }

    private function getAllUsers() {
        $stmt = $this->user->read();
        $num = $stmt->rowCount();

        if($num > 0) {
            $users_arr = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                unset($row['password']); // Remove password from response
                array_push($users_arr, $row);
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $users_arr
            ]);
        } else {
            $this->sendError('No users found');
        }
    }

    private function getUser($id) {
        $this->user->user_id = $id;
        $user = $this->user->readOne();

        if($user) {
            unset($user['password']); // Remove password from response
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $user
            ]);
        } else {
            $this->sendError('User not found');
        }
    }

    private function updateUser($id) {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data)) {
            $this->user->user_id = $id;
            $this->user->first_name = $data->first_name ?? null;
            $this->user->last_name = $data->last_name ?? null;
            $this->user->course = $data->course ?? null;
            $this->user->year_level = $data->year_level ?? null;
            $this->user->block = $data->block ?? null;

            if($this->user->update()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User updated successfully'
                ]);
            } else {
                $this->sendError('Unable to update user');
            }
        } else {
            $this->sendError('No data provided');
        }
    }

    private function deleteUser($id) {
        $this->user->user_id = $id;
        
        if($this->user->delete()) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ]);
        } else {
            $this->sendError('Unable to delete user');
        }
    }

    private function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->email)) {
            $user = $this->user->getByEmail($data->email);
            
            if($user) {
                $this->user->user_id = $user['user_id'];
                if($this->user->generateResetToken()) {
                    // Send reset password email
                    $emailHandler = new EmailHandler();
                    $emailHandler->sendResetPasswordEmail($user['email'], $this->user->reset_password_token);
                    
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Password reset instructions have been sent to your email'
                    ]);
                } else {
                    $this->sendError('Failed to generate reset token');
                }
            } else {
                // For security reasons, don't reveal if email exists
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'If an account exists with this email, password reset instructions have been sent'
                ]);
            }
        } else {
            $this->sendError('Email is required');
        }
    }

    private function resetPassword() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->token) && !empty($data->password)) {
            // Validate password strength
            if(strlen($data->password) < 8) {
                $this->sendError('Password must be at least 8 characters long');
                return;
            }

            $result = $this->user->validateResetToken($data->token);
            
            if($result['status'] === 'success') {
                $this->user->user_id = $result['user_id'];
                if($this->user->resetPassword($data->password)) {
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Password has been reset successfully'
                    ]);
                } else {
                    $this->sendError('Failed to reset password');
                }
            } else {
                $this->sendError($result['message']);
            }
        } else {
            $this->sendError('Token and new password are required');
        }
    }
} 