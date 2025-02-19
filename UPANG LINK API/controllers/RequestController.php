<?php

class RequestController {
    private $db;
    private $user;
    private $fileHandler;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
        $this->fileHandler = new FileHandler();
    }

    public function handleRequest($method, $uri) {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
            return;
        }

        switch ($method) {
            case 'GET':
                if (count($uri) === 1) {
                    // GET /requests - List requests
                    $this->getRequests($userId);
                } elseif (count($uri) === 2) {
                    // GET /requests/{id} - Get request details
                    $this->getRequest($uri[1], $userId);
                } elseif ($uri[1] === 'types' && count($uri) === 2) {
                    // GET /requests/types - Get request types
                    $this->getRequestTypes();
                } elseif ($uri[1] === 'requirements' && count($uri) === 3) {
                    // GET /requests/requirements/{typeId} - Get requirements
                    $this->getRequirements($uri[2]);
                } elseif ($uri[1] === 'statistics' && count($uri) === 2) {
                    // GET /requests/statistics - Get statistics
                    $this->getStatistics($userId);
                } else {
                    throw new Exception('Invalid endpoint', 404);
                }
                break;

            case 'POST':
                if (count($uri) === 1) {
                    // POST /requests - Create request
                    $this->createRequest($userId);
                } elseif (count($uri) === 4 && $uri[2] === 'requirements') {
                    // POST /requests/{requestId}/requirements/{requirementId} - Upload requirement
                    $this->uploadRequirement($uri[1], $uri[3], $userId);
                } elseif (count($uri) === 3 && $uri[2] === 'cancel') {
                    // POST /requests/{id}/cancel - Cancel request
                    $this->cancelRequest($uri[1], $userId);
                } else {
                    throw new Exception('Invalid endpoint', 404);
                }
                break;

            case 'DELETE':
                if (count($uri) === 4 && $uri[2] === 'requirements') {
                    // DELETE /requests/{requestId}/requirements/{requirementId} - Delete requirement
                    $this->deleteRequirement($uri[1], $uri[3], $userId);
                } else {
                    throw new Exception('Invalid endpoint', 404);
                }
                break;

            default:
                throw new Exception('Method not allowed', 405);
        }
    }

    private function getRequests($userId) {
        try {
            $query = "SELECT r.*, rt.name as type_name, rt.description as type_description, 
                     rt.processing_time, rt.fee 
                     FROM requests r 
                     JOIN request_types rt ON r.type_id = rt.id 
                     WHERE r.student_id = :student_id 
                     ORDER BY r.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();

            $requests = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $request = $this->formatRequest($row);
                $request['requirements'] = $this->getRequestRequirements($row['id']);
                $requests[] = $request;
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Requests retrieved successfully',
                'data' => $requests
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to get requests: ' . $e->getMessage());
        }
    }

    private function getRequest($requestId, $userId) {
        try {
            $query = "SELECT r.*, rt.name as type_name, rt.description as type_description, 
                     rt.processing_time, rt.fee 
                     FROM requests r 
                     JOIN request_types rt ON r.type_id = rt.id 
                     WHERE r.id = :id AND r.student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $requestId);
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $request = $this->formatRequest($row);
                $request['requirements'] = $this->getRequestRequirements($requestId);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Request retrieved successfully',
                    'data' => $request
                ]);
            } else {
                throw new Exception('Request not found', 404);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to get request: ' . $e->getMessage());
        }
    }

    private function getRequestTypes() {
        try {
            $query = "SELECT * FROM request_types WHERE is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $types = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $type = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'processing_time' => $row['processing_time'],
                    'fee' => (float)$row['fee'],
                    'requirements' => $this->getTypeRequirements($row['id'])
                ];
                $types[] = $type;
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Request types retrieved successfully',
                'data' => $types
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to get request types: ' . $e->getMessage());
        }
    }

    private function getRequirements($typeId) {
        try {
            $requirements = $this->getTypeRequirements($typeId);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Requirements retrieved successfully',
                'data' => $requirements
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to get requirements: ' . $e->getMessage());
        }
    }

    private function createRequest($userId) {
        try {
            $this->db->beginTransaction();

            // Validate input
            $typeId = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
            $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING);

            if (!$typeId || !$purpose) {
                throw new Exception('Invalid input', 400);
            }

            // Create request
            $query = "INSERT INTO requests (id, student_id, type_id, purpose, status, created_at, updated_at) 
                     VALUES (UUID(), :student_id, :type_id, :purpose, 'PENDING', NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $userId);
            $stmt->bindParam(':type_id', $typeId);
            $stmt->bindParam(':purpose', $purpose);
            $stmt->execute();

            $requestId = $this->db->lastInsertId();

            // Handle file uploads
            if (isset($_FILES['files'])) {
                foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
                    $file = [
                        'name' => $_FILES['files']['name'][$index],
                        'type' => $_FILES['files']['type'][$index],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['files']['error'][$index],
                        'size' => $_FILES['files']['size'][$index]
                    ];

                    $fileUrl = $this->fileHandler->uploadFile($file, 'requirements');
                    
                    // Save file submission
                    $this->saveRequirementSubmission($requestId, $file['name'], $fileUrl);
                }
            }

            $this->db->commit();

            // Get the created request
            $this->getRequest($requestId, $userId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to create request: ' . $e->getMessage());
        }
    }

    private function uploadRequirement($requestId, $requirementId, $userId) {
        try {
            // Verify request ownership
            $this->verifyRequestOwnership($requestId, $userId);

            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded', 400);
            }

            $file = $_FILES['file'];
            $fileUrl = $this->fileHandler->uploadFile($file, 'requirements');
            
            // Save or update requirement submission
            $query = "INSERT INTO requirement_submissions (request_id, requirement_id, file_url, status, submitted_at) 
                     VALUES (:request_id, :requirement_id, :file_url, 'SUBMITTED', NOW())
                     ON DUPLICATE KEY UPDATE 
                     file_url = VALUES(file_url),
                     status = VALUES(status),
                     submitted_at = VALUES(submitted_at)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':request_id', $requestId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':file_url', $fileUrl);
            $stmt->execute();

            echo json_encode([
                'status' => 'success',
                'message' => 'Requirement uploaded successfully'
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to upload requirement: ' . $e->getMessage());
        }
    }

    private function deleteRequirement($requestId, $requirementId, $userId) {
        try {
            // Verify request ownership
            $this->verifyRequestOwnership($requestId, $userId);

            // Get the file URL
            $query = "SELECT file_url FROM requirement_submissions 
                     WHERE request_id = :request_id AND requirement_id = :requirement_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':request_id', $requestId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->execute();

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Delete the file
                $this->fileHandler->deleteFile($row['file_url']);

                // Delete the submission
                $query = "DELETE FROM requirement_submissions 
                         WHERE request_id = :request_id AND requirement_id = :requirement_id";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':request_id', $requestId);
                $stmt->bindParam(':requirement_id', $requirementId);
                $stmt->execute();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Requirement deleted successfully'
                ]);
            } else {
                throw new Exception('Requirement not found', 404);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to delete requirement: ' . $e->getMessage());
        }
    }

    private function cancelRequest($requestId, $userId) {
        try {
            // Verify request ownership
            $this->verifyRequestOwnership($requestId, $userId);

            // Update request status
            $query = "UPDATE requests SET status = 'CANCELLED', updated_at = NOW() 
                     WHERE id = :id AND student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $requestId);
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Request cancelled successfully'
                ]);
            } else {
                throw new Exception('Request not found', 404);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to cancel request: ' . $e->getMessage());
        }
    }

    private function getStatistics($userId) {
        try {
            // Get total counts
            $query = "SELECT 
                     COUNT(*) as total,
                     SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status IN ('IN_REVIEW', 'PROCESSING') THEN 1 ELSE 0 END) as in_progress,
                     SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
                     SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled
                     FROM requests 
                     WHERE student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get counts by type
            $query = "SELECT rt.name, COUNT(*) as count 
                     FROM requests r 
                     JOIN request_types rt ON r.type_id = rt.id 
                     WHERE r.student_id = :student_id 
                     GROUP BY rt.id, rt.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();
            
            $byType = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $byType[$row['name']] = (int)$row['count'];
            }

            // Get counts by month
            $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                     FROM requests 
                     WHERE student_id = :student_id 
                     GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                     ORDER BY month DESC 
                     LIMIT 12";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();
            
            $byMonth = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $byMonth[$row['month']] = (int)$row['count'];
            }

            $statistics = [
                'total' => (int)$counts['total'],
                'pending' => (int)$counts['pending'],
                'in_progress' => (int)$counts['in_progress'],
                'completed' => (int)$counts['completed'],
                'cancelled' => (int)$counts['cancelled'],
                'by_type' => $byType,
                'by_month' => $byMonth
            ];

            echo json_encode([
                'status' => 'success',
                'message' => 'Statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to get statistics: ' . $e->getMessage());
        }
    }

    private function getTypeRequirements($typeId) {
        $query = "SELECT * FROM requirements WHERE type_id = :type_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':type_id', $typeId);
        $stmt->execute();

        $requirements = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $requirements[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'is_required' => (bool)$row['is_required'],
                'allowed_file_types' => json_decode($row['allowed_file_types']),
                'max_file_size' => (int)$row['max_file_size']
            ];
        }
        return $requirements;
    }

    private function getRequestRequirements($requestId) {
        $query = "SELECT r.*, rs.file_url, rs.status, rs.remarks, rs.submitted_at, rs.verified_at 
                 FROM requirements r 
                 LEFT JOIN requirement_submissions rs ON r.id = rs.requirement_id AND rs.request_id = :request_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':request_id', $requestId);
        $stmt->execute();

        $requirements = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $requirements[] = [
                'id' => $row['id'],
                'requirement_id' => $row['id'],
                'requirement' => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_required' => (bool)$row['is_required'],
                    'allowed_file_types' => json_decode($row['allowed_file_types']),
                    'max_file_size' => (int)$row['max_file_size']
                ],
                'file_url' => $row['file_url'],
                'status' => $row['status'],
                'remarks' => $row['remarks'],
                'submitted_at' => $row['submitted_at'],
                'verified_at' => $row['verified_at']
            ];
        }
        return $requirements;
    }

    private function formatRequest($row) {
        return [
            'id' => $row['id'],
            'student_id' => $row['student_id'],
            'type_id' => (int)$row['type_id'],
            'type' => [
                'id' => (int)$row['type_id'],
                'name' => $row['type_name'],
                'description' => $row['type_description'],
                'processing_time' => $row['processing_time'],
                'fee' => (float)$row['fee']
            ],
            'status' => $row['status'],
            'purpose' => $row['purpose'],
            'remarks' => $row['remarks'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    private function verifyRequestOwnership($requestId, $userId) {
        $query = "SELECT id FROM requests WHERE id = :id AND student_id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $requestId);
        $stmt->bindParam(':student_id', $userId);
        $stmt->execute();

        if (!$stmt->fetch()) {
            throw new Exception('Request not found or unauthorized', 404);
        }
    }

    private function saveRequirementSubmission($requestId, $fileName, $fileUrl) {
        $query = "INSERT INTO requirement_submissions (request_id, requirement_id, file_url, status, submitted_at) 
                 VALUES (:request_id, :requirement_id, :file_url, 'SUBMITTED', NOW())";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':request_id', $requestId);
        $stmt->bindParam(':requirement_id', $requirementId);
        $stmt->bindParam(':file_url', $fileUrl);
        $stmt->execute();
    }
} 