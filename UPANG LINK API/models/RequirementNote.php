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
        // Log the request_id being used
        error_log("Getting requirement notes for request_id: " . $request_id);
        
        // Make sure the request_id is treated as an integer
        $request_id = (int)$request_id;
        error_log("Cast request_id to integer: " . $request_id);
        
        // First check if the table exists and has records
        try {
            $checkQuery = "SELECT COUNT(*) as count FROM " . $this->table_name;
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Total notes in table: " . $result['count']);
        } catch (Exception $e) {
            error_log("Error checking table: " . $e->getMessage());
        }
        
        // Try a direct query first to debug
        try {
            $debugQuery = "SELECT * FROM " . $this->table_name . " WHERE request_id = ?";
            $debugStmt = $this->conn->prepare($debugQuery);
            $debugStmt->bindParam(1, $request_id, PDO::PARAM_INT);
            $debugStmt->execute();
            $count = $debugStmt->rowCount();
            error_log("Direct debug query found " . $count . " notes");
            if ($count > 0) {
                $sample = $debugStmt->fetch(PDO::FETCH_ASSOC);
                error_log("Sample note from debug query: " . json_encode($sample));
            }
        } catch (Exception $e) {
            error_log("Error in debug query: " . $e->getMessage());
        }
        
        // Query to get notes for a specific request
        $query = "SELECT rn.*, u.first_name, u.last_name 
                FROM " . $this->table_name . " rn
                LEFT JOIN users u ON rn.admin_id = u.user_id
                WHERE rn.request_id = ?
                ORDER BY rn.created_at DESC";
                
        error_log("Executing query: " . $query . " with request_id: " . $request_id);
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $request_id, PDO::PARAM_INT); // Ensure it's treated as an integer
        $stmt->execute();
        
        // Log the result count
        $count = $stmt->rowCount();
        error_log("Found " . $count . " notes for request_id: " . $request_id);
        
        return $stmt;
    }
} 