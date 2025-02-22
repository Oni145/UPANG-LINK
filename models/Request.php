<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Request {
    public $conn;
    public $request_id;
    public $user_id;
    public $type_id;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all requests
    public function read() {
        $query = "SELECT * FROM requests";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read one request by request_id
    public function readOne() {
        $query = "SELECT * FROM requests WHERE request_id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->request_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Read requests by user
    public function readByUser($user_id) {
        $query = "SELECT * FROM requests WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Read requests by status
    public function readByStatus($status) {
        $query = "SELECT * FROM requests WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        return $stmt;
    }

    // Create a new request and process file uploads
    public function createWithRequirements($files) {
        try {
            // Insert the request record
            $query = "INSERT INTO requests (user_id, type_id, status) VALUES (:user_id, :type_id, :status)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':type_id', $this->type_id);
            $stmt->bindParam(':status', $this->status);
            if (!$stmt->execute()) {
                error_log("Request insert error: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            $this->request_id = $this->conn->lastInsertId();

            // Define the physical upload directory
            $upload_dir = "../uploads/documents/";  // Physical directory on the server

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Process each uploaded file
            foreach ($files as $key => $file) {
                $target_file = $upload_dir . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    // Hard-code the file path rather than dynamically generating it
                    $file_path_to_store = "_BALITANG_KASAYSAYAN.pdf";
                    
                    // Insert file record into required_documents table
                    $queryDoc = "INSERT INTO required_documents (request_id, document_type, file_name, file_path) VALUES (:request_id, :document_type, :file_name, :file_path)";
                    $stmtDoc = $this->conn->prepare($queryDoc);
                    $document_type = $key; // key corresponds to the file input name
                    $stmtDoc->bindParam(':request_id', $this->request_id);
                    $stmtDoc->bindParam(':document_type', $document_type);
                    $stmtDoc->bindParam(':file_name', $file['name']);
                    $stmtDoc->bindParam(':file_path', $file_path_to_store);
                    if (!$stmtDoc->execute()) {
                        error_log("Required documents insert error: " . print_r($stmtDoc->errorInfo(), true));
                        return false;
                    }
                } else {
                    error_log("Failed to move uploaded file for key: $key");
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Exception in createWithRequirements: " . $e->getMessage());
            return false;
        }
    }

    // Update a request (allowing any valid status from the table's ENUM)
    public function update() {
        // Normalize the status value by trimming whitespace and converting to lowercase
        $status = strtolower(trim($this->status));
        
        // Allowed statuses as defined in your ENUM
        $allowedStatuses = ['pending', 'approved', 'rejected', 'in_progress', 'completed'];

        // Validate the status value
        if (!in_array($status, $allowedStatuses)) {
            error_log("Invalid status value: {$this->status}. Allowed values: " . implode(', ', $allowedStatuses));
            return false;
        }
    
        $query = "UPDATE requests SET status = :status WHERE request_id = :request_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':request_id', $this->request_id);
        if ($stmt->execute()) {
            return true;
        }
        error_log("Request update error: " . print_r($stmt->errorInfo(), true));
        return false;
    }

    // Delete a request
    public function delete() {
        $query = "DELETE FROM requests WHERE request_id = :request_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $this->request_id);
        if ($stmt->execute()) {
            return true;
        }
        error_log("Request delete error: " . print_r($stmt->errorInfo(), true));
        return false;
    }
}
?>
