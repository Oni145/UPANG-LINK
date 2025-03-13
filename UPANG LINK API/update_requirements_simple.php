<?php
// Simple script to update the Enrollment Certificate requirements

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
    
    // Update the Enrollment Certificate with proper JSON format
    echo "Updating Enrollment Certificate requirements...\n";
    
    // Define the new JSON fields structure
    $newRequirementsJson = json_encode([
        'fields' => [
            [
                'name' => 'student_id',
                'label' => 'Student ID',
                'type' => 'file',
                'required' => true,
                'allowed_types' => 'pdf,jpg,png',
                'description' => 'Valid student ID document'
            ]
        ],
        'instructions' => 'Please upload a clear copy of your student ID. This is required to verify your enrollment status.'
    ]);
    
    // Update the database for Enrollment Certificate
    $updateQuery = "UPDATE request_types SET requirements = ? WHERE name = 'Enrollment Certificate'";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$newRequirementsJson]);
    
    echo "Updated " . $updateStmt->rowCount() . " rows for Enrollment Certificate\n";
    
    // Commit the transaction
    $db->commit();
    echo "\nTransaction committed. Update completed successfully!\n";
    
    // Verify the update
    $query = "SELECT name, requirements FROM request_types WHERE name = 'Enrollment Certificate'";
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