<?php
class RequirementNote {
    private $conn;
    private $table_name = "request_requirement_notes";
    
    public $note_id;
    public $request_id;
    public $admin_id;
    public $requirement_name;
    public $note;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Add requirement note
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (request_id, admin_id, requirement_name, note)
                VALUES (:request_id, :admin_id, :requirement_name, :note)";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":request_id", $this->request_id);
        $stmt->bindParam(":admin_id", $this->admin_id);
        $stmt->bindParam(":requirement_name", $this->requirement_name);
        $stmt->bindParam(":note", $this->note);
        
        return $stmt->execute();
    }
    
    // Get notes for a request
    public function getByRequest($request_id) {
        $query = "SELECT rn.*, u.first_name, u.last_name 
                FROM " . $this->table_name . " rn
                LEFT JOIN users u ON rn.admin_id = u.user_id
                WHERE request_id = ?
                ORDER BY created_at DESC";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $request_id);
        $stmt->execute();
        
        return $stmt;
    }
} 