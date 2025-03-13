<?php
// Script to remove purpose field for specific request types

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
    
    // List of request types to update
    $requestTypes = [
        'Course Module Request',
        'ID Replacement',
        'New Student ID',
        'PE Uniform Request',
        'School Uniform Request',
        'Transcript of Records'
    ];
    
    // Update each request type
    foreach ($requestTypes as $requestType) {
        echo "Processing $requestType...\n";
        
        // First, get the current requirements for this request type
        $query = "SELECT requirements FROM request_types WHERE name = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$requestType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo "Warning: Request type '$requestType' not found in database. Skipping.\n";
            continue;
        }
        
        // Parse the current requirements
        $requirements = json_decode($result['requirements'], true);
        
        if (!$requirements || !isset($requirements['fields'])) {
            echo "Warning: Invalid requirements format for '$requestType'. Skipping.\n";
            continue;
        }
        
        // Update the database with the modified requirements
        $updateQuery = "UPDATE request_types SET purpose_required = 0 WHERE name = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$requestType]);
        
        echo "Updated $requestType: " . $updateStmt->rowCount() . " rows\n";
    }
    
    // Commit the transaction
    $db->commit();
    echo "\nTransaction committed. Request types updated successfully!\n";
    
    // Show the updated request types
    $query = "SELECT name, purpose_required FROM request_types WHERE name IN ('" . implode("','", $requestTypes) . "')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUpdated Request Types:\n";
    foreach ($types as $type) {
        echo "- " . $type['name'] . ": Purpose Required = " . ($type['purpose_required'] ? "Yes" : "No") . "\n";
    }
    
} catch (Exception $e) {
    // Rollback the transaction if an error occurred
    if (isset($db)) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?> 