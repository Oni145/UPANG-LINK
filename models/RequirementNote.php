<?php
class RequirementNote {
    private $conn;
    private $table_name = "request_requirement_notes";
    
    public $note_id;
    public $request_id;
    public $admin_id;
    public $note;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Getter for the table name
    public function getTableName() {
        return $this->table_name;
    }
    
    // Add requirement note
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (request_id, admin_id, note)
                VALUES (:request_id, :admin_id, :note)";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":request_id", $this->request_id);
        $stmt->bindParam(":admin_id", $this->admin_id);
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
    
    // Update requirement note
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET note = :note 
                  WHERE note_id = :note_id AND admin_id = :admin_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":note", $this->note);
        $stmt->bindParam(":note_id", $this->note_id);
        $stmt->bindParam(":admin_id", $this->admin_id);
        return $stmt->execute();
    }
}
?>
