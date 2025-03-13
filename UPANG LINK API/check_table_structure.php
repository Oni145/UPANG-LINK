<?php
// Script to check the structure of the request_types table

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
    
    // Get table structure
    $query = "DESCRIBE request_types";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nColumns in request_types table:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 