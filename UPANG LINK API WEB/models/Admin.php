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
        // Check if email or username already exists
        if ($this->emailExists($this->email) || $this->usernameExists($this->username)) {
            return ["status" => "error", "message" => "Email or Username already exists."];
        }
    
        // Insert new admin record
        $query = "INSERT INTO " . $this->table_name . " (username, email, first_name, last_name, password, role)
                  VALUES (:username, :email, :first_name, :last_name, :password, :role)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":password", $this->password);
        
        $role = 'admin';
        $stmt->bindParam(":role", $role);
    
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Admin registered successfully."];
        } else {
            return ["status" => "error", "message" => "Registration failed."];
        }
    }
    


    // Check if email already exists
public function emailExists($email) {
    $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0; // Returns true if email exists
}


    // Retrieve a single admin record by email
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? AND role = 'admin' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Retrieve a single admin record by admin_id
    public function getById($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? AND role = 'admin' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
