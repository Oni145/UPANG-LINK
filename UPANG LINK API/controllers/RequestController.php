<?php
class RequestController {
    private $db;
    private $request;

    public function __construct($db) {
        $this->db = $db;
        $this->request = new Request($db);
    }

    public function handleRequest($method, $uri) {
        switch($method) {
            case 'GET':
                if(isset($uri[1])) {
                    if($uri[1] === 'user' && isset($uri[2])) {
                        $this->getRequestsByUser($uri[2]);
                    } else if($uri[1] === 'status' && isset($uri[2])) {
                        $this->getRequestsByStatus($uri[2]);
                    } else if($uri[1] === 'notes' && isset($uri[2])) {
                        $this->getRequestNotes($uri[2]);
                    } else if($uri[1] === 'form' && isset($uri[2])) {
                        $this->getRequestForm($uri[2]);
                    } else if($uri[1] === 'types') {
                        $this->getRequestTypes();
                    } else if($uri[1] === 'statistics') {
                        $this->getRequestStatistics();
                    } else {
                        $this->getRequest($uri[1]);
                    }
                } else {
                    $this->getAllRequests();
                }
                break;
            case 'POST':
                if(isset($uri[1]) && $uri[1] === 'notes') {
                    $this->addRequirementNote();
                } else {
                    $this->createRequest();
                }
                break;
            case 'PUT':
                if(isset($uri[1])) {
                    $this->updateRequest($uri[1]);
                } else {
                    $this->sendError('Request ID required');
                }
                break;
            case 'DELETE':
                if(isset($uri[1])) {
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

        if($num > 0) {
            $requests_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($requests_arr, $row);
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

        if($result) {
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
        
        if(!empty($data->user_id) && !empty($data->type_id)) {
            // Validate the submission
            $formGenerator = new FormGenerator($this->db);
            $validation = $formGenerator->validateSubmission($data->type_id, (array)$data, $_FILES);
            
            if($validation['is_valid']) {
                $this->request->user_id = $data->user_id;
                $this->request->type_id = $data->type_id;
                $this->request->status = "pending";
                
                // Handle file uploads if present
                $files = [];
                if(!empty($_FILES)) {
                    foreach($_FILES as $key => $file) {
                        if($file['error'] === UPLOAD_ERR_OK) {
                            $files[$key] = $file;
                        }
                    }
                }
                
                if($this->request->createWithRequirements($files)) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Request created successfully',
                        'request_id' => $this->request->request_id
                    ];
                    
                    // Add warnings if any
                    if(!empty($validation['warnings'])) {
                        $response['warnings'] = $validation['warnings'];
                    }
                    
                    http_response_code(201);
                    echo json_encode($response);
                } else {
                    $this->sendError('Unable to create request');
                }
            } else {
                $this->sendError('Validation failed', 400, $validation['errors']);
            }
        } else {
            $this->sendError('Incomplete data');
        }
    }

    private function updateRequest($id) {
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->status)) {
            $this->request->request_id = $id;
            $this->request->status = $data->status;

            if($this->request->update()) {
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
        
        if($this->request->delete()) {
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

        if($num > 0) {
            $requests_arr = array();
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($requests_arr, $row);
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

        if($num > 0) {
            $requests_arr = array();
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($requests_arr, $row);
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

    private function getRequestRequirements($type_id) {
        $query = "SELECT requirements FROM request_types WHERE type_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $type_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => json_decode($result['requirements'], true)
            ]);
        } else {
            $this->sendError('Request type not found', 404);
        }
    }

    private function addRequirementNote() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->request_id) && 
           !empty($data->admin_id) && 
           !empty($data->requirement_name) && 
           !empty($data->note)) {
            
            $note = new RequirementNote($this->db);
            $note->request_id = $data->request_id;
            $note->admin_id = $data->admin_id;
            $note->requirement_name = $data->requirement_name;
            $note->note = $data->note;
            
            if($note->create()) {
                // Update request status to indicate missing requirements
                $this->request->request_id = $data->request_id;
                $this->request->status = "requirements_needed";
                $this->request->update();
                
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Requirement note added successfully'
                ]);
            } else {
                $this->sendError('Unable to add requirement note');
            }
        } else {
            $this->sendError('Incomplete data');
        }
    }

    private function getRequestNotes($request_id) {
        $note = new RequirementNote($this->db);
        $result = $note->getByRequest($request_id);
        
        if($result->rowCount() > 0) {
            $notes_arr = [];
            while($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($notes_arr, $row);
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
        $formGenerator = new FormGenerator($this->db);
        $form = $formGenerator->getRequestForm($type_id);
        
        if($form) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $form
            ]);
        } else {
            $this->sendError('Request type not found', 404);
        }
    }

    private function getRequestTypes() {
        $requestType = new RequestType($this->db);
        $stmt = $requestType->read();
        $num = $stmt->rowCount();

        if($num > 0) {
            $types_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($types_arr, $row);
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $types_arr
            ]);
        } else {
            $this->sendError('No request types found', 404);
        }
    }

    private function getRequestStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM requests 
                WHERE user_id = ?";

        $stmt = $this->db->prepare($query);
        $user_id = $this->getUserIdFromToken();
        
        if(!$user_id) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if($result) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $result
            ]);
        } else {
            $this->sendError('No statistics found', 404);
        }
    }

    private function getUserIdFromToken() {
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        
        if(!$token) return false;

        $user = new User($this->db);
        $result = $user->validateSession($token);
        
        return $result['valid'] ? $result['user_id'] : false;
    }

    private function sendError($message, $code = 400, $errors = null) {
        http_response_code($code);
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        if($errors !== null) {
            $response['errors'] = $errors;
        }
        echo json_encode($response);
    }
} 