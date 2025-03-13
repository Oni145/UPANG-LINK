<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Start transaction
    $db->beginTransaction();

    // Define standardized requirements for all request types
    $standardRequirements = [
        'ID Replacement' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'affidavit_loss',
                    'label' => 'Affidavit of Loss',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf',
                    'description' => 'Upload notarized affidavit of loss'
                ],
                [
                    'name' => 'id_picture',
                    'label' => '1x1 ID Picture',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'jpg,png',
                    'description' => '1x1 ID Picture with white background'
                ]
            ],
            'instructions' => 'Please provide your student ID number and upload the required documents.'
        ],
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
                    'description' => 'Upload your completed clearance form'
                ],
                [
                    'name' => 'request_letter',
                    'label' => 'Request Letter',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,doc,docx',
                    'description' => 'Upload your formal request letter'
                ]
            ],
            'instructions' => 'Please submit all required documents in the specified format.'
        ],
        'Enrollment Certificate' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'year_level',
                    'label' => 'Year Level',
                    'type' => 'select',
                    'required' => true,
                    'description' => 'Select your current year level',
                    'options' => ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year']
                ],
                [
                    'name' => 'purpose',
                    'label' => 'Purpose',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'State the purpose for requesting the certificate'
                ]
            ],
            'instructions' => 'Please provide your student ID, year level, and purpose for requesting the certificate.'
        ],
        'Course Module Request' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'course_code',
                    'label' => 'Course Code',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter the course code for the module'
                ],
                [
                    'name' => 'year_level',
                    'label' => 'Year Level',
                    'type' => 'select',
                    'required' => true,
                    'description' => 'Select your current year level',
                    'options' => ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year']
                ],
                [
                    'name' => 'proof_of_enrollment',
                    'label' => 'Proof of Enrollment',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Upload your proof of enrollment'
                ]
            ],
            'instructions' => 'Please provide all required information and documents for the course module request.'
        ],
        'PE Uniform Request' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'uniform_size',
                    'label' => 'PE Uniform Size',
                    'type' => 'select',
                    'required' => true,
                    'description' => 'Select your PE uniform size',
                    'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']
                ]
            ],
            'instructions' => 'Please provide your student ID number and select your PE uniform size.'
        ],
        'School Uniform Request' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'uniform_size',
                    'label' => 'School Uniform Size',
                    'type' => 'select',
                    'required' => true,
                    'description' => 'Select your school uniform size',
                    'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']
                ]
            ],
            'instructions' => 'Please provide your student ID number and select your school uniform size.'
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
                    'name' => 'supporting_document',
                    'label' => 'Supporting Document',
                    'type' => 'file',
                    'required' => true,
                    'allowed_types' => 'pdf,jpg,png',
                    'description' => 'Schedule or enrollment receipt'
                ],
                [
                    'name' => 'student_id',
                    'label' => 'Student ID',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'course_code',
                    'label' => 'Course Code',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your course code (e.g., BSIT, BSCS)'
                ],
                [
                    'name' => 'address',
                    'label' => 'Address',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your complete address'
                ],
                [
                    'name' => 'birthdate',
                    'label' => 'Birthdate',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your birthdate (MM/DD/YYYY)'
                ],
                [
                    'name' => 'emergency_name',
                    'label' => 'Emergency Contact Name',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Full name of emergency contact'
                ],
                [
                    'name' => 'emergency_phone',
                    'label' => 'Emergency Contact Phone',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Phone number of emergency contact'
                ]
            ],
            'instructions' => 'Please ensure your ID picture has a white background and you are wearing formal attire. Upload your schedule or enrollment receipt as supporting document. Fill in all required personal information accurately.'
        ]
    ];

    // Update each request type
    foreach ($standardRequirements as $typeName => $requirements) {
        echo "Updating $typeName...\n";
        
        $query = "UPDATE request_types SET requirements = ? WHERE name = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([json_encode($requirements), $typeName]);
        
        echo "Updated " . $stmt->rowCount() . " rows\n";
    }

    // Commit transaction
    $db->commit();
    echo "\nAll updates committed successfully!\n";

    // Verify updates
    echo "\nVerifying updates:\n";
    $query = "SELECT name, requirements FROM request_types WHERE name IN (" . 
            str_repeat('?,', count($standardRequirements) - 1) . '?)';
    $stmt = $db->prepare($query);
    $stmt->execute(array_keys($standardRequirements));
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "Type: " . $row['name'] . "\n";
        $decoded = json_decode($row['requirements'], true);
        echo "Fields: " . count($decoded['fields']) . "\n";
        foreach ($decoded['fields'] as $field) {
            echo "- " . $field['name'] . " (" . $field['type'] . "): " . 
                 ($field['required'] ? 'Required' : 'Optional') . "\n";
        }
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "Transaction rolled back due to error.\n";
    }
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 