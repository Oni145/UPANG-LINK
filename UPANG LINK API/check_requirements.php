<?php
// Script to check the requirements for all request types

// Include database configuration
require_once __DIR__ . '/config/Database.php';

// Connect to the database
try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Query all request types
    $query = "SELECT name, requirements FROM request_types WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Request Type: " . $row['name'] . "\n";
        echo "Requirements: " . print_r($row['requirements'], true) . "\n";
        echo "----------------------------------------\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 