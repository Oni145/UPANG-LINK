<?php
require_once __DIR__ . '/config/Database.php';

// Connect to the database
try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Define the updated requirements for New Student ID
    $updatedRequirements = [
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
                'description' => 'Upload a recent 1x1 ID photo with white background',
                'allowed_types' => 'jpg,jpeg,png'
            ],
            [
                'name' => 'enrollment_proof',
                'label' => 'Proof of Enrollment',
                'type' => 'file',
                'required' => true,
                'description' => 'Upload your proof of enrollment (e.g., registration form, enrollment receipt)',
                'allowed_types' => 'jpg,jpeg,png,pdf'
            ],
            [
                'name' => 'birthdate',
                'label' => 'Birthdate',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter your birthdate in MM/DD/YYYY format (e.g., 01/15/2000)'
            ],
            [
                'name' => 'emergency_contact_name',
                'label' => 'Emergency Contact Name',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter the full name of your emergency contact person'
            ],
            [
                'name' => 'emergency_contact_number',
                'label' => 'Emergency Contact Phone Number',
                'type' => 'text',
                'required' => true,
                'description' => 'NUMBERS ONLY - Enter emergency contact phone number without spaces, dashes or special characters (e.g., 09123456789)'
            ],
            [
                'name' => 'signature',
                'label' => 'Signature',
                'type' => 'file',
                'required' => true,
                'description' => 'Upload a clear image of your signature on white paper',
                'allowed_types' => 'jpg,jpeg,png'
            ]
        ],
        'instructions' => 'Please provide all required information and documents. Ensure that your ID photo has a white background and your signature is clear and written on white paper.'
    ];

    // Update the New Student ID type
    $updateQuery = "UPDATE request_types SET requirements = ? WHERE name LIKE '%New Student ID%'";
    $updateStmt = $db->prepare($updateQuery);
    $jsonRequirements = json_encode($updatedRequirements);
    $updateStmt->execute([$jsonRequirements]);
    
    echo "Updated New Student ID requirements successfully.\n";
    echo "New requirements: " . $jsonRequirements . "\n\n";

    // Verify the update
    $query = "SELECT type_id, name, requirements FROM request_types WHERE name LIKE '%New Student ID%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($type) {
        echo "Verified update for: " . $type['name'] . " (ID: " . $type['type_id'] . ")\n";
        $requirements = json_decode($type['requirements'], true);
        
        if (isset($requirements['fields'])) {
            echo "Fields (" . count($requirements['fields']) . "):\n";
            foreach ($requirements['fields'] as $index => $field) {
                echo "  " . ($index + 1) . ". Name: " . $field['name'] . ", Label: " . $field['label'] . ", Type: " . $field['type'] . ", Required: " . ($field['required'] ? 'Yes' : 'No') . "\n";
                
                if ($field['type'] === 'dropdown' && isset($field['options'])) {
                    echo "     Options: " . count($field['options']) . " choices\n";
                }
            }
            
            if (isset($requirements['instructions'])) {
                echo "Instructions: " . $requirements['instructions'] . "\n";
            }
        }
    } else {
        echo "New Student ID type not found.\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 