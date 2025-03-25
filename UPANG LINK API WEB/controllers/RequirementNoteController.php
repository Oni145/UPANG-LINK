<?php
if (!class_exists('RequirementNoteController')) {

    require_once __DIR__ . '/../models/RequirementNote.php';

    class RequirementNoteController {
        private $db;
        private $requirementNote;
        private $adminId; // Changed from $UserId to be consistent with admin_id usage

        public function __construct($db) {
            $this->db = $db;
            $this->requirementNote = new RequirementNote($this->db);
        }

        public function handleRequest($method, $endpoint) {
            $this->authenticate();
            switch ($method) {
                case 'POST':
                    $this->create();
                    break;
                case 'PUT':
                    $this->update();
                    break;
                case 'GET':
                    $this->get();
                    break;
                default:
                    $this->sendError("Method not allowed.", 405);
                    break;
            }
        }
        
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
            
            // Check user_sessions table (kept as user_id for tokens)
            $stmtAdmin = $this->db->prepare("SELECT user_id, expires_at FROM user_sessions WHERE token = ?");
            $stmtAdmin->execute([$token]);
            $adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
            if ($adminRow) {
                $currentTime = new DateTime();
                $expiresAt = new DateTime($adminRow['expires_at']);
                if ($currentTime > $expiresAt) {
                    $delStmt = $this->db->prepare("DELETE FROM user_sessions WHERE token = ?");
                    $delStmt->execute([$token]);
                    $this->sendError("Access Denied: Admin token expired", 401);
                    exit;
                }
                // Extend expiration
                $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
                $updateStmt = $this->db->prepare("UPDATE user_sessions SET expires_at = ? WHERE token = ?");
                $updateStmt->execute([$newExpiresAt, $token]);
                $this->adminId = $adminRow['user_id']; // Set the adminId property
                return;
            }
            
            $this->sendError("Access Denied: Invalid or expired token", 401);
            exit;
        }

        private function create() {
            $data = json_decode(file_get_contents("php://input"));
            $required = ['request_id', 'note'];
            $missingFields = [];
            foreach ($required as $field) {
                if (!isset($data->$field) || empty($data->$field)) {
                    $missingFields[] = $field;
                }
            }
            if (!empty($missingFields)) {
                http_response_code(400);
                echo json_encode([
                    "message" => "Incomplete data provided.",
                    "missing_fields" => $missingFields
                ]);
                return;
            }
            
            $query = "SELECT COUNT(*) as count FROM " . $this->requirementNote->getTableName() . " 
                      WHERE request_id = :request_id 
                        AND admin_id = :admin_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":request_id", $data->request_id);
            $stmt->bindParam(":admin_id", $this->adminId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['count'] > 0) {
                http_response_code(409);
                echo json_encode([
                    "status" => "error",
                    "message" => "A requirement note for this request already exists."
                ]);
                return;
            }
            
            $this->requirementNote->request_id = $data->request_id;
            $this->requirementNote->admin_id = $this->adminId;
            $this->requirementNote->note = $data->note;
            
            try {
                if ($this->requirementNote->create()) {
                    http_response_code(201);
                    echo json_encode(["message" => "Requirement note was created."]);
                } else {
                    throw new Exception("Unable to create requirement note.");
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Database error occurred.",
                    "error" => $e->getMessage()
                ]);
            }
        }

        private function update() {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->note_id) || empty($data->note_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Note ID is required for update."]);
                return;
            }
            if (!isset($data->note)) {
                http_response_code(400);
                echo json_encode(["message" => "Updated note is required."]);
                return;
            }
            
            $this->requirementNote->note_id = $data->note_id;
            $this->requirementNote->admin_id = $this->adminId;
            $this->requirementNote->note = $data->note;
            
            try {
                if ($this->requirementNote->update()) {
                    http_response_code(200);
                    echo json_encode(["message" => "Requirement note updated successfully."]);
                } else {
                    throw new Exception("Unable to update requirement note.");
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Database error occurred during update.",
                    "error" => $e->getMessage()
                ]);
            }
        }

        private function get() {
            try {
                if (isset($_GET['request_id'])) {
                    $query = "SELECT * FROM " . $this->requirementNote->getTableName() . " 
                              WHERE request_id = ? ORDER BY created_at DESC";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(1, $_GET['request_id']);
                    $stmt->execute();
                } else {
                    $query = "SELECT * FROM " . $this->requirementNote->getTableName() . " ORDER BY created_at DESC";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute();
                }
                
                $notes_arr = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    unset($row['requirement_name']);
                    $notes_arr[] = $row;
                }
                http_response_code(200);
                echo json_encode($notes_arr);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Error fetching notes.",
                    "error" => $e->getMessage()
                ]);
            }
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