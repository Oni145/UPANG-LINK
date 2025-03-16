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
                
                // Parse requirements JSON if it exists
                if(!empty($result['requirements'])) {
                    error_log("Requirements before parsing: " . $result['requirements']);
                    $requirements = json_decode($result['requirements'], true);
                    if(json_last_error() === JSON_ERROR_NONE) {
                        $result['requirements'] = $requirements;
                        error_log("Requirements parsed successfully");
                    } else {
                        error_log("Error parsing requirements JSON: " . json_last_error_msg());
                    }
                } else {
                    error_log("No requirements found for this request type");
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
                        if (!empty($result['requirements']) && is_array($result['requirements']) && isset($result['requirements']['fields'])) {
                            foreach ($result['requirements']['fields'] as $field) {
                                $validFieldNames[] = $field['name'];
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
                    if (!empty($result['requirements']) && is_array($result['requirements']) && isset($result['requirements']['fields'])) {
                        error_log("Processing requirement fields for status check");
                        foreach ($result['requirements']['fields'] as $field) {
                            // Only process file type fields that are required and match the current request type
                            if ($field['type'] === 'file' && $field['required']) {
                                $fieldName = $field['name'];
                                
                                // Get files for this field
                                $fieldFiles = isset($organizedFiles[$fieldName]) ? $organizedFiles[$fieldName] : [];
                                
                                // Create status object with only non-empty fields
                                $status = [
                                    'name' => $fieldName,
                                    'label' => $field['label'],
                                    'required' => $field['required'],
                                    'uploaded' => !empty($fieldFiles)
                                ];
                                
                                // Only add files if they exist
                                if (!empty($fieldFiles)) {
                                    $status['files'] = $fieldFiles;
                                }
                                
                                $requirementStatus[$fieldName] = $status;
                            }
                        }
                    } else {
                        error_log("No requirement fields found to process for status check");
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
                            foreach ($requirements as $requirement) {
                                if (isset($requirement['field_name'])) {
                                    $validFieldNames[] = $requirement['field_name'];
                                }
                            }
                        }

                        // Filter requirement_status to only include requirements relevant to this request type
                        if (!empty($requirementStatus) && !empty($validFieldNames)) {
                            $filteredRequirementStatus = [];
                            foreach ($requirementStatus as $requirement) {
                                if (isset($requirement['requirement_name']) && 
                                    in_array($requirement['requirement_name'], $validFieldNames)) {
                                    $filteredRequirementStatus[] = $requirement;
                                }
                            }
                            $requirementStatus = $filteredRequirementStatus;
                        }

                        // Filter out empty or null values from each note record
                        // And try to parse the note content to check if it's relevant to this request type
                        $notes = [];
                        foreach ($rawNotes as $note) {
                            // Try to parse the note content if it's JSON
                            $noteContent = $note['note'];
                            $isRelevant = true; // Assume all notes are relevant by default
                            $parsedContent = null;
                            
                            if ($noteContent && substr($noteContent, 0, 1) === '{') {
                                $parsedNote = json_decode($noteContent, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    // Check if the note contains fields that are part of this request type
                                    $hasRelevantField = false;
                                    $relevantFields = [];
                                    
                                    if (!empty($validFieldNames)) {
                                        foreach ($parsedNote as $key => $value) {
                                            if (in_array($key, $validFieldNames) || $key === 'purpose') {
                                                $hasRelevantField = true;
                                                $relevantFields[$key] = $value;
                                            }
                                        }
                                        $isRelevant = $hasRelevantField;
                                        if ($isRelevant) {
                                            $parsedContent = $relevantFields;
                                        }
                                    }
                                }
                            }
                            
                            // Only include relevant notes
                            if ($isRelevant) {
                                $cleanNote = [
                                    'note_id' => $note['note_id'],
                                    'request_id' => $note['request_id'],
                                    'admin_id' => $note['admin_id'],
                                    'created_at' => $note['created_at']
                                ];
                                
                                // If we successfully parsed the content, include the parsed fields
                                // Otherwise include the original note content
                                if ($parsedContent) {
                                    $cleanNote['content'] = $parsedContent;
                                } else {
                                    $cleanNote['note'] = $noteContent;
                                }
                                
                                $notes[] = $cleanNote;
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
                        foreach ($rawNotes as $note) {
                            // Try to parse the note content if it's JSON
                            $noteContent = $note['note'];
                            $isRelevant = true; // Assume all notes are relevant by default
                            $parsedContent = null;
                            
                            if ($noteContent && substr($noteContent, 0, 1) === '{') {
                                $parsedNote = json_decode($noteContent, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    // Check if the note contains fields that are part of this request type
                                    $hasRelevantField = false;
                                    $relevantFields = [];
                                    
                                    if (!empty($validFieldNames)) {
                                        foreach ($parsedNote as $key => $value) {
                                            if (in_array($key, $validFieldNames) || $key === 'purpose') {
                                                $hasRelevantField = true;
                                                $relevantFields[$key] = $value;
                                            }
                                        }
                                        $isRelevant = $hasRelevantField;
                                        if ($isRelevant) {
                                            $parsedContent = $relevantFields;
                                        }
                                    }
                                }
                            }
                            
                            // Only include relevant notes
                            if ($isRelevant) {
                                $cleanNote = [
                                    'note_id' => $note['note_id'],
                                    'request_id' => $note['request_id'],
                                    'admin_id' => $note['admin_id'],
                                    'created_at' => $note['created_at']
                                ];
                                
                                // If we successfully parsed the content, include the parsed fields
                                // Otherwise include the original note content
                                if ($parsedContent) {
                                    $cleanNote['content'] = $parsedContent;
                                } else {
                                    $cleanNote['note'] = $noteContent;
                                }
                                
                                $notes[] = $cleanNote;
                            }
                        }
                        
                        if (!empty($notes)) {
                            $request['notes'] = $notes;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error processing notes: " . $e->getMessage());
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
} 