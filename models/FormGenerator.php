<?php
class FormGenerator {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getRequestForm($type_id) {
        $query = "SELECT name, requirements, processing_time FROM request_types WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $type_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) {
            // Decode the requirements JSON
            $requirements = json_decode($result['requirements'], true);
            if (!$requirements) {
                // If decoding fails, default to an empty array
                $requirements = [];
            }
            // Get the 'fields' element; if not set, use an empty array
            $fields = isset($requirements['fields']) ? $requirements['fields'] : [];
            // If $fields is not an array, attempt to decode it
            if (!is_array($fields)) {
                $fields = json_decode($fields, true);
            }
            // If still not an array, default to an empty array
            if (!is_array($fields)) {
                $fields = [];
            }
            
            // Separate required and optional fields
            $required_fields = array_filter($fields, function($field) {
                return is_array($field) && isset($field['required']) && $field['required'] === true;
            });
            $optional_fields = array_filter($fields, function($field) {
                return is_array($field) && isset($field['required']) && $field['required'] === false;
            });
            
            return [
                'request_type' => $result['name'],
                'processing_time' => $result['processing_time'],
                'form_data' => [
                    'required_fields' => array_values($required_fields),
                    'optional_fields' => array_values($optional_fields),
                    'instructions' => isset($requirements['instructions']) ? $requirements['instructions'] : ""
                ]
            ];
        }
        return false;
    }
    
    public function validateSubmission($type_id, $data, $files) {
        $form = $this->getRequestForm($type_id);
        if(!$form) return false;
        
        $errors = [];
        $warnings = [];
        
        // Validate required fields
        foreach($form['form_data']['required_fields'] as $field) {
            if($field['type'] === 'file') {
                if(!isset($files[$field['name']]) || $files[$field['name']]['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = $field['label'] . " is required";
                } else {
                    // Validate file type
                    $allowed = explode(',', $field['allowed_types']);
                    $ext = strtolower(pathinfo($files[$field['name']]['name'], PATHINFO_EXTENSION));
                    if(!in_array($ext, $allowed)) {
                        $errors[] = $field['label'] . " must be one of these types: " . $field['allowed_types'];
                    }
                }
            } else {
                if(!isset($data[$field['name']]) || empty($data[$field['name']])) {
                    $errors[] = $field['label'] . " is required";
                }
            }
        }
        
        // Validate optional fields if provided
        foreach($form['form_data']['optional_fields'] as $field) {
            if($field['type'] === 'file' && isset($files[$field['name']]) && $files[$field['name']]['error'] === UPLOAD_ERR_OK) {
                // Validate file type for optional files
                $allowed = explode(',', $field['allowed_types']);
                $ext = strtolower(pathinfo($files[$field['name']]['name'], PATHINFO_EXTENSION));
                if(!in_array($ext, $allowed)) {
                    $warnings[] = $field['label'] . " must be one of these types: " . $field['allowed_types'] . ". This file will be ignored.";
                }
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
?>
