<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Begin transaction
    $db->beginTransaction();
    
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

    // Get all request types that match Transcript of Records
    $query = "SELECT type_id, name, requirements FROM request_types WHERE name LIKE '%Transcript%' OR name LIKE '%Record%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($types) . " matching request types.\n\n";
    
    // Update each matching request type
    foreach ($types as $type) {
        echo "Updating: " . $type['name'] . " (ID: " . $type['type_id'] . ")\n";
        
        $updateQuery = "UPDATE request_types SET requirements = ? WHERE type_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $jsonRequirements = json_encode($transcriptRequirements);
        $updateStmt->execute([$jsonRequirements, $type['type_id']]);
        
        echo "  Updated successfully.\n";
    }
    
    // Commit the transaction
    $db->commit();
    echo "\nAll updates committed to database.\n";
    
    // Verify the updates
    $verifyQuery = "SELECT type_id, name, requirements FROM request_types WHERE name LIKE '%Transcript%' OR name LIKE '%Record%'";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->execute();
    
    echo "\nVerification:\n";
    while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['type_id'] . ", Name: " . $row['name'] . "\n";
        $req = json_decode($row['requirements'], true);
        echo "  Fields: " . count($req['fields']) . "\n";
        foreach ($req['fields'] as $field) {
            echo "    - " . $field['name'] . " (" . $field['label'] . "): " . $field['type'] . "\n";
        }
        echo "\n";
    }
    
    echo "Script completed successfully.\n";

} catch (PDOException $e) {
    // Rollback transaction if error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "Transaction rolled back.\n";
    }
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 