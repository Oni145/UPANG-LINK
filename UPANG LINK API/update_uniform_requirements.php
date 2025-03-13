<?php
// Script to update uniform request requirements to remove student ID upload

// Include database configuration
require_once 'config/Database.php';

// Database connection setup
try {
    echo "Connecting to database...\n";
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("Could not connect to database. Please check the Database class.");
    }
    
    // Enable SQL errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Begin transaction
    $db->beginTransaction();
    echo "Starting database transaction...\n";
    
    // Update PE Uniform Request
    $peUniformRequirements = json_encode([
        'fields' => [
            [
                'name' => 'student_id_number',
                'label' => 'Student ID Number',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter your student ID number'
            ],
            [
                'name' => 'uniform_size',
                'label' => 'PE Uniform Size',
                'type' => 'text',
                'required' => true,
                'description' => 'Please specify your uniform size (S, M, L, XL)'
            ]
        ],
        'instructions' => 'Please provide your student ID number and uniform size.'
    ]);
    
    $updatePEQuery = "UPDATE request_types SET requirements = ? WHERE name = 'PE Uniform Request'";
    $updatePEStmt = $db->prepare($updatePEQuery);
    $updatePEStmt->execute([$peUniformRequirements]);
    echo "Updated PE Uniform Request: " . $updatePEStmt->rowCount() . " rows\n";
    
    // Update School Uniform Request
    $schoolUniformRequirements = json_encode([
        'fields' => [
            [
                'name' => 'student_id_number',
                'label' => 'Student ID Number',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter your student ID number'
            ],
            [
                'name' => 'uniform_size',
                'label' => 'School Uniform Size',
                'type' => 'text',
                'required' => true,
                'description' => 'Please specify your uniform size (S, M, L, XL)'
            ]
        ],
        'instructions' => 'Please provide your student ID number and uniform size.'
    ]);
    
    $updateSchoolQuery = "UPDATE request_types SET requirements = ? WHERE name = 'School Uniform Request'";
    $updateSchoolStmt = $db->prepare($updateSchoolQuery);
    $updateSchoolStmt->execute([$schoolUniformRequirements]);
    echo "Updated School Uniform Request: " . $updateSchoolStmt->rowCount() . " rows\n";
    
    // Commit the transaction
    $db->commit();
    echo "\nTransaction committed. Uniform request types updated successfully!\n";
    
    // Show the updated requirements
    $query = "SELECT name, requirements FROM request_types WHERE name LIKE '%Uniform Request'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUpdated Uniform Request Types:\n";
    foreach ($types as $type) {
        echo "- " . $type['name'] . ":\n";
        echo json_encode(json_decode($type['requirements']), JSON_PRETTY_PRINT) . "\n\n";
    }
    
} catch (Exception $e) {
    // Rollback the transaction if an error occurred
    if (isset($db)) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?> 