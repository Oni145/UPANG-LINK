<?php
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
                // Login, register, and logout do not require a token (logout validates token internally)
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
                        default:
                            $this->sendError('Invalid endpoint');
                    }
                } else {
                    $this->sendError('Invalid endpoint');
                }
                break;
            case 'GET':
                // Require token for GET endpoints
                $this->requireToken();
                if (isset($uri[0]) && $uri[0] === 'users') {
                    if (isset($uri[1]) && !empty(trim($uri[1]))) {
                        $this->getUser($uri[1]);
                    } else {
                        $this->getAllUsers();
                    }
                } else {
                    $this->sendError('Invalid endpoint');
                }
                break;
            case 'PUT':
                // Require token for PUT endpoints
                $this->requireToken();
                if (isset($uri[0]) && $uri[0] === 'users') {
                    if (isset($uri[1]) && !empty(trim($uri[1]))) {
                        $this->updateUser($uri[1]);
                    } else {
                        $this->sendError('User ID required');
                    }
                } else {
                    $this->sendError('Invalid endpoint');
                }
                break;
            case 'DELETE':
                // Require token for DELETE endpoints
                $this->requireToken();
                if (isset($uri[0]) && $uri[0] === 'users') {
                    if (isset($uri[1]) && !empty(trim($uri[1]))) {
                        $this->deleteUser($uri[1]);
                    } else {
                        $this->sendError('User ID required');
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
        if (empty($data->student_number)) {
            $missing[] = "student_number";
        }
        if (empty($data->password)) {
            $missing[] = "password";
        }
        if (!empty($missing)) {
            $this->sendError("Missing fields: " . implode(', ', $missing), 400);
            return;
        }
        
        $user = $this->user->getByStudentNumber($data->student_number);
        if ($user && password_verify($data->password, $user['password'])) {
            unset($user['password']);
            // Invalidate any existing tokens for this user
            $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            // Generate a new token for API access
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
        $data = json_decode(file_get_contents("php://input"));
        $missing = [];
        if (empty($data->student_number)) {
            $missing[] = "student_number";
        }
        if (empty($data->password)) {
            $missing[] = "password";
        }
        if (empty($data->first_name)) {
            $missing[] = "first_name";
        }
        if (empty($data->last_name)) {
            $missing[] = "last_name";
        }
        if (empty($data->role)) {
            $missing[] = "role";
        }
        if (empty($data->course)) {
            $missing[] = "course";
        }
        if (empty($data->year_level)) {
            $missing[] = "year_level";
        }
        if (empty($data->block)) {
            $missing[] = "block";
        }
        if (empty($data->admission_year)) {
            $missing[] = "admission_year";
        }
        if (!empty($missing)) {
            $this->sendError("Missing fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Set all required fields
        $this->user->student_number   = $data->student_number;
        $this->user->password         = password_hash($data->password, PASSWORD_DEFAULT);
        $this->user->first_name       = $data->first_name;
        $this->user->last_name        = $data->last_name;
        $this->user->role             = $data->role;
        $this->user->course           = $data->course;
        $this->user->year_level       = $data->year_level;
        $this->user->block            = $data->block;
        $this->user->admission_year   = $data->admission_year;
        
        if ($this->user->create()) {
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User created successfully'
            ]);
        } else {
            $this->sendError('Unable to create user', 500);
        }
    }

    // ----- FETCH ALL USERS -----
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

    // ----- FETCH A SINGLE USER -----
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

    // ----- UPDATE USER FUNCTIONALITY -----
    private function updateUser($id) {
        $data = json_decode(file_get_contents("php://input"));
        $missing = [];
        if (empty($data->first_name)) {
            $missing[] = "first_name";
        }
        if (empty($data->last_name)) {
            $missing[] = "last_name";
        }
        if (empty($data->role)) {
            $missing[] = "role";
        }
        if (empty($data->course)) {
            $missing[] = "course";
        }
        if (empty($data->year_level)) {
            $missing[] = "year_level";
        }
        if (empty($data->block)) {
            $missing[] = "block";
        }
        if (empty($data->admission_year)) {
            $missing[] = "admission_year";
        }
        if (!empty($missing)) {
            $this->sendError("Missing fields for update: " . implode(', ', $missing), 400);
            return;
        }
        $this->user->user_id         = $id;
        $this->user->first_name      = $data->first_name;
        $this->user->last_name       = $data->last_name;
        $this->user->role            = $data->role;
        $this->user->course          = $data->course;
        $this->user->year_level      = $data->year_level;
        $this->user->block           = $data->block;
        $this->user->admission_year  = $data->admission_year;
    
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

    // ----- DELETE USER FUNCTIONALITY -----
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

    // ----- LOGOUT FUNCTIONALITY -----
    private function logout() {
        $headers = apache_request_headers();
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
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

    // ----- TOKEN VALIDATION & SLIDING EXPIRATION -----
    public function validateToken($token) {
        // Check auth_tokens first (for normal users)
        $stmt = $this->db->prepare("SELECT user_id, expires_at FROM auth_tokens WHERE token = ?");
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
            return ['id' => $row['user_id'], 'type' => 'user'];
        }
        // If not found, check admin_tokens (for admin users)
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
        return false;
    }

    // ----- TOKEN GENERATION -----
    private function generateToken($length = 16) {
        return bin2hex(random_bytes($length));
    }

    // ----- REQUIRE TOKEN -----
    // This method checks for the token in the Authorization header, validates it (from either table),
    // and exits with an error if it's missing or invalid.
    private function requireToken() {
        $headers = apache_request_headers();
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
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

    // ----- ERROR HANDLING -----
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
    }
}
?>
