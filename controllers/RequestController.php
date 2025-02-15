<?php
require_once '../controllers/AuthController.php';
require_once '../models/FormGenerator.php';
require_once '../models/RequestType.php';
require_once '../models/RequiredDocument.php';
require_once '../models/RequirementNote.php';
require_once '../models/RequirementTemplate.php';

class RequestController {
    private $db;
    private $request;

    public function __construct($db) {
        $this->db = $db;
        $this->request = new Request($db);
    }
    
    /**
     * Authenticate the incoming request.
     * Expects the header "Authorization: Bearer YOUR_VALID_TOKEN".
     * First checks the admin_tokens table, then checks the auth_tokens table.
     * Applies sliding expiration for the token found.
     */
    private function authenticate() {
        // Retrieve headers (support both apache_request_headers() and getallheaders())
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : getallheaders();
        $authHeader = '';
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            $this->sendError("Access Denied: No token provided", 401);
            exit;
        }
        
        // Extract token using regex (case-insensitive)
        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $this->sendError("Access Denied: Invalid token format", 401);
            exit;
        }
        
        // --- First, check admin_tokens table ---
        $stmtAdmin = $this->db->prepare("SELECT admin_id, expires_at FROM admin_tokens WHERE token = ?");
        $stmtAdmin->execute([$token]);
        $adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if ($adminRow) {
            $currentTime = new DateTime();
            $expiresAt = new DateTime($adminRow['expires_at']);
            if ($currentTime > $expiresAt) {
                $delStmt = $this->db->prepare("DELETE FROM admin_tokens WHERE token = ?");
                $delStmt->execute([$token]);
                $this->sendError("Access Denied: Admin token expired", 401);
                exit;
            }
            // Sliding expiration for admin token: update expires_at to 24 hours from now
            $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
            $updateStmt = $this->db->prepare("UPDATE admin_tokens SET expires_at = ? WHERE token = ?");
            $updateStmt->execute([$newExpiresAt, $token]);
            return; // Authenticated as admin.
        }
        
        // --- Next, check auth_tokens table for student token ---
        $stmtStudent = $this->db->prepare("SELECT user_id, expires_at FROM auth_tokens WHERE token = ?");
        $stmtStudent->execute([$token]);
        $studentRow = $stmtStudent->fetch(PDO::FETCH_ASSOC);
        if ($studentRow) {
            $currentTime = new DateTime();
            $expiresAt = new DateTime($studentRow['expires_at']);
            if ($currentTime > $expiresAt) {
                $delStmt = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
                $delStmt->execute([$token]);
                $this->sendError("Access Denied: Student token expired", 401);
                exit;
            }
            // Sliding expiration for student token: update expires_at to 24 hours from now
            $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
            $updateStmt = $this->db->prepare("UPDATE auth_tokens SET expires_at = ? WHERE token = ?");
            $updateStmt->execute([$newExpiresAt, $token]);
            return; // Authenticated as student.
        }
        
        // If token is not found in either table:
        $this->sendError("Access Denied: Invalid or expired token", 401);
        exit;
    }
    
    public function handleRequest($method, $uri) {
        // Authenticate before processing any request
        $this->authenticate();
        
        switch ($method) {
            case 'GET':
                if (isset($uri[1])) {
                    if ($uri[1] === 'user' && isset($uri[2])) {
                        $this->getRequestsByUser($uri[2]);
                    } else if ($uri[1] === 'status' && isset($uri[2])) {
                        $this->getRequestsByStatus($uri[2]);
                    } else if ($uri[1] === 'notes' && isset($uri[2])) {
                        $this->getRequestNotes($uri[2]);
                    } else if ($uri[1] === 'form' && isset($uri[2])) {
                        $this->getRequestForm($uri[2]);
                    } else {
                        $this->getRequest($uri[1]);
                    }
                } else {
                    $this->getAllRequests();
                }
                break;
            
            case 'POST':
                if (isset($uri[1]) && $uri[1] === 'notes') {
                    $this->addRequirementNote();
                } else {
                    $this->createRequest();
                }
                break;
            
            case 'PUT':
                if (isset($uri[1])) {
                    $this->updateRequest($uri[1]);
                } else {
                    $this->sendError('Request ID required');
                }
                break;
            
            case 'DELETE':
                if (isset($uri[1])) {
                    $this->deleteRequest($uri[1]);
                } else {
                    $this->sendError('Request ID required');
                }
                break;
            
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function getAllRequests() {
        $stmt = $this->request->read();
        $num = $stmt->rowCount();
        if ($num > 0) {
            $requests_arr = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $requests_arr[] = $row;
            }
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $requests_arr
            ]);
        } else {
            $this->sendError('No requests found', 404);
        }
    }
    
    private function getRequest($id) {
        $this->request->request_id = $id;
        $result = $this->request->readOne();
        if ($result) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $result
            ]);
        } else {
            $this->sendError('Request not found', 404);
        }
    }
    
    private function createRequest() {
        $data = json_decode(file_get_contents("php://input"));
        if (!$data) {
            $data = (object) $_POST;
        }
        
        // Check for missing required text fields
        $missing = $this->checkMissingFields($data, ['user_id', 'type_id', 'purpose']);
        if (!empty($missing)) {
            $this->sendError('Missing parameters: ' . implode(', ', $missing));
            return;
        }
        
        // Check for missing required file fields
        $missingFiles = $this->checkMissingFiles(['clearance_form', 'request_letter']);
        if (!empty($missingFiles)) {
            $this->sendError('Missing file(s): ' . implode(', ', $missingFiles));
            return;
        }
        
        if (!class_exists('FormGenerator')) {
            $this->sendError("Required class 'FormGenerator' is missing.", 500);
            return;
        }
        
        $formGenerator = new FormGenerator($this->db);
        $validation = $formGenerator->validateSubmission($data->type_id, (array)$data, $_FILES);
        
        if ($validation === false) {
            $this->sendError('Validation failed: Submission is invalid. Please check your input data.', 400);
            return;
        }
        
        if (!is_array($validation)) {
            $this->sendError('Validation failed: Unexpected response from validation function', 400);
            return;
        }
        
        if (!empty($validation['is_valid'])) {
            $this->request->user_id = $data->user_id;
            $this->request->type_id = $data->type_id;
            $this->request->status = "pending";
            
            $files = [];
            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $files[$key] = $file;
                    }
                }
            }
            
            if ($this->request->createWithRequirements($files)) {
                $response = [
                    'status' => 'success',
                    'message' => 'Request created successfully',
                    'request_id' => $this->request->request_id
                ];
                if (!empty($validation['warnings'])) {
                    $response['warnings'] = $validation['warnings'];
                }
                http_response_code(201);
                echo json_encode($response);
            } else {
                $this->sendError('Unable to create request');
            }
        } else {
            $errors = isset($validation['errors']) ? $validation['errors'] : 'Unknown validation error';
            $this->sendError('Validation failed', 400, $errors);
        }
    }
    
    private function updateRequest($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (!$data) {
            $data = (object) $_POST;
        }
        $missing = $this->checkMissingFields($data, ['status']);
        if (!empty($missing)) {
            $this->sendError('Missing parameter: ' . implode(', ', $missing));
            return;
        }
        if (!empty($data->status)) {
            $this->request->request_id = $id;
            $this->request->status = $data->status;
            if ($this->request->update()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Request status updated successfully'
                ]);
            } else {
                $this->sendError('Unable to update request');
            }
        } else {
            $this->sendError('Status is required');
        }
    }
    
    private function deleteRequest($id) {
        $this->request->request_id = $id;
        if ($this->request->delete()) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Request deleted successfully'
            ]);
        } else {
            $this->sendError('Unable to delete request');
        }
    }
    
    private function getRequestsByUser($user_id) {
        $result = $this->request->readByUser($user_id);
        $num = $result->rowCount();
        if ($num > 0) {
            $requests_arr = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $requests_arr[] = $row;
            }
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $requests_arr
            ]);
        } else {
            $this->sendError('No requests found for this user', 404);
        }
    }
    
    private function getRequestsByStatus($status) {
        $result = $this->request->readByStatus($status);
        $num = $result->rowCount();
        if ($num > 0) {
            $requests_arr = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $requests_arr[] = $row;
            }
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $requests_arr
            ]);
        } else {
            $this->sendError('No requests found with this status', 404);
        }
    }
    
    private function getRequestNotes($request_id) {
        if (!class_exists('RequirementNote')) {
            $this->sendError("Required class 'RequirementNote' is missing.", 500);
            return;
        }
        $note = new RequirementNote($this->db);
        $result = $note->getByRequest($request_id);
        if ($result->rowCount() > 0) {
            $notes_arr = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $notes_arr[] = $row;
            }
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $notes_arr
            ]);
        } else {
            $this->sendError('No notes found', 404);
        }
    }
    
    private function getRequestForm($type_id) {
        if (!class_exists('FormGenerator')) {
            $this->sendError("Required class 'FormGenerator' is missing.", 500);
            return;
        }
        $formGenerator = new FormGenerator($this->db);
        $form = $formGenerator->getRequestForm($type_id);
        if ($form) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $form
            ]);
        } else {
            $this->sendError('Request type not found', 404);
        }
    }
    
    // Helper function to check for missing text fields
    private function checkMissingFields($data, array $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (empty($data->{$field})) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
    
    // Helper function to check for missing file fields
    private function checkMissingFiles(array $fileKeys) {
        $missing = [];
        foreach ($fileKeys as $key) {
            if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
                $missing[] = $key;
            }
        }
        return $missing;
    }
    
    private function sendError($message, $code = 400, $errors = null) {
        http_response_code($code);
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        echo json_encode($response);
    }
}
?>
