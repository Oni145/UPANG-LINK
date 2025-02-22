<?php
class FormGenerator {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Retrieve the form template for a given request type.
    public function getRequestForm($type_id) {
        $query = "SELECT name, requirements, processing_time FROM request_types WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $type_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            // Decode the requirements JSON.
            $requirements = json_decode($result['requirements'], true);
            if (!$requirements) {
                $requirements = [];
            }
            
            // Check if the requirements is structured as an object with a "fields" key.
            if (isset($requirements['fields']) && is_array($requirements['fields'])) {
                $fields = $requirements['fields'];
            } elseif (is_array($requirements)) {
                // If it's a plain array (e.g. ["Valid student ID"]), map each element.
                $fields = array_map(function($req) {
                    $name = strtolower(str_replace(' ', '_', $req));
                    $lower_req = strtolower($req);
                    $type = 'text';  // Default field type.
                    $allowed_types = '';
                    
                    // Check if the requirement should be a file upload.
                    if (strpos($lower_req, 'student id') !== false) {
                        // For "Valid student ID", allow only image formats.
                        $type = 'file';
                        $allowed_types = 'jpg,png,jpeg';
                    } elseif (strpos($lower_req, 'registration form') !== false) {
                        // For "Registration Form", only allow PDFs.
                        $type = 'file';
                        $allowed_types = 'pdf';
                    } elseif (
                        strpos($lower_req, 'form') !== false ||
                        strpos($lower_req, 'letter') !== false ||
                        strpos($lower_req, 'picture') !== false ||
                        strpos($lower_req, 'loss') !== false ||
                        strpos($lower_req, 'approval') !== false
                    ) {
                        $type = 'file';
                        $allowed_types = 'pdf,jpg,png,doc,docx';
                    }
                    
                    return [
                        'name' => $name,
                        'label' => $req,
                        'type' => $type,
                        'required' => true,
                        'allowed_types' => $allowed_types
                    ];
                }, $requirements);
            } else {
                $fields = [];
            }
            
            // Separate required and optional fields.
            $required_fields = array_filter($fields, function($field) {
                return is_array($field) && isset($field['required']) && $field['required'] === true;
            });
            $optional_fields = array_filter($fields, function($field) {
                return is_array($field) && isset($field['required']) && $field['required'] === false;
            });
            
            return [
                'request_type'    => $result['name'],
                'processing_time' => $result['processing_time'],
                'form_data'       => [
                    'required_fields' => array_values($required_fields),
                    'optional_fields' => array_values($optional_fields),
                    'instructions'    => isset($requirements['instructions']) ? $requirements['instructions'] : ""
                ]
            ];
        }
        return false;
    }
    
    // Validate the submission against the required fields.
    public function validateSubmission($type_id, $data, $files) {
        $form = $this->getRequestForm($type_id);
        if (!$form) {
            return ['is_valid' => false, 'errors' => ['type_id' => 'Invalid request type']];
        }
        
        $errors = [];
        $warnings = [];
        
        // Validate required fields.
        foreach ($form['form_data']['required_fields'] as $field) {
            if ($field['type'] === 'file') {
                if (!isset($files[$field['name']]) || $files[$field['name']]['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = $field['label'] . " is required";
                } else {
                    $allowed = explode(',', $field['allowed_types']);
                    $ext = strtolower(pathinfo($files[$field['name']]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) {
                        $errors[] = $field['label'] . " must be one of these types: " . $field['allowed_types'];
                    }
                }
            } else {
                if (!isset($data[$field['name']]) || empty($data[$field['name']])) {
                    $errors[] = $field['label'] . " is required";
                }
            }
        }
        
        // Validate optional fields if provided.
        foreach ($form['form_data']['optional_fields'] as $field) {
            if ($field['type'] === 'file' && isset($files[$field['name']]) && $files[$field['name']]['error'] === UPLOAD_ERR_OK) {
                $allowed = explode(',', $field['allowed_types']);
                $ext = strtolower(pathinfo($files[$field['name']]['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $warnings[] = $field['label'] . " must be one of these types: " . $field['allowed_types'] . ". This file will be ignored.";
                }
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }
}
?>
