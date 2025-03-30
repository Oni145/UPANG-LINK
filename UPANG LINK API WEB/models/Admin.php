<?php
class Admin {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $email;
    public $first_name;
    public $last_name;
    public $password;
    public $role;
    public $password_reset_token;    
    public $password_reset_expires;    
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new admin record
    public function create() {
        // Check if email exists in any role
        $existingUser = $this->getUserByEmail($this->email);
        
        if ($existingUser) {
            return [
                "status" => "error", 
                "message" => "Email already registered as " . $existingUser['role'],
                "existing_role" => $existingUser['role']
            ];
        }
    
        // Insert new admin record
        $query = "INSERT INTO " . $this->table_name . " 
                  (email, first_name, last_name, password, role)
                  VALUES (:email, :first_name, :last_name, :password, :role)";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":password", $this->password);
    
        $role = 'admin';
        $stmt->bindParam(":role", $role);
    
        if ($stmt->execute()) {
            return [
                "status" => "success", 
                "message" => "Admin registered successfully."
            ];
        }
        
        return [
            "status" => "error", 
            "message" => "Registration failed."
        ];
    }
    
    // Check if email exists in any role (admin or student)
    public function emailExists($email) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    // Get user by email regardless of role
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get admin by email (role-specific)
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE email = ? AND role = 'admin' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get admin by ID (role-specific)
    public function getById($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? AND role = 'admin' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>