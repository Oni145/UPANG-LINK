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
            // Special handling for request types that don't need additional fields
            // as their data is already available in student details
            if (stripos($result['name'], 'Course Module') !== false || 
                stripos($result['name'], 'Enrollment Certificate') !== false) {
                
                return [
                    'request_type' => $result['name'],
                    'processing_time' => $result['processing_time'],
                    'form_data' => [
                        'required_fields' => [],
                        'optional_fields' => [],
                        'instructions' => 'No additional information needed for this request.'
                    ]
                ];
            }
            
            // Special handling for ID Replacement - requires specific documents
            if (stripos($result['name'], 'ID Replacement') !== false) {
                return [
                    'request_type' => $result['name'],
                    'processing_time' => $result['processing_time'],
                    'form_data' => [
                        'required_fields' => [
                            [
                                'name' => 'affidavit_of_loss',
                                'label' => 'Affidavit of Loss',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of your Affidavit of Loss',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ],
                            [
                                'name' => 'payment_receipt',
                                'label' => 'Payment Receipt',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of your Payment Receipt',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ]
                        ],
                        'optional_fields' => [],
                        'instructions' => 'Please upload the required documents for ID Replacement.'
                    ]
                ];
            }
            
            // Special handling for New Student ID - requires photo and signature
            if (stripos($result['name'], 'New Student ID') !== false) {
                return [
                    'request_type' => $result['name'],
                    'processing_time' => $result['processing_time'],
                    'form_data' => [
                        'required_fields' => [
                            [
                                'name' => 'id_photo',
                                'label' => '1x1 ID Photo',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a 1x1 ID photo with white background',
                                'allowed_types' => 'jpg,jpeg,png'
                            ],
                            [
                                'name' => 'signature',
                                'label' => 'Signature',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a clear image of your signature on white paper',
                                'allowed_types' => 'jpg,jpeg,png,pdf'
                            ]
                        ],
                        'optional_fields' => [],
                        'instructions' => 'Please upload your 1x1 ID photo and signature for your new student ID.'
                    ]
                ];
            }
            
            // Special handling for Transcript of Records - requires request form and clearance
            if (stripos($result['name'], 'Transcript of Records') !== false) {
                return [
                    'request_type' => $result['name'],
                    'processing_time' => $result['processing_time'],
                    'form_data' => [
                        'required_fields' => [
                            [
                                'name' => 'request_form',
                                'label' => 'Request Form',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of the completed Request Form',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ],
                            [
                                'name' => 'clearance',
                                'label' => 'Clearance',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of your Clearance',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ]
                        ],
                        'optional_fields' => [],
                        'instructions' => 'Please upload the required documents for Transcript of Records. Your student information will be automatically included.'
                    ]
                ];
            }
            
            $requirements = json_decode($result['requirements'], true);
            
            // Separate required and optional fields
            $fields = $requirements['fields'];
            $required_fields = array_filter($fields, function($field) {
                return $field['required'] === true;
            });
            $optional_fields = array_filter($fields, function($field) {
                return $field['required'] === false;
            });
            
            return [
                'request_type' => $result['name'],
                'processing_time' => $result['processing_time'],
                'form_data' => [
                    'required_fields' => array_values($required_fields),
                    'optional_fields' => array_values($optional_fields),
                    'instructions' => $requirements['instructions']
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