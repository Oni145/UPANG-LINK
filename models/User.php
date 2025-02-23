<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $student_number;
    public $email; // Email property
    public $password;
    public $first_name;
    public $last_name;
    // Removed role property
    public $course;
    public $year_level;
    public $block;
    public $admission_year;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if a user exists with the given student number
    public function getByStudentNumber($student_number) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE student_number = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_number);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check if a user exists with the given email
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create() {
        // Check for duplicate student number
        if ($this->getByStudentNumber($this->student_number)) {
            throw new Exception("Duplicate student number found.");
        }
        // Check for duplicate email
        if ($this->getByEmail($this->email)) {
            throw new Exception("Duplicate email found.");
        }

        $query = "INSERT INTO " . $this->table_name . "
                (student_number, email, password, first_name, last_name, course, year_level, block, admission_year)
                VALUES (:student_number, :email, :password, :first_name, :last_name, :course, :year_level, :block, :admission_year)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":student_number", $this->student_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        // Removed role binding
        $stmt->bindParam(":course", $this->course);
        $stmt->bindParam(":year_level", $this->year_level);
        $stmt->bindParam(":block", $this->block);
        $stmt->bindParam(":admission_year", $this->admission_year);

        if ($stmt->execute()) {
            return true;
        }
        return false;
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

    public function update() {
        // Build the SET clause without role
        $query = "UPDATE " . $this->table_name . " SET 
                  first_name = :first_name, 
                  last_name = :last_name, 
                  email = :email, 
                  course = :course, 
                  year_level = :year_level, 
                  block = :block, 
                  admission_year = :admission_year, 
                  updated_at = NOW() 
                  WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":first_name", $this->first_name);
        $stmt->bindValue(":last_name", $this->last_name);
        $stmt->bindValue(":email", $this->email);
        $stmt->bindValue(":course", $this->course);
        $stmt->bindValue(":year_level", $this->year_level);
        $stmt->bindValue(":block", $this->block);
        $stmt->bindValue(":admission_year", $this->admission_year);
        $stmt->bindValue(":user_id", $this->user_id);

        return $stmt->execute();
    }
}
?>
