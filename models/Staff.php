<?php
class Staff {
    // Database connection and table name
    private $conn;
    private $table_name = "staffs";

    // Object properties
    public $staff_id;
    public $username;
    public $name;
    public $email;
    public $password;
    public $created_at;
    public $updated_at;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new staff record
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, name, email, password)
                  VALUES (:username, :name, :email, :password)";
        $stmt = $this->conn->prepare($query);

        // Bind values to placeholders
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":name",     $this->name);
        $stmt->bindParam(":email",    $this->email);
        $stmt->bindParam(":password", $this->password);

        // Execute query and return true if successful, false otherwise
        return $stmt->execute();
    }

    // Retrieve a single staff record by username
    public function getByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Retrieve a single staff record by staff_id
    public function getById($staff_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE staff_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $staff_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
