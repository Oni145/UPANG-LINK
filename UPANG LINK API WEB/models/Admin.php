<?php
class Admin {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
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
        $query = "INSERT INTO " . $this->table_name . " (email, first_name, last_name, password, role)
                  VALUES (:email, :first_name, :last_name, :password, 'admin')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":password", $this->password);
        return $stmt->execute();
    }

    // Retrieve a single admin record by username
    public function getByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? AND role = 'admin' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
