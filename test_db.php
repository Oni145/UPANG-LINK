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

    // Check connection
    if($conn) {
        echo "Database connection successful!\n\n";
        
        // Query to get all tables
        $stmt = $conn->query("SHOW TABLES");
        
        echo "Database Tables:\n";
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "- " . $row[0] . "\n";
        }
        
        // Check if users table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "\nUsers table exists.\n";
            
            // Get user table structure
            echo "\nUser Table Structure:\n";
            $stmt = $conn->query("DESCRIBE users");
            
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
                $columns[] = $row['Field'];
            }
            
            // Check for missing student detail columns
            $requiredStudentFields = ['student_number', 'birthdate', 'emergency_contact', 'course', 'current_year'];
            $missingFields = array_diff($requiredStudentFields, $columns);
            
            if (!empty($missingFields)) {
                echo "\nWARNING: Missing student detail fields in users table:\n";
                foreach ($missingFields as $field) {
                    echo "- " . $field . "\n";
                }
            } else {
                echo "\nAll required student detail fields exist in the users table.\n";
            }
            
            // Count users
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\nTotal users: " . $result['count'] . "\n";
            
            // Check sample user data
            $stmt = $conn->query("SELECT * FROM users LIMIT 1");
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "\nSample User Fields:\n";
                foreach ($user as $field => $value) {
                    // Don't show actual values for privacy, just field names and whether they have values
                    echo "- " . $field . ": " . (empty($value) ? "empty" : "has value") . "\n";
                }
                
                // Check for NULL values in student detail fields
                if (in_array('student_number', $columns)) {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE student_number IS NULL");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "\nUsers with NULL student_number: " . $result['count'] . "\n";
                }
            } else {
                echo "\nNo users found in the database.\n";
            }
        } else {
            echo "\nWARNING: Users table does not exist!\n";
        }
    } else {
        echo "Database connection failed!";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?> 