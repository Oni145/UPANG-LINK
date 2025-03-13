<?php
require_once __DIR__ . '/config/Database.php';

// Connect to the database
try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Define the correct requirements for Enrollment Certificate
    $requirements = [
        'fields' => [
            [
                'name' => 'student_id',
                'label' => 'Student ID Number',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter your student ID number'
            ]
        ],
        'instructions' => 'Please enter your student ID number to request an enrollment certificate.'
    ];

    // Update the database
    $updateQuery = "UPDATE request_types SET requirements = ? WHERE name = 'Enrollment Certificate'";
    $stmt = $db->prepare($updateQuery);
    $jsonRequirements = json_encode($requirements);
    $stmt->execute([$jsonRequirements]);
    
    echo "Updated Enrollment Certificate requirements.\n";

    // Verify the update
    $verifyQuery = "SELECT requirements FROM request_types WHERE name = 'Enrollment Certificate'";
    $stmt = $db->prepare($verifyQuery);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "\nVerification:\n";
        echo "Current requirements: " . json_encode(json_decode($result['requirements']), JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "\nWarning: Could not verify the update.\n";
    }

    echo "\nScript completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 