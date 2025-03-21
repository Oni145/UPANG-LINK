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
                status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'REJECTED', 'CANCELLED') DEFAULT 'PENDING',
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
                        LEFT JOIN request_types rt ON r.type_id = rt.type_id
                        LEFT JOIN users u ON r.user_id = u.user_id
                    WHERE r.user_id = :user_id
                    ORDER BY r.submitted_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        LEFT JOIN request_types rt ON r.type_id = rt.type_id
                        LEFT JOIN users u ON r.user_id = u.user_id
                    WHERE r.request_id = ?";
            
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
            // First, check student details
            $query = "SELECT current_year, course, student_number, birthdate, emergency_contact, details_complete 
                     FROM users 
                     WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $data->user_id);
            $stmt->execute();
            
            $studentDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if student details are incomplete
            $isIncomplete = !$studentDetails || 
                           empty($studentDetails['current_year']) || 
                           empty($studentDetails['course']) || 
                           empty($studentDetails['student_number']) ||
                           empty($studentDetails['birthdate']) ||
                           empty($studentDetails['emergency_contact']) ||
                           !$studentDetails['details_complete'];
            
            // Generate tracking number
            $tracking_number = $this->generateTrackingNumber();
            
            $query = "INSERT INTO " . $this->table . " 
                        (tracking_number, user_id, type_id, description, status, created_at) 
                    VALUES 
                        (:tracking_number, :user_id, :type_id, :description, :status, NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            // Clean data
            $user_id = htmlspecialchars(strip_tags($data->user_id));
            $type_id = htmlspecialchars(strip_tags($data->type_id));
            $description = htmlspecialchars(strip_tags($data->description));
            
            // Set status based on student details completeness
            $status = $isIncomplete ? 'REJECTED' : 'PENDING';
            
            // Bind data
            $stmt->bindParam(':tracking_number', $tracking_number);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':type_id', $type_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':status', $status);
            
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

    public function getRequestType($typeId) {
        try {
            // First check if the type_id column exists
            $columnsQuery = "SHOW COLUMNS FROM request_types LIKE 'type_id'";
            $columnsStmt = $this->conn->prepare($columnsQuery);
            $columnsStmt->execute();
            $typeIdExists = $columnsStmt->rowCount() > 0;
            
            // Check if category_id column exists
            $categoryColumnsQuery = "SHOW COLUMNS FROM request_types LIKE 'category_id'";
            $categoryColumnsStmt = $this->conn->prepare($categoryColumnsQuery);
            $categoryColumnsStmt->execute();
            $categoryIdExists = $categoryColumnsStmt->rowCount() > 0;
            
            // Check if categories table exists
            $categoriesTableQuery = "SHOW TABLES LIKE 'categories'";
            $categoriesTableStmt = $this->conn->prepare($categoriesTableQuery);
            $categoriesTableStmt->execute();
            $categoriesTableExists = $categoriesTableStmt->rowCount() > 0;
            
            if (!$typeIdExists) {
                // If type_id doesn't exist, use id instead
                error_log("type_id column does not exist in request_types table, using id instead");
                
                if ($categoryIdExists && $categoriesTableExists) {
                    $query = "SELECT 
                                rt.id as type_id,
                                rt.name,
                                rt.description,
                                rt.requirements,
                                rt.processing_time,
                                c.name as category_name
                            FROM 
                                request_types rt
                                LEFT JOIN categories c ON rt.category_id = c.category_id
                            WHERE 
                                rt.id = ?";
                } else {
                    // No category join needed
                    $query = "SELECT 
                                rt.id as type_id,
                                rt.name,
                                rt.description,
                                rt.requirements,
                                rt.processing_time,
                                'General' as category_name
                            FROM 
                                request_types rt
                            WHERE 
                                rt.id = ?";
                }
            } else {
                // Use type_id as normal
                if ($categoryIdExists && $categoriesTableExists) {
                    $query = "SELECT 
                                rt.type_id,
                                rt.name,
                                rt.description,
                                rt.requirements,
                                rt.processing_time,
                                c.name as category_name
                            FROM 
                                request_types rt
                                LEFT JOIN categories c ON rt.category_id = c.category_id
                            WHERE 
                                rt.type_id = ?";
                } else {
                    // No category join needed
                    $query = "SELECT 
                                rt.type_id,
                                rt.name,
                                rt.description,
                                rt.requirements,
                                rt.processing_time,
                                'General' as category_name
                            FROM 
                                request_types rt
                            WHERE 
                                rt.type_id = ?";
                }
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $typeId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($result) {
                // Parse requirements JSON if it exists
                if(!empty($result['requirements'])) {
                    if (is_string($result['requirements'])) {
                        $requirements = json_decode($result['requirements'], true);
                        if(json_last_error() === JSON_ERROR_NONE) {
                            // Only include fields that are relevant to this request type
                            if (isset($requirements['fields'])) {
                                $relevantFields = [];
                                foreach ($requirements['fields'] as $field) {
                                    // Check if this field is specific to this request type
                                    // For now, we include all fields since we're already filtering by request type
                                    $relevantFields[] = $field;
                                }
                                $requirements['fields'] = $relevantFields;
                            }
                            $result['requirements'] = $requirements;
                        } else {
                            error_log("Error parsing requirements JSON in getRequestType: " . json_last_error_msg());
                            // If JSON parsing fails, create a basic structure
                            $result['requirements'] = [
                                'fields' => [],
                                'instructions' => 'Please fill out all required fields.'
                            ];
                        }
                    }
                }
                
                // Filter out any empty or null values
                $cleanResult = [];
                foreach ($result as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $cleanResult[$key] = $value;
                    }
                }
                
                return $cleanResult;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            error_log("Database error in getRequestType: " . $e->getMessage());
            return null;
        }
    }

    public function getDetailsByTrackingNumber($tracking_number, $user_id) {
        try {
            error_log("Getting request details by tracking number: $tracking_number for user: $user_id");
            
            // First check if the request_id column exists
            $columnsQuery = "SHOW COLUMNS FROM " . $this->table . " LIKE 'request_id'";
            $columnsStmt = $this->conn->prepare($columnsQuery);
            $columnsStmt->execute();
            $requestIdExists = $columnsStmt->rowCount() > 0;
            
            // Check if category_id column exists
            $categoryColumnsQuery = "SHOW COLUMNS FROM request_types LIKE 'category_id'";
            $categoryColumnsStmt = $this->conn->prepare($categoryColumnsQuery);
            $categoryColumnsStmt->execute();
            $categoryIdExists = $categoryColumnsStmt->rowCount() > 0;
            
            // Check if categories table exists
            $categoriesTableQuery = "SHOW TABLES LIKE 'categories'";
            $categoriesTableStmt = $this->conn->prepare($categoriesTableQuery);
            $categoriesTableStmt->execute();
            $categoriesTableExists = $categoriesTableStmt->rowCount() > 0;
            
            // Check if type_id column exists in request_types
            $typeIdColumnsQuery = "SHOW COLUMNS FROM request_types LIKE 'type_id'";
            $typeIdColumnsStmt = $this->conn->prepare($typeIdColumnsQuery);
            $typeIdColumnsStmt->execute();
            $typeIdExists = $typeIdColumnsStmt->rowCount() > 0;
            
            // Build the query based on the table structure
            if (!$requestIdExists) {
                // If request_id doesn't exist, use id instead
                error_log("request_id column does not exist, using id instead");
                
                if ($categoryIdExists && $categoriesTableExists && $typeIdExists) {
                    $query = "SELECT 
                                r.id as request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                rt.name as document_type,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                rt.processing_time,
                                rt.requirements,
                                u.first_name,
                                u.last_name,
                                c.name as category_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.type_id
                                LEFT JOIN users u ON r.user_id = u.user_id
                                LEFT JOIN categories c ON rt.category_id = c.category_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                } else if (!$typeIdExists) {
                    // If type_id doesn't exist in request_types, use id instead
                    $query = "SELECT 
                                r.id as request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                rt.name as document_type,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                rt.processing_time,
                                rt.requirements,
                                u.first_name,
                                u.last_name,
                                'General' as category_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                } else {
                    // No category join needed
                    $query = "SELECT 
                                r.id as request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                rt.name as document_type,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                rt.processing_time,
                                rt.requirements,
                                u.first_name,
                                u.last_name,
                                'General' as category_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.type_id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                }
            } else {
                // Use request_id as normal
                if ($categoryIdExists && $categoriesTableExists && $typeIdExists) {
                    $query = "SELECT 
                                r.request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                rt.name as document_type,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                rt.processing_time,
                                rt.requirements,
                                u.first_name,
                                u.last_name,
                                c.name as category_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.type_id
                                LEFT JOIN users u ON r.user_id = u.user_id
                                LEFT JOIN categories c ON rt.category_id = c.category_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                } else if (!$typeIdExists) {
                    // If type_id doesn't exist in request_types, use id instead
                    $query = "SELECT 
                                r.request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                rt.name as document_type,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                rt.processing_time,
                                rt.requirements,
                                u.first_name,
                                u.last_name,
                                'General' as category_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                } else {
                    // No category join needed
                    $query = "SELECT 
                                r.request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                rt.name as document_type,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                rt.processing_time,
                                rt.requirements,
                                u.first_name,
                                u.last_name,
                                'General' as category_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.type_id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                }
            }
            
            error_log("Executing query with tracking_number=$tracking_number and user_id=$user_id");
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $tracking_number);
            $stmt->bindParam(2, $user_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($result) {
                error_log("Found request with tracking number: $tracking_number. Request ID: " . $result['request_id']);
                
                // Initialize requirements with a safe default structure
                $result['requirements'] = [
                    'fields' => [],
                    'instructions' => 'Please fill out all required fields.'
                ];
                
                // Parse requirements JSON if it exists
                if(!empty($result['requirements'])) {
                    error_log("Requirements before parsing: " . print_r($result['requirements'], true));
                    try {
                        if (is_string($result['requirements'])) {
                            $requirements = json_decode($result['requirements'], true);
                            if(json_last_error() === JSON_ERROR_NONE && is_array($requirements)) {
                                // Ensure the requirements have the correct structure
                                if (!isset($requirements['fields'])) {
                                    $requirements['fields'] = [];
                                }
                                if (!isset($requirements['instructions'])) {
                                    $requirements['instructions'] = 'Please fill out all required fields.';
                                }
                                $result['requirements'] = $requirements;
                                error_log("Requirements parsed successfully");
                            } else {
                                error_log("Error parsing requirements JSON: " . json_last_error_msg());
                                // Keep the default structure
                            }
                        } else if (is_array($result['requirements'])) {
                            // If it's already an array, ensure it has the correct structure
                            if (!isset($result['requirements']['fields'])) {
                                $result['requirements']['fields'] = [];
                            }
                            if (!isset($result['requirements']['instructions'])) {
                                $result['requirements']['instructions'] = 'Please fill out all required fields.';
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error processing requirements: " . $e->getMessage());
                        // Keep the default structure
                    }
                } else {
                    error_log("No requirements found for this request type");
                }
                
                // Ensure requirements is properly structured before proceeding
                if (!is_array($result['requirements']) || !isset($result['requirements']['fields'])) {
                    $result['requirements'] = [
                        'fields' => [],
                        'instructions' => 'Please fill out all required fields.'
                    ];
                }
                
                try {
                    // Fetch associated files from request_files table
                    // First check if the table exists
                    $tableExistsQuery = "SHOW TABLES LIKE 'request_files'";
                    $tableExistsStmt = $this->conn->prepare($tableExistsQuery);
                    $tableExistsStmt->execute();
                    $tableExists = $tableExistsStmt->rowCount() > 0;
                    
                    if ($tableExists) {
                        $filesQuery = "SELECT 
                                        file_id,
                                        request_id,
                                        field_name,
                                        original_name,
                                        file_name,
                                        file_path,
                                        file_type,
                                        file_size,
                                        uploaded_at
                                      FROM 
                                        request_files
                                      WHERE 
                                        request_id = ?";
                        
                        error_log("Fetching files for request_id: " . $result['request_id']);
                        $filesStmt = $this->conn->prepare($filesQuery);
                        $filesStmt->bindParam(1, $result['request_id']);
                        $filesStmt->execute();
                        $rawFiles = $filesStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Found " . count($rawFiles) . " files for this request");
                        
                        // Get the list of valid field names for this request type
                        $validFieldNames = [];
                        if (!empty($result['requirements'])) {
                            $requirements = is_string($result['requirements']) ? json_decode($result['requirements'], true) : $result['requirements'];
                            if (is_array($requirements) && isset($requirements['fields'])) {
                                foreach ($requirements['fields'] as $field) {
                                    $validFieldNames[] = $field['name'];
                                }
                            }
                        }
                        
                        // Filter out empty or null values from each file record
                        // And only include files that match the current request type's fields
                        $files = [];
                        foreach ($rawFiles as $file) {
                            // Skip files that don't match the current request type
                            if (!empty($validFieldNames) && !in_array($file['field_name'], $validFieldNames)) {
                                continue;
                            }
                            
                            $cleanFile = [];
                            foreach ($file as $key => $value) {
                                if ($value !== null && $value !== '') {
                                    $cleanFile[$key] = $value;
                                }
                            }
                            $files[] = $cleanFile;
                        }
                        
                        // Organize files by field name for easier access in the frontend
                        $organizedFiles = [];
                        foreach ($files as $file) {
                            $fieldName = $file['field_name'];
                            if (!isset($organizedFiles[$fieldName])) {
                                $organizedFiles[$fieldName] = [];
                            }
                            $organizedFiles[$fieldName][] = $file;
                        }
                    } else {
                        error_log("request_files table does not exist");
                        $files = [];
                        $organizedFiles = [];
                    }
                    
                    // Check if all required files have been uploaded
                    $requirementStatus = [];
                    try {
                        // For course modules, we want to set an empty requirements structure
                        if (stripos($result['request_type'], 'Course Module') !== false) {
                            error_log("Setting up course module requirements structure");
                            $result['requirements'] = [
                                'fields' => [],
                                'instructions' => 'No additional information needed for this request.',
                                'key' => $result['request_type'] . '_' . $result['type_id'] . '_' . time(),
                                'force_refresh' => true
                            ];
                        }
                        
                        // Ensure we have a valid requirements structure
                        if (!isset($result['requirements']) || !is_array($result['requirements'])) {
                            $result['requirements'] = [
                                'fields' => [],
                                'instructions' => 'Please fill out all required fields.'
                            ];
                        }
                        
                        // Ensure fields is an array
                        if (!isset($result['requirements']['fields']) || !is_array($result['requirements']['fields'])) {
                            $result['requirements']['fields'] = [];
                        }
                        
                        // Process each field safely
                        foreach ($result['requirements']['fields'] as $field) {
                            try {
                                if (!is_array($field)) {
                                    error_log("Invalid field structure (not an array): " . print_r($field, true));
                                    continue;
                                }
                                
                                if (!isset($field['type']) || !isset($field['name'])) {
                                    error_log("Invalid field structure (missing required properties): " . print_r($field, true));
                                    continue;
                                }
                                
                                // Only process file type fields that are required
                                if ($field['type'] === 'file' && ($field['required'] ?? false)) {
                                    $fieldName = $field['name'];
                                    
                                    // Get files for this field
                                    $fieldFiles = isset($organizedFiles[$fieldName]) ? $organizedFiles[$fieldName] : [];
                                    
                                    // Create status object with only non-empty fields
                                    $status = [
                                        'name' => $fieldName,
                                        'label' => $field['label'] ?? $fieldName,
                                        'required' => true,
                                        'uploaded' => !empty($fieldFiles)
                                    ];
                                    
                                    // Only add files if they exist and are properly structured
                                    if (!empty($fieldFiles) && is_array($fieldFiles)) {
                                        $cleanFiles = [];
                                        foreach ($fieldFiles as $file) {
                                            if (is_array($file)) {
                                                $cleanFile = [];
                                                foreach ($file as $key => $value) {
                                                    if ($value !== null && $value !== '') {
                                                        $cleanFile[$key] = $value;
                                                    }
                                                }
                                                if (!empty($cleanFile)) {
                                                    $cleanFiles[] = $cleanFile;
                                                }
                                            }
                                        }
                                        if (!empty($cleanFiles)) {
                                            $status['files'] = $cleanFiles;
                                        }
                                    }
                                    
                                    $requirementStatus[$fieldName] = $status;
                                }
                            } catch (Exception $e) {
                                error_log("Error processing field: " . $e->getMessage());
                                continue;
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error processing requirements and files: " . $e->getMessage());
                        $requirementStatus = [];
                    }
                } catch (Exception $e) {
                    error_log("Error processing files: " . $e->getMessage());
                    // Continue execution even if there's an error with files
                    $files = [];
                    $organizedFiles = [];
                    $requirementStatus = [];
                }
                
                try {
                    // Fetch associated notes from request_notes table if it exists
                    // First check if the table exists
                    $tableExistsQuery = "SHOW TABLES LIKE 'request_notes'";
                    $tableExistsStmt = $this->conn->prepare($tableExistsQuery);
                    $tableExistsStmt->execute();
                    $tableExists = $tableExistsStmt->rowCount() > 0;
                    
                    if ($tableExists) {
                        $notesQuery = "SELECT 
                                        note_id,
                                        request_id,
                                        user_id as admin_id,
                                        note,
                                        created_at
                                      FROM 
                                        request_notes
                                      WHERE 
                                        request_id = ?";
                        
                        error_log("Fetching notes for request_id: " . $result['request_id']);
                        $notesStmt = $this->conn->prepare($notesQuery);
                        $notesStmt->bindParam(1, $result['request_id']);
                        $notesStmt->execute();
                        $rawNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Found " . count($rawNotes) . " notes for this request");
                        
                        // Get the valid field names for this request type
                        $validFieldNames = [];
                        if (!empty($requirements)) {
                            // Handle course modules specially
                            if (stripos($result['request_type'], 'Course Module') !== false) {
                                // No fields to validate for course modules
                                $validFieldNames = [];
                                error_log("Course module request - skipping field validation");
                            } else {
                                try {
                                    // Make sure requirements is an array
                                    if (is_string($requirements)) {
                                        $requirements = json_decode($requirements, true);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            error_log("JSON decode error in notes section: " . json_last_error_msg());
                                            $requirements = ['fields' => []];
                                        }
                                    }
                                    
                                    // Ensure requirements is an array
                                    if (!is_array($requirements)) {
                                        error_log("Requirements is not an array, setting default structure");
                                        $requirements = ['fields' => []];
                                    }
                                    
                                    // Extract field names from requirements
                                    if (isset($requirements['fields']) && is_array($requirements['fields'])) {
                                        foreach ($requirements['fields'] as $field) {
                                            if (isset($field['name'])) {
                                                $validFieldNames[] = $field['name'];
                                            }
                                        }
                                    }
                                } catch (Exception $e) {
                                    error_log("Error processing requirements for field names: " . $e->getMessage());
                                    $validFieldNames = [];
                                }
                            }
                        }

                        // Filter out empty or null values from each note record
                        // And try to parse the note content to check if it's relevant to this request type
                        $notes = [];
                        foreach ($rawNotes as $note) {
                            try {
                                // Try to parse the note content if it's JSON
                                $noteContent = isset($note['note']) ? $note['note'] : '';
                                if (empty($noteContent)) {
                                    continue;
                                }
                                
                                $isRelevant = true; // Assume all notes are relevant by default
                                $parsedContent = null;
                                
                                if (is_string($noteContent) && substr($noteContent, 0, 1) === '{') {
                                    $parsedNote = json_decode($noteContent, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsedNote)) {
                                        // For course modules, include all notes
                                        if (stripos($result['request_type'], 'Course Module') !== false) {
                                            $parsedContent = $parsedNote;
                                        } else {
                                            // Check if the note contains fields that are part of this request type
                                            $relevantFields = [];
                                            
                                            foreach ($parsedNote as $key => $value) {
                                                // Include if it's a valid field name or a special field like 'purpose'
                                                if (empty($validFieldNames) || in_array($key, $validFieldNames) || $key === 'purpose') {
                                                    $relevantFields[$key] = $value;
                                                }
                                            }
                                            
                                            if (!empty($relevantFields)) {
                                                $parsedContent = $relevantFields;
                                            }
                                        }
                                    }
                                }
                                
                                // Create the clean note object
                                $cleanNote = [
                                    'note_id' => $note['note_id'],
                                    'request_id' => $note['request_id'],
                                    'admin_id' => $note['admin_id'],
                                    'created_at' => $note['created_at']
                                ];
                                
                                // Add either parsed content or original note
                                if ($parsedContent !== null) {
                                    $cleanNote['content'] = $parsedContent;
                                } else {
                                    $cleanNote['note'] = $noteContent;
                                }
                                
                                $notes[] = $cleanNote;
                            } catch (Exception $e) {
                                error_log("Error processing note: " . $e->getMessage());
                                continue;
                            }
                        }
                    } else {
                        error_log("request_notes table does not exist");
                        $notes = [];
                    }
                } catch (Exception $e) {
                    error_log("Error processing notes: " . $e->getMessage());
                    // Continue execution even if there's an error with notes
                    $notes = [];
                }
                
                // Create a clean array with only the necessary fields
                $rawRequest = [
                    'id' => $result['tracking_number'],
                    'request_id' => $result['request_id'],
                    'user_id' => $result['user_id'],
                    'type_id' => $result['type_id'],
                    'purpose' => $result['purpose'],
                    'status' => $result['status'],
                    'submitted_at' => $result['submitted_at'],
                    'updated_at' => $result['updated_at'],
                    'request_type' => $result['request_type'],
                    'first_name' => $result['first_name'],
                    'last_name' => $result['last_name'],
                    'category_name' => $result['category_name'] ?? 'General',
                    'requirement_status' => isset($requirementStatus) ? $requirementStatus : [],
                    'notes' => isset($notes) ? $notes : []
                ];
                
                // Filter out empty or null values from the main request object
                $request = [];
                foreach ($rawRequest as $key => $value) {
                    if ($value !== null && $value !== '' && $value !== []) {
                        $request[$key] = $value;
                    }
                }
                
                try {
                    // Get request type details
                    error_log("Fetching request type details for type_id: " . $result['type_id']);
                    $requestType = $this->getRequestType($result['type_id']);
                    
                    // Filter out empty or null values from the request type
                    if ($requestType) {
                        $cleanRequestType = [];
                        foreach ($requestType as $key => $value) {
                            if ($value !== null && $value !== '') {
                                $cleanRequestType[$key] = $value;
                            }
                        }
                        $request['type'] = $cleanRequestType;
                    }
                } catch (Exception $e) {
                    error_log("Error fetching request type details: " . $e->getMessage());
                }
                
                return $request;
            } else {
                error_log("No request found with tracking number: $tracking_number for user: $user_id");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Database error in getDetailsByTrackingNumber: " . $e->getMessage());
            throw new Exception("Failed to fetch request details: " . $e->getMessage(), 500);
        }
    }

    public function getRequestDetailsWithType($tracking_number, $user_id, $include_type_details = false) {
        try {
            error_log("Getting request details with type focus for tracking number: $tracking_number and user: $user_id");
            
            // First check if the request_id column exists
            $columnsQuery = "SHOW COLUMNS FROM " . $this->table . " LIKE 'request_id'";
            $columnsStmt = $this->conn->prepare($columnsQuery);
            $columnsStmt->execute();
            $requestIdExists = $columnsStmt->rowCount() > 0;
            
            // Check if type_id column exists in request_types
            $typeIdColumnsQuery = "SHOW COLUMNS FROM request_types LIKE 'type_id'";
            $typeIdColumnsStmt = $this->conn->prepare($typeIdColumnsQuery);
            $typeIdColumnsStmt->execute();
            $typeIdExists = $typeIdColumnsStmt->rowCount() > 0;
            
            // Build the query based on the table structure
            if (!$requestIdExists) {
                // If request_id doesn't exist, use id instead
                error_log("request_id column does not exist, using id instead");
                
                if (!$typeIdExists) {
                    // If type_id doesn't exist in request_types, use id instead
                    $query = "SELECT 
                                r.id as request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                u.first_name,
                                u.last_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                } else {
                    $query = "SELECT 
                                r.id as request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                u.first_name,
                                u.last_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.type_id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                }
            } else {
                // Use request_id as normal
                if (!$typeIdExists) {
                    // If type_id doesn't exist in request_types, use id instead
                    $query = "SELECT 
                                r.request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                u.first_name,
                                u.last_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                } else {
                    $query = "SELECT 
                                r.request_id,
                                r.tracking_number,
                                r.user_id,
                                r.type_id,
                                r.purpose,
                                r.status,
                                r.submitted_at,
                                r.updated_at,
                                rt.name as request_type,
                                u.first_name,
                                u.last_name
                            FROM 
                                " . $this->table . " r
                                LEFT JOIN request_types rt ON r.type_id = rt.type_id
                                LEFT JOIN users u ON r.user_id = u.user_id
                            WHERE 
                                r.tracking_number = ? AND r.user_id = ?";
                }
            }
            
            error_log("Executing query with tracking_number=$tracking_number and user_id=$user_id");
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $tracking_number);
            $stmt->bindParam(2, $user_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($result) {
                error_log("Found request with tracking number: $tracking_number. Request ID: " . $result['request_id']);
                
                // Create a clean array with only the necessary fields
                $request = [
                    'id' => $result['tracking_number'],
                    'request_id' => $result['request_id'],
                    'user_id' => $result['user_id'],
                    'type_id' => $result['type_id'],
                    'purpose' => $result['purpose'],
                    'status' => $result['status'],
                    'submitted_at' => $result['submitted_at'],
                    'updated_at' => $result['updated_at'],
                    'request_type' => $result['request_type'],
                    'first_name' => $result['first_name'],
                    'last_name' => $result['last_name']
                ];
                
                // Filter out empty or null values
                foreach ($request as $key => $value) {
                    if ($value === null || $value === '') {
                        unset($request[$key]);
                    }
                }
                
                // Get request type details with a focus on requirements only if needed
                if ($include_type_details) {
                    try {
                        error_log("Fetching request type details for type_id: " . $result['type_id']);
                        $requestType = $this->getRequestType($result['type_id']);
                        
                        if ($requestType) {
                            // Get only the relevant fields for this request type
                            $cleanRequestType = [
                                'type_id' => $requestType['type_id'],
                                'name' => $requestType['name'],
                                'processing_time' => $requestType['processing_time'] ?? null,
                                'category_name' => $requestType['category_name'] ?? 'General'
                            ];
                            
                            // Add requirements if they exist
                            if (!empty($requestType['requirements'])) {
                                $cleanRequestType['requirements'] = $requestType['requirements'];
                            }
                            
                            // Filter out empty or null values
                            foreach ($cleanRequestType as $key => $value) {
                                if ($value === null || $value === '') {
                                    unset($cleanRequestType[$key]);
                                }
                            }
                            
                            $request['type'] = $cleanRequestType;
                        }
                    } catch (Exception $e) {
                        error_log("Error fetching request type details: " . $e->getMessage());
                    }
                }
                
                // Get requirement status
                try {
                    // Check if request_files table exists
                    $tableExistsQuery = "SHOW TABLES LIKE 'request_files'";
                    $tableExistsStmt = $this->conn->prepare($tableExistsQuery);
                    $tableExistsStmt->execute();
                    $tableExists = $tableExistsStmt->rowCount() > 0;
                    
                    if ($tableExists) {
                        $filesQuery = "SELECT 
                                        file_id,
                                        request_id,
                                        field_name,
                                        original_name,
                                        file_name,
                                        file_path,
                                        file_type,
                                        file_size,
                                        uploaded_at
                                      FROM 
                                        request_files
                                      WHERE 
                                        request_id = ?";
                        
                        error_log("Fetching files for request_id: " . $result['request_id']);
                        $filesStmt = $this->conn->prepare($filesQuery);
                        $filesStmt->bindParam(1, $result['request_id']);
                        $filesStmt->execute();
                        $rawFiles = $filesStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Found " . count($rawFiles) . " files for this request");
                        
                        // Get the list of valid field names for this request type
                        $validFieldNames = [];
                        
                        // Get the request type requirements to determine valid field names
                        $requestType = $this->getRequestType($result['type_id']);
                        if (!empty($requestType['requirements']) && 
                            is_array($requestType['requirements']) && 
                            isset($requestType['requirements']['fields'])) {
                            foreach ($requestType['requirements']['fields'] as $field) {
                                $validFieldNames[] = $field['name'];
                            }
                        }
                        
                        // Create requirement status array
                        $requirementStatus = [];
                        
                        // Get the fields from the request type
                        $fields = !empty($requestType['requirements']) && 
                                 is_array($requestType['requirements']) && 
                                 isset($requestType['requirements']['fields']) ? 
                                 $requestType['requirements']['fields'] : [];
                        
                        foreach ($fields as $field) {
                            // Only process file type fields
                            if ($field['type'] === 'file') {
                                $fieldName = $field['name'];
                                
                                // Get files for this field
                                $fieldFiles = array_filter($rawFiles, function($file) use ($fieldName) {
                                    return $file['field_name'] === $fieldName;
                                });
                                
                                // Create status object
                                $status = [
                                    'requirement_name' => $fieldName,
                                    'label' => $field['label'],
                                    'required' => $field['required'] ?? false,
                                    'uploaded' => !empty($fieldFiles)
                                ];
                                
                                // Add files if they exist
                                if (!empty($fieldFiles)) {
                                    $status['files'] = array_values($fieldFiles);
                                }
                                
                                $requirementStatus[] = $status;
                            }
                        }
                        
                        if (!empty($requirementStatus)) {
                            $request['requirement_status'] = $requirementStatus;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error processing requirement status: " . $e->getMessage());
                }
                
                // Get notes if they exist
                try {
                    // Check if request_notes table exists
                    $tableExistsQuery = "SHOW TABLES LIKE 'request_notes'";
                    $tableExistsStmt = $this->conn->prepare($tableExistsQuery);
                    $tableExistsStmt->execute();
                    $tableExists = $tableExistsStmt->rowCount() > 0;
                    
                    if ($tableExists) {
                        $notesQuery = "SELECT 
                                        note_id,
                                        request_id,
                                        user_id as admin_id,
                                        note,
                                        created_at
                                      FROM 
                                        request_notes
                                      WHERE 
                                        request_id = ?";
                        
                        error_log("Fetching notes for request_id: " . $result['request_id']);
                        $notesStmt = $this->conn->prepare($notesQuery);
                        $notesStmt->bindParam(1, $result['request_id']);
                        $notesStmt->execute();
                        $rawNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Found " . count($rawNotes) . " notes for this request");
                        
                        // Get the valid field names for this request type
                        $validFieldNames = [];
                        
                        // Get the request type requirements to determine valid field names
                        $requestType = $this->getRequestType($result['type_id']);
                        if (!empty($requestType['requirements']) && 
                            is_array($requestType['requirements']) && 
                            isset($requestType['requirements']['fields'])) {
                            foreach ($requestType['requirements']['fields'] as $field) {
                                $validFieldNames[] = $field['name'];
                            }
                        }
                        
                        // Filter and process notes
                        $notes = [];
                        try {
                            if (!empty($result['notes'])) {
                                foreach ($result['notes'] as $noteContent) {
                                    try {
                                        // Skip empty notes
                                        if (empty($noteContent)) {
                                            continue;
                                        }

                                        // If noteContent is already an array, use it directly
                                        if (is_array($noteContent)) {
                                            $note = $noteContent;
                                        } else {
                                            // Try to decode if it's a JSON string
                                            $note = json_decode($noteContent, true);
                                            if (json_last_error() !== JSON_ERROR_NONE) {
                                                error_log("Failed to decode note JSON: " . json_last_error_msg());
                                                continue;
                                            }
                                        }

                                        // Validate note structure
                                        if (!is_array($note) || !isset($note['content']) || !isset($note['timestamp'])) {
                                            error_log("Invalid note structure: " . print_r($note, true));
                                            continue;
                                        }

                                        // Clean the note data
                                        $cleanNote = [
                                            'content' => strip_tags($note['content']),
                                            'timestamp' => $note['timestamp']
                                        ];

                                        // Only add optional fields if they exist and are not empty
                                        if (isset($note['user']) && !empty($note['user'])) {
                                            $cleanNote['user'] = strip_tags($note['user']);
                                        }
                                        if (isset($note['type']) && !empty($note['type'])) {
                                            $cleanNote['type'] = strip_tags($note['type']);
                                        }

                                        $notes[] = $cleanNote;
                                    } catch (Exception $e) {
                                        error_log("Error processing individual note: " . $e->getMessage());
                                        continue;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error processing notes: " . $e->getMessage());
                        }

                        // Always ensure we have a valid notes array
                        $result['notes'] = $notes;
                    }
                } catch (Exception $e) {
                    error_log("Error processing notes: " . $e->getMessage());
                    // Continue execution even if there's an error with notes
                    $notes = [];
                }
                
                return $request;
            } else {
                error_log("No request found with tracking number: $tracking_number for user: $user_id");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Database error in getRequestDetailsWithType: " . $e->getMessage());
            throw new Exception("Failed to fetch request details: " . $e->getMessage(), 500);
        }
    }

    public function cancel($request_id, $user_id) {
        try {
            // First verify the request belongs to the user and is in a cancellable state
            $query = "SELECT status FROM " . $this->table . " 
                     WHERE request_id = :request_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception("Request not found or unauthorized", 404);
            }
            
            // Only allow cancellation of pending requests
            if ($request['status'] !== 'PENDING') {
                throw new Exception("Only pending requests can be cancelled", 400);
            }
            
            // Update the request status to cancelled
            $query = "UPDATE " . $this->table . " 
                     SET status = 'CANCELLED', updated_at = NOW() 
                     WHERE request_id = :request_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                return $this->getById($request_id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to cancel request", 500);
        }
    }
} 