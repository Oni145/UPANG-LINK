<?php
class Request {
    private $conn;
    private $table = 'requests';

    public function __construct($db) {
        $this->conn = $db;
        $this->ensureTablesExist();
    }

    private function ensureTablesExist() {
        try {
            // Create request_types table if not exists
            $query = "CREATE TABLE IF NOT EXISTS request_types (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                requirements TEXT,
                processing_time VARCHAR(50),
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);

            // Create requests table if not exists
            $query = "CREATE TABLE IF NOT EXISTS requests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                tracking_number VARCHAR(20),
                user_id INT NOT NULL,
                type_id INT NOT NULL,
                description TEXT,
                status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'REJECTED') DEFAULT 'PENDING',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id),
                FOREIGN KEY (type_id) REFERENCES request_types(id)
            )";
            $this->conn->exec($query);
        } catch (PDOException $e) {
            error_log("Database setup error: " . $e->getMessage());
            throw new Exception("Failed to setup database tables", 500);
        }
    }

    public function getAll($user_id) {
        try {
            $query = "SELECT 
                        r.*, 
                        rt.name as request_type,
                        u.first_name,
                        u.last_name,
                        u.email
                    FROM 
                        " . $this->table . " r
                        LEFT JOIN request_types rt ON r.type_id = rt.id
                        LEFT JOIN users u ON r.user_id = u.user_id
                    WHERE r.user_id = :user_id
                    ORDER BY r.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($result)) {
                throw new Exception("No requests found", 404);
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to fetch requests", 500);
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT 
                        r.*, 
                        rt.name as request_type,
                        u.first_name,
                        u.last_name,
                        u.email
                    FROM 
                        " . $this->table . " r
                        LEFT JOIN request_types rt ON r.type_id = rt.id
                        LEFT JOIN users u ON r.user_id = u.user_id
                    WHERE r.id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to fetch request", 500);
        }
    }

    public function getTypes() {
        try {
            $query = "SELECT type_id, name, description, requirements, processing_time FROM request_types WHERE is_active = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process each type to ensure requirements are properly formatted
            foreach ($types as &$type) {
                error_log("Processing type: " . $type['name'] . ", Raw requirements: " . print_r($type['requirements'], true));
                
                if (!empty($type['requirements'])) {
                    // Try to decode JSON
                    $requirements = json_decode($type['requirements'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $type['requirements'] = $requirements;
                        error_log("Successfully decoded JSON requirements for " . $type['name']);
                    } else {
                        error_log("JSON decode error for " . $type['name'] . ": " . json_last_error_msg());
                        // Try to parse as comma-separated list
                        $fields = array_map('trim', explode(',', $type['requirements']));
                        $type['requirements'] = [
                            'fields' => array_map(function($field) {
                                return [
                                    'name' => $field,
                                    'label' => ucwords(str_replace('_', ' ', $field)),
                                    'type' => 'text',
                                    'required' => true,
                                    'description' => 'Please enter ' . strtolower(str_replace('_', ' ', $field))
                                ];
                            }, $fields),
                            'instructions' => 'Please fill out all required fields.'
                        ];
                        error_log("Parsed requirements as comma-separated list for " . $type['name']);
                    }
                } else {
                    error_log("No requirements found for " . $type['name'] . ", using default structure");
                    $type['requirements'] = [
                        'fields' => [],
                        'instructions' => 'Please fill out all required fields.'
                    ];
                }
                
                error_log("Final requirements for " . $type['name'] . ": " . print_r($type['requirements'], true));
            }
            
            return $types;
        } catch (PDOException $e) {
            error_log("Database error in getTypes: " . $e->getMessage());
            throw new Exception("Failed to fetch request types", 500);
        }
    }

    public function getStatistics($user_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected
                    FROM " . $this->table . "
                    WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to fetch request statistics", 500);
        }
    }

    public function create($data) {
        try {
            // Generate tracking number
            $tracking_number = $this->generateTrackingNumber();
            
            $query = "INSERT INTO " . $this->table . " 
                        (tracking_number, user_id, type_id, description, status, created_at) 
                    VALUES 
                        (:tracking_number, :user_id, :type_id, :description, 'PENDING', NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            // Clean data
            $user_id = htmlspecialchars(strip_tags($data->user_id));
            $type_id = htmlspecialchars(strip_tags($data->type_id));
            $description = htmlspecialchars(strip_tags($data->description));
            
            // Bind data
            $stmt->bindParam(':tracking_number', $tracking_number);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':type_id', $type_id);
            $stmt->bindParam(':description', $description);
            
            if($stmt->execute()) {
                $id = $this->conn->lastInsertId();
                return $this->getById($id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to create request", 500);
        }
    }

    private function generateTrackingNumber() {
        // Format: REQ-YYYYMMDD-XXXX where X is a random number
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "REQ-{$date}-{$random}";
    }

    public function update($id, $data) {
        try {
            $updateFields = [];
            $params = [];
            
            // Only update fields that are provided
            if(isset($data->status)) {
                $updateFields[] = "status = :status";
                $params[':status'] = htmlspecialchars(strip_tags($data->status));
            }
            
            if(isset($data->description)) {
                $updateFields[] = "description = :description";
                $params[':description'] = htmlspecialchars(strip_tags($data->description));
            }
            
            if(empty($updateFields)) {
                return false;
            }
            
            $query = "UPDATE " . $this->table . " 
                    SET " . implode(", ", $updateFields) . "
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $params[':id'] = $id;
            
            foreach($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to update request", 500);
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to delete request", 500);
        }
    }
} 