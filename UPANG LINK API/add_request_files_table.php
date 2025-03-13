<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Create request_files table
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS request_files (
        file_id INT PRIMARY KEY AUTO_INCREMENT,
        request_id INT NOT NULL,
        field_name VARCHAR(100) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        file_size INT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
    )";
    
    $db->exec($createTableQuery);
    echo "Created request_files table.\n";

    // Verify the table was created
    $verifyQuery = "SHOW TABLES LIKE 'request_files'";
    $stmt = $db->prepare($verifyQuery);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Verified: request_files table exists.\n";
        
        // Show table structure
        $describeQuery = "DESCRIBE request_files";
        $stmt = $db->prepare($describeQuery);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTable structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "Error: Table was not created successfully.\n";
    }

    echo "\nScript completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 