<?php
require_once __DIR__ . '/config/Database.php';

// Connect to the database
try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Define the correct requirements for Transcript of Records
    $transcriptRequirements = [
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
    ];

    // Update the database for Transcript of Records
    $updateQuery = "UPDATE request_types SET requirements = ? WHERE name = 'Transcript of Records'";
    $updateStmt = $db->prepare($updateQuery);
    $jsonRequirements = json_encode($transcriptRequirements);
    $updateStmt->execute([$jsonRequirements]);
    
    echo "Transcript of Records requirements updated successfully.\n";
    echo "New requirements: " . $jsonRequirements . "\n";

    // Verify the update
    $verifyQuery = "SELECT requirements FROM request_types WHERE name = 'Transcript of Records'";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->execute();
    $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "\nVerification:\n";
        echo "Current requirements in database: " . $result['requirements'] . "\n";
    } else {
        echo "\nWarning: Could not verify the update. 'Transcript of Records' request type not found.\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 