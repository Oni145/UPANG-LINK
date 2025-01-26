<?php
class Request {
    private $conn;
    private $table_name = "requests";

    public $request_id;
    public $user_id;
    public $type_id;
    public $status;
    public $submitted_at;
    public $updated_at;

    // Add new properties for requirements
    public $requirements = [];
    public $documents = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create Request
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, type_id, status)
                VALUES (:user_id, :type_id, :status)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":type_id", $this->type_id);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            $this->request_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Read All Requests with Details
    public function read() {
        $query = "SELECT r.*, rt.name as request_type, rt.processing_time,
                  u.first_name, u.last_name, u.student_number,
                  c.name as category_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN request_types rt ON r.type_id = rt.type_id
                  LEFT JOIN users u ON r.user_id = u.user_id
                  LEFT JOIN categories c ON rt.category_id = c.category_id
                  ORDER BY r.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read Single Request with Details
    public function readOne() {
        $query = "SELECT r.*, rt.name as request_type, rt.processing_time,
                  u.first_name, u.last_name, u.student_number,
                  c.name as category_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN request_types rt ON r.type_id = rt.type_id
                  LEFT JOIN users u ON r.user_id = u.user_id
                  LEFT JOIN categories c ON rt.category_id = c.category_id
                  WHERE r.request_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->request_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update Request Status
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE request_id = :request_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":request_id", $this->request_id);

        return $stmt->execute();
    }

    // Delete Request
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE request_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->request_id);

        return $stmt->execute();
    }

    // Read Requests by User
    public function readByUser($user_id) {
        $query = "SELECT r.*, rt.name as request_type, rt.processing_time,
                  c.name as category_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN request_types rt ON r.type_id = rt.type_id
                  LEFT JOIN categories c ON rt.category_id = c.category_id
                  WHERE r.user_id = ?
                  ORDER BY r.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Read Requests by Status
    public function readByStatus($status) {
        $query = "SELECT r.*, rt.name as request_type, rt.processing_time,
                  u.first_name, u.last_name, u.student_number,
                  c.name as category_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN request_types rt ON r.type_id = rt.type_id
                  LEFT JOIN users u ON r.user_id = u.user_id
                  LEFT JOIN categories c ON rt.category_id = c.category_id
                  WHERE r.status = ?
                  ORDER BY r.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();

        return $stmt;
    }

    // Check if all requirements are met
    public function checkRequirements() {
        $query = "SELECT requirements FROM request_types WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->type_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) {
            // Convert JSON requirements string to array
            $required = json_decode($result['requirements'], true);
            if(!$required) return ['complete' => true]; // No requirements
            
            // Get submitted documents
            $query = "SELECT document_type, is_verified 
                     FROM required_documents 
                     WHERE request_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->request_id);
            $stmt->execute();
            
            $submitted = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $submitted[$row['document_type']] = $row['is_verified'];
            }
            
            // Check which requirements are missing
            $missing = [];
            foreach($required as $req) {
                if(!isset($submitted[$req])) {
                    $missing[] = $req;
                }
            }
            
            return [
                'complete' => empty($missing),
                'missing' => $missing,
                'submitted' => $submitted
            ];
        }
        return ['complete' => false, 'error' => 'Request type not found'];
    }

    // Create request with requirements check
    public function createWithRequirements($files = []) {
        try {
            $this->conn->beginTransaction();
            
            // First create the request
            if(!$this->create()) {
                throw new Exception("Failed to create request");
            }
            
            // Then handle document uploads
            if(!empty($files)) {
                $document = new RequiredDocument($this->conn);
                foreach($files as $type => $file) {
                    $document->request_id = $this->request_id;
                    $document->document_type = $type;
                    if(!$document->upload($file)) {
                        throw new Exception("Failed to upload document: " . $type);
                    }
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Get request details including requirements status
    public function readOneWithRequirements() {
        $request = $this->readOne();
        if($request) {
            // Get requirements status
            $requirements = $this->checkRequirements();
            $request['requirements'] = $requirements;
            
            // Get uploaded documents
            $query = "SELECT * FROM required_documents WHERE request_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->request_id);
            $stmt->execute();
            
            $documents = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $documents[] = $row;
            }
            $request['documents'] = $documents;
            
            return $request;
        }
        return false;
    }
} 