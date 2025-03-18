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
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            error_log("Attempting to connect to database: " . $dsn);
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            error_log("Database connection successful");
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            error_log("DSN: mysql:host=" . $this->host . ";dbname=" . $this->db_name);
            error_log("Username: " . $this->username);
            throw new Exception("Database connection failed: " . $e->getMessage(), 500);
        }

        return $this->conn;
    }
} 