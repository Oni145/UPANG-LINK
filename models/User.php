<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $student_number;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $course;
    public $year_level;
    public $block;
    public $admission_year;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (student_number, password, first_name, last_name, role, course, year_level, block, admission_year)
                VALUES (:student_number, :password, :first_name, :last_name, :role, :course, :year_level, :block, :admission_year)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":student_number", $this->student_number);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":course", $this->course);
        $stmt->bindParam(":year_level", $this->year_level);
        $stmt->bindParam(":block", $this->block);
        $stmt->bindParam(":admission_year", $this->admission_year);

        if($stmt->execute()) {
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
} 