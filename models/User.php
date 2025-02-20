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
                (student_number, email, password, first_name, last_name, role, course, year_level, block, admission_year)
                VALUES (:student_number, :email, :password, :first_name, :last_name, :role, :course, :year_level, :block, :admission_year)";

        $stmt = $this->conn->prepare($query);

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
        // Prepare fields that are always updated, now including email
        $fields = [
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'email'          => $this->email,
            'course'         => $this->course,
            'year_level'     => $this->year_level,
            'block'          => $this->block,
            'admission_year' => $this->admission_year
        ];
        
        // Build the SET clause; include role if it's provided (not empty)
        $setClause = "first_name = :first_name, last_name = :last_name, email = :email, course = :course, year_level = :year_level, block = :block, admission_year = :admission_year, updated_at = NOW()";
        if (!empty($this->role)) {
            $setClause = "first_name = :first_name, last_name = :last_name, role = :role, email = :email, course = :course, year_level = :year_level, block = :block, admission_year = :admission_year, updated_at = NOW()";
            $fields['role'] = $this->role;
        }
        
        $query = "UPDATE " . $this->table_name . " SET " . $setClause . " WHERE user_id = :user_id";
        $fields['user_id'] = $this->user_id;
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($fields as $key => $value) {
            $stmt->bindValue(":" . $key, $value);
        }
        
        return $stmt->execute();
    }
}
?>
