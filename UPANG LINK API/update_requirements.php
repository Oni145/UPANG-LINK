<?php
// This script updates the requirements format in the database
// to ensure they're compatible with the Android app

// Include database configuration
require_once 'config/Database.php';

// Connect to database - fix the connection method based on your actual Database class
try {
    // Create database object
    $database = new Database();
    
    // Check which connection method is available
    if (method_exists($database, 'getConnection')) {
        $db = $database->getConnection();
    } else if (method_exists($database, 'dbConnection')) {
        $db = $database->dbConnection();
    } else {
        // Try to examine the Database class to find the connection method
        $methods = get_class_methods($database);
        echo "Available methods in Database class: " . implode(", ", $methods) . "\n";
        die("Could not determine the correct database connection method. Please check the Database class.");
    }
    
    // Enable SQL errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $db->beginTransaction();
    
    echo "Updating requirements format in database...\n";
    
    // First, update all request types to have an empty fields array if they have null requirements
    $query = "UPDATE request_types 
              SET requirements = JSON_OBJECT('fields', JSON_ARRAY()) 
              WHERE requirements IS NULL OR requirements = 'null' OR requirements = ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo "Updated request types with null requirements: " . $stmt->rowCount() . " rows\n";
    
    // Update all request types that use the old format (required_docs)
    $query = "UPDATE request_types
              SET requirements = JSON_OBJECT('fields', 
                (SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'name', LOWER(REPLACE(REPLACE(doc, ' ', '_'), '-', '_')),
                        'label', doc,
                        'type', 'file',
                        'required', true,
                        'allowed_types', 'pdf,jpg,png',
                        'description', CONCAT('Please provide ', doc)
                    )
                )
                FROM JSON_TABLE(
                    JSON_EXTRACT(requirements, '$.required_docs'),
                    '$[*]' COLUMNS (doc VARCHAR(255) PATH '$')
                ) as docs)
              )
              WHERE JSON_CONTAINS_PATH(requirements, 'one', '$.required_docs')";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        echo "Updated request types with old format: " . $stmt->rowCount() . " rows\n";
    } catch (Exception $e) {
        echo "Error updating old format: " . $e->getMessage() . "\n";
        
        // Try a simpler approach with individual updates
        echo "Trying individual updates for each request type...\n";
        
        // Get all request types with the old format
        $query = "SELECT type_id, name, requirements FROM request_types 
                 WHERE JSON_CONTAINS_PATH(requirements, 'one', '$.required_docs')";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $oldFormatTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($oldFormatTypes as $type) {
            echo "Processing: " . $type['name'] . "\n";
            $reqJson = json_decode($type['requirements'], true);
            if (isset($reqJson['required_docs']) && is_array($reqJson['required_docs'])) {
                $fields = [];
                foreach ($reqJson['required_docs'] as $doc) {
                    $fields[] = [
                        'name' => strtolower(str_replace([' ', '-'], '_', $doc)),
                        'label' => $doc,
                        'type' => 'file',
                        'required' => true,
                        'allowed_types' => 'pdf,jpg,png',
                        'description' => "Please provide $doc"
                    ];
                }
                
                $newReqJson = ['fields' => $fields];
                $newReqStr = json_encode($newReqJson);
                
                $updateQuery = "UPDATE request_types SET requirements = ? WHERE type_id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$newReqStr, $type['type_id']]);
                echo "Updated " . $type['name'] . "\n";
            }
        }
    }
    
    // Check the results
    $query = "SELECT 
                name, 
                JSON_CONTAINS_PATH(requirements, 'one', '$.fields') as has_fields_property,
                JSON_LENGTH(JSON_EXTRACT(requirements, '$.fields')) as field_count
              FROM request_types";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "\n\nResults after update:\n";
    echo "-------------------------\n";
    echo "Request Type | Has Fields | Field Count\n";
    echo "-------------------------\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['name'] . " | " . 
             ($row['has_fields_property'] ? "Yes" : "No") . " | " . 
             $row['field_count'] . "\n";
    }
    
    // Commit transaction
    $db->commit();
    
    echo "\nUpdate completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback transaction if error
    if (isset($db)) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?> 