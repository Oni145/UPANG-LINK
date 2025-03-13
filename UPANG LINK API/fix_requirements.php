<?php
require_once __DIR__ . '/config/Database.php';

// Connect to the database
try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Query all request types
    $query = "SELECT type_id, name, requirements FROM request_types ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($types) . " request types.\n\n";

    // Define standard requirements for each type
    $standardRequirements = [
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
                    'name' => 'student_id_document',
                    'label' => 'Student ID',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload a clear photo of your student ID',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
                ],
                [
                    'name' => 'professor_approval',
                    'label' => 'Professor Approval',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload the professor approval document',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
                ]
            ],
            'instructions' => 'Please upload your student ID and professor approval document to request course modules.'
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
                    'name' => 'student_id_document',
                    'label' => 'Student ID',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload a clear photo of your student ID',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
                ]
            ],
            'instructions' => 'Please upload your student ID to request an enrollment certificate.'
        ],
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
                    'description' => 'Upload your affidavit of loss document',
                    'allowed_types' => 'pdf'
                ],
                [
                    'name' => 'id_picture',
                    'label' => '1x1 ID Photo',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload a recent 1x1 ID photo',
                    'allowed_types' => 'jpg,jpeg,png'
                ]
            ],
            'instructions' => 'Please upload your affidavit of loss and a recent 1x1 ID photo to request an ID replacement.'
        ],
        'New Student ID' => [
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Student ID Number',
                    'type' => 'text',
                    'required' => true,
                    'description' => 'Enter your student ID number'
                ],
                [
                    'name' => 'id_photo',
                    'label' => '1x1 ID Photo',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload a recent 1x1 ID photo',
                    'allowed_types' => 'jpg,jpeg,png'
                ],
                [
                    'name' => 'enrollment_proof',
                    'label' => 'Proof of Enrollment',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload proof of enrollment (e.g., registration form)',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
                ]
            ],
            'instructions' => 'Please upload a recent 1x1 ID photo and proof of enrollment to request a new student ID.'
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
                    'name' => 'student_id_document',
                    'label' => 'Student ID',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload a clear photo of your student ID',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
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
            'instructions' => 'Please select your PE uniform size and upload your student ID to request a PE uniform.'
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
                    'name' => 'student_id_document',
                    'label' => 'Student ID',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Upload a clear photo of your student ID',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
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
            'instructions' => 'Please select your school uniform size and upload your student ID to request a school uniform.'
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
                    'description' => 'Upload your completed clearance form',
                    'allowed_types' => 'jpg,jpeg,png,pdf'
                ],
                [
                    'name' => 'request_letter',
                    'label' => 'Request Letter',
                    'type' => 'file',
                    'required' => true,
                    'description' => 'Formal letter stating the purpose of requesting TOR',
                    'allowed_types' => 'pdf,doc,docx'
                ]
            ],
            'instructions' => 'Please enter your student ID number and upload your completed clearance form and request letter to request a transcript of records.'
        ]
    ];

    // Update each request type with proper requirements
    foreach ($types as $type) {
        echo "Processing: " . $type['name'] . " (ID: " . $type['type_id'] . ")\n";
        
        // Find the standard requirements for this type
        $newRequirements = null;
        foreach ($standardRequirements as $typeName => $requirements) {
            if (strpos($type['name'], $typeName) !== false) {
                $newRequirements = $requirements;
                break;
            }
        }
        
        // If no matching standard requirements found, use a default
        if ($newRequirements === null) {
            echo "  WARNING: No standard requirements found for this type. Using default.\n";
            $newRequirements = [
                'fields' => [
                    [
                        'name' => 'student_id',
                        'label' => 'Student ID Number',
                        'type' => 'text',
                        'required' => true,
                        'description' => 'Enter your student ID number'
                    ],
                    [
                        'name' => 'student_id_document',
                        'label' => 'Student ID',
                        'type' => 'file',
                        'required' => true,
                        'description' => 'Upload a clear photo of your student ID',
                        'allowed_types' => 'jpg,jpeg,png,pdf'
                    ]
                ],
                'instructions' => 'Please fill out all required fields and upload the necessary documents.'
            ];
        }
        
        // Update the database
        $updateQuery = "UPDATE request_types SET requirements = ? WHERE type_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $jsonRequirements = json_encode($newRequirements);
        $updateStmt->execute([$jsonRequirements, $type['type_id']]);
        
        echo "  Updated requirements: " . $jsonRequirements . "\n\n";
    }

    echo "All request types have been updated with proper requirements.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 