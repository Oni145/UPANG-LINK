<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'UPANG LINK API/config/database.php';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();

    echo "Connected to database. Checking users table structure...\n";

    // Check if the columns exist first
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'student_number'");
    $studentNumberExists = $stmt->rowCount() > 0;
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'birthdate'");
    $birthdateExists = $stmt->rowCount() > 0;
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'emergency_contact'");
    $emergencyContactExists = $stmt->rowCount() > 0;
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'course'");
    $courseExists = $stmt->rowCount() > 0;
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'current_year'");
    $currentYearExists = $stmt->rowCount() > 0;

    $alterQueries = [];

    // Prepare alter queries for missing columns
    if (!$studentNumberExists) {
        $alterQueries[] = "ADD COLUMN student_number VARCHAR(50)";
    }
    
    if (!$birthdateExists) {
        $alterQueries[] = "ADD COLUMN birthdate DATE";
    }
    
    if (!$emergencyContactExists) {
        $alterQueries[] = "ADD COLUMN emergency_contact VARCHAR(100)";
    }
    
    if (!$courseExists) {
        $alterQueries[] = "ADD COLUMN course VARCHAR(100)";
    }
    
    if (!$currentYearExists) {
        $alterQueries[] = "ADD COLUMN current_year VARCHAR(20)";
    }

    // If we have changes to make
    if (!empty($alterQueries)) {
        $alterSQL = "ALTER TABLE users " . implode(", ", $alterQueries);
        
        echo "Executing SQL to update users table:\n";
        echo $alterSQL . "\n\n";
        
        if ($conn->exec($alterSQL)) {
            echo "Users table updated successfully!\n";
        } else {
            echo "Failed to update users table.\n";
        }
    } else {
        echo "No changes needed. All required columns already exist.\n";
    }

    // Verify the table structure after updates
    echo "\nCurrent users table structure:\n";
    $stmt = $conn->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?> 