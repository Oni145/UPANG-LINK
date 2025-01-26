<?php
class RequestType {
    private $conn;
    private $table_name = "request_types";

    public $type_id;
    public $category_id;
    public $name;
    public $description;
    public $requirements;
    public $processing_time;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT rt.*, c.name as category_name 
                FROM " . $this->table_name . " rt
                LEFT JOIN categories c ON rt.category_id = c.category_id
                WHERE rt.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT rt.*, c.name as category_name 
                FROM " . $this->table_name . " rt
                LEFT JOIN categories c ON rt.category_id = c.category_id
                WHERE rt.type_id = ? AND rt.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->type_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function readByCategory($category_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE category_id = ? AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        return $stmt;
    }
} 