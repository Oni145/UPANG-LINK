<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Add original_name column if it doesn't exist
    $alterTableQuery = "
    ALTER TABLE request_files 
    ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) NOT NULL AFTER field_name,
    MODIFY COLUMN file_name VARCHAR(255) NOT NULL COMMENT 'System generated filename',
    MODIFY COLUMN original_name VARCHAR(255) NOT NULL COMMENT 'Original filename from user'";
    
    $db->exec($alterTableQuery);
    echo "Added original_name column to request_files table.\n";

    // Verify the table structure
    $describeQuery = "DESCRIBE request_files";
    $stmt = $db->prepare($describeQuery);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUpdated table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}";
        if (!empty($column['Comment'])) {
            echo " ({$column['Comment']})";
        }
        echo "\n";
    }

    echo "\nScript completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 