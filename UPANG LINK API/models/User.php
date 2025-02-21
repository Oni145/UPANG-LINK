<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $student_number;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $course;
    public $year_level;
    public $block;
    public $admission_year;
    public $email_verified;
    public $email_verification_token;
    public $email_token_expiry;
    public $created_at;
    public $updated_at;
    public $reset_password_token;
    public $reset_token_expiry;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (student_number, email, password, first_name, last_name, role, course, year_level, block, admission_year, email_verification_token, email_token_expiry)
                VALUES (:student_number, :email, :password, :first_name, :last_name, :role, :course, :year_level, :block, :admission_year, :email_verification_token, :email_token_expiry)";

        $stmt = $this->conn->prepare($query);

        // Generate verification token
        $this->email_verification_token = bin2hex(random_bytes(32));
        $this->email_token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt->bindParam(":student_number", $this->student_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":course", $this->course);
        $stmt->bindParam(":year_level", $this->year_level);
        $stmt->bindParam(":block", $this->block);
        $stmt->bindParam(":admission_year", $this->admission_year);
        $stmt->bindParam(":email_verification_token", $this->email_verification_token);
        $stmt->bindParam(":email_token_expiry", $this->email_token_expiry);

        if($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function verifyEmail($token) {
        $query = "SELECT user_id, email_token_expiry FROM " . $this->table_name . "
                WHERE email_verification_token = ? AND email_verified = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if token has expired
            if(strtotime($row['email_token_expiry']) < time()) {
                return ['status' => 'error', 'message' => 'Verification token has expired'];
            }

            // Update user as verified
            $query = "UPDATE " . $this->table_name . "
                    SET email_verified = 1,
                        email_verification_token = NULL,
                        email_token_expiry = NULL
                    WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $row['user_id']);
            
            if($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Email verified successfully'];
            }
        }
        
        return ['status' => 'error', 'message' => 'Invalid verification token'];
    }

    public function createSession($user_id, $device_info = null, $ip_address = null) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token expires in 24 hours

        $query = "INSERT INTO user_sessions
                (user_id, token, device_info, ip_address, expires_at)
                VALUES (:user_id, :token, :device_info, :ip_address, :expires_at)";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":device_info", $device_info);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":expires_at", $expires_at);

        if($stmt->execute()) {
            return ['token' => $token, 'expires_at' => $expires_at];
        }
        return false;
    }

    public function validateSession($token) {
        $query = "SELECT us.*, u.email_verified 
                FROM user_sessions us
                JOIN users u ON us.user_id = u.user_id
                WHERE us.token = ? AND us.is_active = 1 AND us.expires_at > NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update last activity
            $this->updateSessionActivity($token);
            
            return [
                'valid' => true,
                'user_id' => $session['user_id'],
                'email_verified' => $session['email_verified']
            ];
        }
        return ['valid' => false];
    }

    public function logout($token) {
        $query = "UPDATE user_sessions 
                SET is_active = 0 
                WHERE token = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        
        return $stmt->execute();
    }

    private function updateSessionActivity($token) {
        $query = "UPDATE user_sessions 
                SET last_activity = CURRENT_TIMESTAMP 
                WHERE token = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        
        return $stmt->execute();
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByStudentNumber($student_number) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE student_number = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_number);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET first_name = :first_name,
                    last_name = :last_name,
                    course = :course,
                    year_level = :year_level,
                    block = :block
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":course", $this->course);
        $stmt->bindParam(":year_level", $this->year_level);
        $stmt->bindParam(":block", $this->block);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function regenerateVerificationToken() {
        $this->email_verification_token = bin2hex(random_bytes(32));
        $this->email_token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $query = "UPDATE " . $this->table_name . "
                SET email_verification_token = :token,
                    email_token_expiry = :expiry
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $this->email_verification_token);
        $stmt->bindParam(":expiry", $this->email_token_expiry);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function generateResetToken() {
        $this->reset_password_token = bin2hex(random_bytes(32));
        $this->reset_token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "UPDATE " . $this->table_name . "
                SET reset_password_token = :token,
                    reset_token_expiry = :expiry
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $this->reset_password_token);
        $stmt->bindParam(":expiry", $this->reset_token_expiry);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function validateResetToken($token) {
        $query = "SELECT user_id, reset_token_expiry FROM " . $this->table_name . "
                WHERE reset_password_token = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if token has expired
            if(strtotime($row['reset_token_expiry']) < time()) {
                return ['status' => 'error', 'message' => 'Reset token has expired'];
            }

            return ['status' => 'success', 'user_id' => $row['user_id']];
        }
        
        return ['status' => 'error', 'message' => 'Invalid reset token'];
    }

    public function resetPassword($new_password) {
        $query = "UPDATE " . $this->table_name . "
                SET password = :password,
                    reset_password_token = NULL,
                    reset_token_expiry = NULL
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }
} 