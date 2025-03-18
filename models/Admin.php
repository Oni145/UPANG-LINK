<?php
class Admin {
    private $conn;
    private $table_name = "admins";

    public $admin_id;
    public $username;
    public $email;
    public $first_name;
    public $last_name;
    public $password;
    public $password_reset_token;    
    public $password_reset_expires;    
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new admin record
    public function create() {
<<<<<<< Updated upstream:models/Admin.php
        $query = "INSERT INTO " . $this->table_name . " (username, email, first_name, last_name, password)
                  VALUES (:username, :email, :first_name, :last_name, :password)";
=======
        // Check if email or username already exists
        if ($this->emailExists($this->email) || $this->usernameExists($this->username)) {
            return ["status" => "error", "message" => "Email or Username already exists."];
        }
    
        // Insert new admin record
        $query = "INSERT INTO " . $this->table_name . " (username, email, first_name, last_name, password, role)
                  VALUES (:username, :email, :first_name, :last_name, :password, :role)";
        
>>>>>>> Stashed changes:UPANG LINK API WEB/models/Admin.php
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

// Check if username already exists
public function usernameExists($username) {
    $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE username = :username";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0; // Returns true if username exists
}


    // Retrieve a single admin record by username
    public function getByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

    public function getAdminByToken($token) {
        $query = "SELECT a.* FROM admins a 
                  JOIN admin_tokens t ON a.admin_id = t.admin_id 
                  WHERE t.token = :token LIMIT 1";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    
    

    // Retrieve a single admin record by email
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Retrieve a single admin record by admin_id
    public function getById($admin_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE admin_id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $admin_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
