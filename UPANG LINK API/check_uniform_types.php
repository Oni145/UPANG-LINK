<?php
// Include database connection
require_once 'config/Database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Check for uniform-related request types
echo "Checking for uniform-related request types:\n";
$stmt = $conn->query("SELECT type_id, name, requirements FROM request_types WHERE name LIKE '%Uniform%'");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($types)) {
    echo "No uniform-related request types found.\n";
} else {
    echo "Found " . count($types) . " uniform-related request types:\n";
    foreach ($types as $type) {
        echo "\nID: " . $type['type_id'] . ", Name: " . $type['name'] . "\n";
        echo "Requirements: " . $type['requirements'] . "\n";
        
        // Decode and check requirements
        $requirements = json_decode($type['requirements'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error decoding JSON: " . json_last_error_msg() . "\n";
        } else {
            echo "Decoded requirements: \n";
            print_r($requirements);
        }
    }
}

// Check for School Uniform Request specifically
echo "\n\nChecking for 'School Uniform Request' specifically:\n";
$stmt = $conn->prepare("SELECT * FROM request_types WHERE name = ?");
$stmt->execute(['School Uniform Request']);
$schoolUniform = $stmt->fetch(PDO::FETCH_ASSOC);

if ($schoolUniform) {
    echo "Found School Uniform Request with ID: " . $schoolUniform['type_id'] . "\n";
    print_r($schoolUniform);
} else {
    echo "School Uniform Request not found.\n";
    
    // Check for similar names
    echo "\nChecking for similar names:\n";
    $stmt = $conn->query("SELECT type_id, name FROM request_types");
    $allTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allTypes as $type) {
        echo $type['type_id'] . ": " . $type['name'] . "\n";
    }
}
?> 