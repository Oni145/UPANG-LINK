<?php
class RequiredDocument {
    private $conn;
    private $table_name = "required_documents";
    
    // Properties
    public $document_id;
    public $request_id;
    public $document_type;
    public $file_name;
    public $file_path;
    public $uploaded_at;
    public $is_verified;
    
    // Upload directory
    private $upload_path = "../uploads/documents/";
    
    public function __construct($db) {
        $this->conn = $db;
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_path)) {
            mkdir($this->upload_path, 0777, true);
        }
    }
    
    public function upload($file) {
        // Validate file
        if(!$this->validateFile($file)) {
            return false;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $extension;
        $target_path = $this->upload_path . $new_filename;
        
        // Move uploaded file
        if(move_uploaded_file($file['tmp_name'], $target_path)) {
            // Save to database
            $query = "INSERT INTO " . $this->table_name . "
                    (request_id, document_type, file_name, file_path, is_verified)
                    VALUES (:request_id, :document_type, :file_name, :file_path, :is_verified)";
            
            $stmt = $this->conn->prepare($query);
            
            $this->file_name = $file['name'];
            $this->file_path = $new_filename;
            $this->is_verified = false;
            
            $stmt->bindParam(":request_id", $this->request_id);
            $stmt->bindParam(":document_type", $this->document_type);
            $stmt->bindParam(":file_name", $this->file_name);
            $stmt->bindParam(":file_path", $this->file_path);
            $stmt->bindParam(":is_verified", $this->is_verified);
            
            if($stmt->execute()) {
                $this->document_id = $this->conn->lastInsertId();
                return true;
            }
        }
        return false;
    }
    
    private function validateFile($file) {
        // Check file size (5MB limit)
        $max_size = 5 * 1024 * 1024;
        if($file['size'] > $max_size) {
            return false;
        }
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        if(!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        return true;
    }
    
    public function verify($document_id) {
        $query = "UPDATE " . $this->table_name . "
                SET is_verified = true
                WHERE document_id = ?";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $document_id);
        
        return $stmt->execute();
    }
} 