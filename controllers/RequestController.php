<?php
if (!class_exists('RequestController')) {

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
         * Checks admin_tokens, then auth_tokens (student).
         * Applies sliding expiration if a token is found.
         */
        private function authenticate() {
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
            
            if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
                $token = $matches[1];
            } else {
                $this->sendError("Access Denied: Invalid token format", 401);
                exit;
            }
            
            // Check admin_tokens table first
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
                $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
                $updateStmt = $this->db->prepare("UPDATE admin_tokens SET expires_at = ? WHERE token = ?");
                $updateStmt->execute([$newExpiresAt, $token]);
                return; // Authenticated as admin.
            }
            
            // Finally, check auth_tokens (student tokens)
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
                $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
                $updateStmt = $this->db->prepare("UPDATE auth_tokens SET expires_at = ? WHERE token = ?");
                $updateStmt->execute([$newExpiresAt, $token]);
                return; // Authenticated as student.
            }
            
            $this->sendError("Access Denied: Invalid or expired token", 401);
            exit;
        }
        
        public function handleRequest($method, $uri) {
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
        
        /**
         * createRequest:
         * Processes the POST request to create a new request.
         * Implements a rate limit counter (maximum 1000 posts per hour) using the "rate_limits" table.
         * If more than an hour has passed since the stored start_time, the counter is reset.
         */
        private function createRequest() {
            // Parse incoming data.
            $data = json_decode(file_get_contents("php://input"));
            if (!$data) {
                $data = (object) $_POST;
            }
            
            // Rate limit check using the "rate_limits" table.
            $userId = $data->user_id;
            $currentTime = time();
            
            // Retrieve the user's rate limit record.
            $stmt = $this->db->prepare("SELECT counter, start_time FROM rate_limits WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rateLimit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rateLimit) {
                // No record exists; create one.
                $insertStmt = $this->db->prepare("INSERT INTO rate_limits (user_id, counter, start_time) VALUES (?, ?, ?)");
                $insertStmt->execute([$userId, 0, date('Y-m-d H:i:s', $currentTime)]);
                $counter = 0;
                $startTime = $currentTime;
            } else {
                $counter = (int)$rateLimit['counter'];
                $startTime = strtotime($rateLimit['start_time']);
            }
            
            // Reset the counter if an hour has passed.
            if (($currentTime - $startTime) >= 3600) {
                $resetStmt = $this->db->prepare("UPDATE rate_limits SET counter = 0, start_time = ? WHERE user_id = ?");
                $resetStmt->execute([date('Y-m-d H:i:s', $currentTime), $userId]);
                $counter = 0;
            }
            
            // Deny the request if the limit has been reached.
            if ($counter >= 1000) {
                $this->sendError('Rate limit exceeded. Maximum 1000 posts per hour allowed.', 429);
                return;
            }
            
            // Increment the counter.
            $incStmt = $this->db->prepare("UPDATE rate_limits SET counter = counter + 1 WHERE user_id = ?");
            $incStmt->execute([$userId]);
            
            // Check for missing required text fields.
            $missing = $this->checkMissingFields($data, ['user_id', 'type_id', 'purpose']);
            if (!empty($missing)) {
                $this->sendError('Missing parameters: ' . implode(', ', $missing));
                return;
            }
            
            // Check for missing required file fields.
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
            
            if ($validation === false || (isset($validation['is_valid']) && $validation['is_valid'] === false)) {
                $errors = isset($validation['errors']) ? $validation['errors'] : 'Validation failed: Submission is invalid. Please check your input data.';
                $this->sendError('Validation failed', 400, $errors);
                return;
            }
            
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
        }
        
        // GET functions.
        private function getAllRequests() {
            $stmt = $this->request->read();
            if ($stmt->rowCount() > 0) {
                $requests_arr = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['requirements'])) {
                        unset($row['requirements']);
                    }
                    $allowedFields = $this->getAllowedFileKeys($row['type_id']);
                    $docs = $this->getRequiredDocuments($row['request_id'], $allowedFields);
                    $row = array_merge($row, $docs);
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
                if (isset($result['requirements'])) {
                    unset($result['requirements']);
                }
                $allowedFields = $this->getAllowedFileKeys($result['type_id']);
                $docs = $this->getRequiredDocuments($result['request_id'], $allowedFields);
                $result = array_merge($result, $docs);
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                $this->sendError('Request not found', 404);
            }
        }
        
        private function getRequestsByUser($user_id) {
            $result = $this->request->readByUser($user_id);
            if ($result->rowCount() > 0) {
                $requests_arr = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['requirements'])) {
                        unset($row['requirements']);
                    }
                    $allowedFields = $this->getAllowedFileKeys($row['type_id']);
                    $docs = $this->getRequiredDocuments($row['request_id'], $allowedFields);
                    $row = array_merge($row, $docs);
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
            if ($result->rowCount() > 0) {
                $requests_arr = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['requirements'])) {
                        unset($row['requirements']);
                    }
                    $allowedFields = $this->getAllowedFileKeys($row['type_id']);
                    $docs = $this->getRequiredDocuments($row['request_id'], $allowedFields);
                    $row = array_merge($row, $docs);
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
        
        /**
         * getAllowedFileKeys:
         * Uses the form template to fetch allowed file field names for a given type_id.
         */
        private function getAllowedFileKeys($type_id) {
            $allowed = [];
            if (!class_exists('FormGenerator')) {
                return $allowed;
            }
            $formGenerator = new FormGenerator($this->db);
            $form = $formGenerator->getRequestForm($type_id);
            if (!empty($form['form_data'])) {
                if (!empty($form['form_data']['required_fields'])) {
                    foreach ($form['form_data']['required_fields'] as $field) {
                        if ($field['type'] === 'file') {
                            $allowed[] = $field['name'];
                        }
                    }
                }
                if (!empty($form['form_data']['optional_fields'])) {
                    foreach ($form['form_data']['optional_fields'] as $field) {
                        if ($field['type'] === 'file') {
                            $allowed[] = $field['name'];
                        }
                    }
                }
            }
            return $allowed;
        }
        
        /**
         * getRequiredDocuments:
         * Retrieves all file documents for a request and groups them by a mapped document_type.
         */
        private function getRequiredDocuments($request_id, $allowedFields = null) {
            $query = "SELECT document_type, file_name, file_path FROM required_documents WHERE request_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$request_id]);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            
            // Mapping from stored document_type to desired display keys.
            $mapping = [
                'clearance_form'    => 'Clearance',
                'request_letter'    => 'RequestLetter',
                'valid_id'          => 'StudentID',
                'valid_student_id'  => 'StudentID',
                'registration_form' => 'RegistrationForm',
                'affidavit_of_loss' => 'AffidavitOfLoss',
                'id_picture'        => 'IDPicture',
                'professor_approval'=> 'ProfessorApproval'
            ];
            
            foreach ($documents as $doc) {
                // Filter: always include clearance_form and request_letter.
                if (is_array($allowedFields) && 
                    !in_array($doc['document_type'], $allowedFields) && 
                    $doc['document_type'] !== 'clearance_form' && 
                    $doc['document_type'] !== 'request_letter') {
                    continue;
                }
                $filePath = "../uploads/documents/" . str_replace('uploads/uploads', 'uploads', $doc['file_path']);
                $docType = $doc['document_type'];
                $displayKey = isset($mapping[$docType]) ? $mapping[$docType] : ucfirst($docType);
                
                if (!isset($result[$displayKey])) {
                    $result[$displayKey] = [];
                }
                $result[$displayKey][] = [
                    'file_name' => $doc['file_name'],
                    'file_path' => $filePath
                ];
            }
            return $result;
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
        
        // Helper functions.
        
        private function checkMissingFields($data, array $fields) {
            $missing = [];
            foreach ($fields as $field) {
                if (empty($data->{$field})) {
                    $missing[] = $field;
                }
            }
            return $missing;
        }
        
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
}
?>
