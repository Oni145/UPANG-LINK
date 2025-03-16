<?php
class Database {
    private $host = "localhost";
    private $db_name = "upang_link";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            // Log the error instead of echoing it
            error_log("Database Connection Error: " . $e->getMessage());
            // Return null if connection fails
            return null;
        }

        return $this->conn;
    }
} 