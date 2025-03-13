<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Define the requirements for Enrollment Certificate
    $requirements = [
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
                'type' => 'dropdown',
                'required' => true,
                'description' => 'Select your current year level',
                'options' => ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year']
            ],
            [
                'name' => 'purpose',
                'label' => 'Purpose',
                'type' => 'text',
                'required' => true,
                'description' => 'State the purpose of requesting the Enrollment Certificate'
            ]
        ],
        'instructions' => 'Please provide your student ID number, year level, and purpose for requesting the Enrollment Certificate.'
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