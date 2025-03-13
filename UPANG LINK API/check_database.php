<?php
// Include database connection
require_once 'config/Database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// List all tables
echo "All tables in the database:\n";
$stmt = $conn->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

// Check if request_types table exists
if (in_array('request_types', $tables)) {
    echo "\nrequest_types table exists.\n";
    
    // Check table structure
    echo "\nTable structure:\n";
    $stmt = $conn->query('DESCRIBE request_types');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
    
    // List all request types
    echo "\nAll request types:\n";
    $stmt = $conn->query('SELECT * FROM request_types');
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($types);
} else {
    echo "\nrequest_types table does not exist.\n";
}
?> 