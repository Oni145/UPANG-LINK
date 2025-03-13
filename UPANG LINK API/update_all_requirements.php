<?php
// Script to update all request types to use the standardized fields format

// Include database configuration
require_once 'config/Database.php';

// Database connection setup
try {
    echo "Connecting to database...\n";
    
    // Try to connect using various methods
    $database = new Database();
    $methods = get_class_methods($database);
    $db = null;
    
    // Try common connection methods
    foreach (['connect', 'getConnection', 'dbConnection', 'getDbConnection'] as $method) {
        if (method_exists($database, $method)) {
            try {
                echo "Trying $method()...\n";
                $db = $database->$method();
                if ($db) {
                    echo "Connected with $method()\n";
                    break;
                }
            } catch (Exception $e) {
                echo "Error with $method(): " . $e->getMessage() . "\n";
            }
        }
    }
    
    if (!$db) {
        echo "Available methods: " . implode(", ", $methods) . "\n";
        die("Could not connect to database. Please check the Database class and modify this script.");
    }
    
    // Enable SQL errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Begin transaction
    $db->beginTransaction();
    echo "Starting database transaction...\n";
    
    // Get all request types
    $query = "SELECT type_id, name, requirements FROM request_types";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($types) . " request types to update\n";
    
    // Define standard requirements for each request type
    $standardRequirements = [
        'Transcript of Records' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'clearance_form',
                    'label' => 'Clearance Form',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Fully accomplished clearance form'
                ],
                [
                    'name' => 'request_letter',
                    'label' => 'Request Letter',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,doc,docx',
                    'description' => 'Formal letter stating the purpose of requesting TOR'
                ]
            ],
            'instructions' => 'Please submit all required documents in PDF, JPG, or PNG format.'
        ],
        'Enrollment Certificate' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'student_id_file',
                    'label' => 'Student ID Document',
                    'type' => 'file',
                    'required' => false,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Upload a copy of your student ID (optional)'
                ]
            ],
            'instructions' => 'Please enter your student ID number. You may also upload a copy of your student ID if needed.'
        ],
        'New Student ID' => [
            'fields' => [
                [
                    'name' => 'id_picture',
                    'label' => '1x1 ID Picture',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'jpg,png',
                    'description' => '1x1 ID Picture (white background, formal attire)'
                ],
                [
                    'name' => 'registration_form',
                    'label' => 'Registration Form',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Completed registration form'
                ]
            ],
            'instructions' => 'Please ensure your ID picture has a white background and you are wearing formal attire.'
        ],
        'ID Replacement' => [
            'fields' => [
                [
                    'name' => 'affidavit_loss',
                    'label' => 'Affidavit of Loss',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf',
                    'description' => 'Notarized affidavit of loss'
                ],
                [
                    'name' => 'id_picture',
                    'label' => '1x1 ID Picture',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'jpg,png',
                    'description' => '1x1 ID Picture (white background, formal attire)'
                ],
                [
                    'name' => 'payment_receipt',
                    'label' => 'Payment Receipt',
                    'type' => 'file',
                    'required' => false,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Receipt of payment for ID replacement (can be submitted later)'
                ]
            ],
            'instructions' => 'Please submit a notarized affidavit of loss and a recent ID picture.'
        ],
        'PE Uniform Request' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'student_id_file',
                    'label' => 'Student ID Document',
                    'type' => 'file',
                    'required' => false,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Upload a copy of your student ID (optional)'
                ],
                [
                    'name' => 'size',
                    'label' => 'Uniform Size',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Specify your uniform size (S, M, L, XL, etc.)'
                ]
            ],
            'instructions' => 'Please enter your student ID number and specify your uniform size. You may also upload a copy of your student ID if needed.'
        ],
        'School Uniform Request' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'student_id_file',
                    'label' => 'Student ID Document',
                    'type' => 'file',
                    'required' => false,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Upload a copy of your student ID (optional)'
                ],
                [
                    'name' => 'size',
                    'label' => 'Uniform Size',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Specify your uniform size (S, M, L, XL, etc.)'
                ]
            ],
            'instructions' => 'Please enter your student ID number and specify your uniform size. You may also upload a copy of your student ID if needed.'
        ],
        'Course Module Request' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'student_id_file',
                    'label' => 'Student ID Document',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Upload a copy of your student ID'
                ],
                [
                    'name' => 'course_code',
                    'label' => 'Course Code',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter the code of the course for which you need the module'
                ]
            ],
            'instructions' => 'Please provide your student ID and course code for the module request.'
        ]
    ];
    
    // Update each request type
    foreach ($types as $type) {
        $typeId = $type['type_id'];
        $typeName = $type['name'];
        
        echo "\nProcessing: " . $typeName . "\n";
        
        // Use predefined requirements if available, otherwise convert from the old format
        if (isset($standardRequirements[$typeName])) {
            $newRequirements = $standardRequirements[$typeName];
            echo "  Using predefined requirements\n";
        } else {
            // Parse existing requirements
            $reqJson = json_decode($type['requirements'], true);
            $newRequirements = ['fields' => []];
            
            // Convert from old format if needed
            if (isset($reqJson['required_docs']) && is_array($reqJson['required_docs'])) {
                echo "  Converting from required_docs format\n";
                
                foreach ($reqJson['required_docs'] as $doc) {
                    $fieldName = strtolower(str_replace([' ', '-', '(', ')', ','], '_', $doc));
                    $fieldName = rtrim($fieldName, '_');
                    
                    $newRequirements['fields'][] = [
                        'name' => $fieldName,
                        'label' => $doc,
                        'type' => 'file',
                        'required' => true,
                        'allowed_types' => 'pdf,jpg,png',
                        'description' => "Please provide $doc"
                    ];
                }
                
                $newRequirements['instructions'] = 'Please submit all required documents.';
            } 
            // Keep existing fields format if already present
            elseif (isset($reqJson['fields']) && is_array($reqJson['fields'])) {
                echo "  Already has fields format\n";
                $newRequirements = $reqJson;
            } else {
                echo "  No requirements or empty format\n";
            }
        }
        
        // Update the database
        $newReqStr = json_encode($newRequirements);
        $updateQuery = "UPDATE request_types SET requirements = ? WHERE type_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$newReqStr, $typeId]);
        
        echo "  Updated " . $typeName . " with " . count($newRequirements['fields']) . " field(s)\n";
    }
    
    // Commit the transaction
    $db->commit();
    echo "\nTransaction committed. All request types updated successfully!\n";
    
    // Show the results
    $query = "SELECT 
              name, 
              JSON_CONTAINS_PATH(requirements, 'one', '$.fields') as has_fields_property,
              JSON_LENGTH(JSON_EXTRACT(requirements, '$.fields')) as field_count
              FROM request_types";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "\nResults after update:\n";
    echo "-------------------------\n";
    echo "Request Type | Has Fields | Field Count\n";
    echo "-------------------------\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['name'] . " | " . 
             ($row['has_fields_property'] ? "Yes" : "No") . " | " . 
             $row['field_count'] . "\n";
    }
    
} catch (Exception $e) {
    // Rollback transaction if error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "Transaction rolled back.\n";
    }
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDone!\n";
?> 