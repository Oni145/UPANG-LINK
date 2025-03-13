<?php
// Include database connection
require_once 'config/Database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// First, check if the table exists
$query = "SHOW TABLES LIKE 'request_types'";
$stmt = $conn->prepare($query);
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

echo "Table request_types exists: " . ($tableExists ? "Yes" : "No") . "\n\n";

if (!$tableExists) {
    echo "The request_types table does not exist. Please check your database setup.\n";
    exit;
}

// List all request types to verify data
echo "All request types in the database:\n";
$query = "SELECT type_id, name FROM request_types";
$stmt = $conn->prepare($query);
$stmt->execute();
$allTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($allTypes as $type) {
    echo "ID: " . $type['type_id'] . ", Name: " . $type['name'] . "\n";
}
echo "\n";

// Query to get School Uniform Request type
echo "Looking for 'School Uniform Request'...\n";
$query = "SELECT * FROM request_types WHERE name = 'School Uniform Request'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "School Uniform Request found with ID: " . $result['type_id'] . "\n\n";
    echo "Full details:\n";
    print_r($result);

    // Check if requirements field exists and is properly formatted
    if (isset($result['requirements'])) {
        echo "\n\nRequirements JSON:\n";
        echo $result['requirements'];
        
        echo "\n\nDecoded Requirements:\n";
        $requirements = json_decode($result['requirements'], true);
        print_r($requirements);
        
        // Check if fields array exists
        if (isset($requirements['fields'])) {
            echo "\n\nFields count: " . count($requirements['fields']) . "\n";
            echo "Fields details:\n";
            print_r($requirements['fields']);
        } else {
            echo "\n\nNo 'fields' array found in requirements\n";
        }
    } else {
        echo "\n\nNo requirements field found\n";
    }
} else {
    echo "School Uniform Request not found in the database.\n";
    
    // Try a more flexible search
    echo "\nTrying a more flexible search...\n";
    $query = "SELECT type_id, name FROM request_types WHERE name LIKE '%uniform%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $similarTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($similarTypes) {
        echo "Found similar request types:\n";
        foreach ($similarTypes as $type) {
            echo "ID: " . $type['type_id'] . ", Name: " . $type['name'] . "\n";
        }
    } else {
        echo "No similar request types found.\n";
    }
}

// Check column structure
echo "\n\nTable structure:\n";
$query = "DESCRIBE request_types";
$stmt = $conn->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($columns);
?> 