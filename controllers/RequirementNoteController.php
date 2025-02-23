<?php
if (!class_exists('RequirementNoteController')) {

    require_once __DIR__ . '/../models/RequirementNote.php';

    class RequirementNoteController {
        private $db;
        private $requirementNote;
        private $adminId; // holds the admin_id from admin_tokens

        public function __construct($db) {
            $this->db = $db;
            $this->requirementNote = new RequirementNote($this->db);
        }

        // Entry point: authenticate, then route based on the HTTP method.
        public function handleRequest($method, $endpoint) {
            $this->authenticate();
            switch ($method) {
                case 'POST':
                    $this->create();
                    break;
                case 'PUT':  // Update is handled via HTTP PUT.
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
        
        /**
         * Authenticate the request.
         * Expects an "Authorization: Bearer YOUR_VALID_TOKEN" header.
         * Checks the admin_tokens table first. If a valid admin token is found,
         * updates its expiration and sets $this->adminId.
         * If a token is found in auth_tokens (student token), an error is returned.
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
            
            // Check admin_tokens table first.
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
                // Extend expiration (sliding expiration, e.g., one day)
                $newExpiresAt = date('Y-m-d H:i:s', time() + 86400);
                $updateStmt = $this->db->prepare("UPDATE admin_tokens SET expires_at = ? WHERE token = ?");
                $updateStmt->execute([$newExpiresAt, $token]);
                $this->adminId = $adminRow['admin_id'];
                return; // Authenticated as admin.
            }
            
            // If token is found in auth_tokens, disallow posting.
            $stmtStudent = $this->db->prepare("SELECT user_id, expires_at FROM auth_tokens WHERE token = ?");
            $stmtStudent->execute([$token]);
            $studentRow = $stmtStudent->fetch(PDO::FETCH_ASSOC);
            if ($studentRow) {
                $this->sendError("Access Denied: Only admin tokens are allowed for posting notes.", 401);
                exit;
            }
            
            $this->sendError("Access Denied: Invalid or expired token", 401);
            exit;
        }

        // Create a new requirement note.
        // Ignores any admin_id provided in the payload and uses the token-derived adminId.
        // Checks for duplicate note based solely on request_id and admin_id.
        private function create() {
            $data = json_decode(file_get_contents("php://input"));
            // Validate required fields.
            $required = ['request_id', 'requirement_name', 'note'];
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
            
            // Check for duplicate note based on request_id and admin_id.
            $query = "SELECT COUNT(*) as count FROM " . $this->requirementNote->getTableName() . " 
                      WHERE request_id = :request_id 
                        AND admin_id = :admin_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":request_id", $data->request_id);
            $stmt->bindParam(":admin_id", $this->adminId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['count'] > 0) {
                error_log("Duplicate note attempt detected for request_id: {$data->request_id} by admin id: {$this->adminId}");
                http_response_code(409); // Conflict.
                echo json_encode([
                    "status" => "error",
                    "message" => "A requirement note for this request already exists. Please verify the note details or update the existing note if needed."
                ]);
                return;
            }
            
            // Set the note details using the token-derived admin id.
            $this->requirementNote->request_id = $data->request_id;
            $this->requirementNote->admin_id = $this->adminId;
            $this->requirementNote->requirement_name = $data->requirement_name;
            $this->requirementNote->note = $data->note;
            
            try {
                if ($this->requirementNote->create()) {
                    http_response_code(201);
                    echo json_encode(["message" => "Requirement note was created."]);
                } else {
                    throw new Exception("Unable to create requirement note.");
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') { // Integrity constraint violation.
                    http_response_code(400);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Database integrity error: Possibly invalid admin or request ID.",
                        "error" => $e->getMessage()
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Database error occurred.",
                        "error" => $e->getMessage()
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage()
                ]);
            }
        }

        // Update an existing requirement note.
        // Expects a JSON payload with at least the note_id and one update field (requirement_name or note).
        private function update() {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->note_id) || empty($data->note_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Note ID is required for update."]);
                return;
            }
            if (!isset($data->requirement_name) && !isset($data->note)) {
                http_response_code(400);
                echo json_encode(["message" => "At least one update field (requirement_name or note) is required."]);
                return;
            }
            
            $this->requirementNote->note_id = $data->note_id;
            $this->requirementNote->admin_id = $this->adminId; // Ensure the note belongs to the authenticated admin.
            if (isset($data->requirement_name)) {
                $this->requirementNote->requirement_name = $data->requirement_name;
            }
            if (isset($data->note)) {
                $this->requirementNote->note = $data->note;
            }
            
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
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage()
                ]);
            }
        }

        // Retrieve notes.
        // If a "request_id" is provided in GET, filter notes for that request.
        // Otherwise, fetch all created notes (without joining first_name/last_name).
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
        
        // Helper function to send JSON errors.
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
