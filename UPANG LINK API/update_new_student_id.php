<?php
require_once 'config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Connecting to database...\n";
    
    // Start transaction
    $db->beginTransaction();
    
    // Define the new requirements for New Student ID
    $newRequirementsJson = json_encode([
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
                'name' => 'emergency_contact_name',
                'label' => 'Contact Name',
                'type' => 'text',
                'required' => true,
                'description' => 'Full name of emergency contact',
                'group' => 'emergency_contact',
                'group_label' => 'Emergency Contact'
            ],
            [
                'name' => 'emergency_contact_phone',
                'label' => 'Phone Number',
                'type' => 'text',
                'required' => true,
                'description' => 'Phone number of emergency contact',
                'group' => 'emergency_contact',
                'group_label' => 'Emergency Contact'
            ]
        ],
        'instructions' => 'Please ensure your ID picture has a white background and you are wearing formal attire. Upload your schedule or enrollment receipt as supporting document. Fill in all required personal information accurately.'
    ]);
    
    // Update the database for New Student ID
    $updateQuery = "UPDATE request_types SET requirements = ? WHERE name = 'New Student ID'";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$newRequirementsJson]);
    
    echo "Updated " . $updateStmt->rowCount() . " rows for New Student ID\n";
    
    // Commit the transaction
    $db->commit();
    echo "\nTransaction committed. Update completed successfully!\n";
    
    // Verify the update
    $query = "SELECT name, requirements FROM request_types WHERE name = 'New Student ID'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nVerification:\n";
    echo "-------------------------\n";
    echo "Request Type: " . $row['name'] . "\n";
    echo "Requirements: " . $row['requirements'] . "\n";
    
} catch (Exception $e) {
    // Rollback transaction if error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "Transaction rolled back.\n";
    }
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
?> 