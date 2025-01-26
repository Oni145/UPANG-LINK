<?php
class RequirementTemplate {
    private $conn;
    private $table_name = "requirement_templates";
    
    public $template_id;
    public $type_id;
    public $requirement_name;
    public $description;
    public $file_types;
    public $is_required;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new requirement template
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (type_id, requirement_name, description, file_types, is_required)
                VALUES (:type_id, :requirement_name, :description, :file_types, :is_required)";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":type_id", $this->type_id);
        $stmt->bindParam(":requirement_name", $this->requirement_name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":file_types", $this->file_types);
        $stmt->bindParam(":is_required", $this->is_required);
        
        return $stmt->execute();
    }
    
    // Get requirements for a request type
    public function getByRequestType($type_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE type_id = ?";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $type_id);
        $stmt->execute();
        
        return $stmt;
    }
} 