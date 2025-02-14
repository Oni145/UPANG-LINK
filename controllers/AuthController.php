<?php
class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function handleRequest($method, $uri) {
        switch($method) {
            case 'POST':
                if(isset($uri[1])) {
                    switch($uri[1]) {
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

    private function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->student_number) && !empty($data->password)) {
            $user = $this->user->getByStudentNumber($data->student_number);
            
            if($user && password_verify($data->password, $user['password'])) {
                // Remove password from response
                unset($user['password']);
                
                // Invalidate any existing tokens for this user
                $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                
                // Generate a new token for API access
                $token = $this->generateToken();
                $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours from now

                // Save the token in the auth_tokens table
                $stmt = $this->db->prepare("INSERT INTO auth_tokens (token, user_id, login_time, expires_at) VALUES (?, ?, NOW(), ?)");
                if(!$stmt->execute([$token, $user['user_id'], $expiresAt])) {
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
        } else {
            $this->sendError('Incomplete data');
        }
    }

    private function register() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(
            !empty($data->student_number) && 
            !empty($data->password) &&
            !empty($data->first_name) &&
            !empty($data->last_name) &&
            !empty($data->role)
        ) {
            $this->user->student_number = $data->student_number;
            $this->user->password = password_hash($data->password, PASSWORD_DEFAULT);
            $this->user->first_name = $data->first_name;
            $this->user->last_name = $data->last_name;
            $this->user->role = $data->role;
            $this->user->course = $data->course ?? null;
            $this->user->year_level = $data->year_level ?? null;
            $this->user->block = $data->block ?? null;
            $this->user->admission_year = $data->admission_year ?? null;

            if($this->user->create()) {
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User created successfully'
                ]);
            } else {
                $this->sendError('Unable to create user');
            }
        } else {
            $this->sendError('Incomplete data');
        }
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

    private function logout() {
        // Expect token in Authorization header as "Bearer <token>"
        $headers = apache_request_headers();
        $authHeader = null;
        if(isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif(isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            $this->sendError('Authorization token not provided', 401);
            return;
        }
        if(preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $this->sendError('Invalid Authorization header format', 400);
            return;
        }

        // Delete the token from the auth_tokens table
        $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
        if($stmt->execute([$token])) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Logout successful'
            ]);
        } else {
            $this->sendError('Unable to logout');
        }
    }

    // Validate token with sliding expiration
    // This function checks if the token is valid and, if so, extends its expiration by 24 hours.
    public function validateToken($token) {
        $stmt = $this->db->prepare("SELECT user_id, expires_at FROM auth_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$row) {
            return false; // Token not found
        }
        
        $currentTime = new DateTime();
        $expiresAt = new DateTime($row['expires_at']);
        if($currentTime > $expiresAt) {
            // Token expired: delete it from the database
            $del = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
            $del->execute([$token]);
            return false;
        }
        
        // Sliding expiration: update expires_at to 24 hours from now
        $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
        $updateStmt = $this->db->prepare("UPDATE auth_tokens SET expires_at = ? WHERE token = ?");
        $updateStmt->execute([$newExpiresAt, $token]);
        
        return $row['user_id'];
    }

    private function generateToken($length = 16) {
        return bin2hex(random_bytes($length));
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
