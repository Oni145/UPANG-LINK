<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create request_files table if not exists
    $query = "CREATE TABLE IF NOT EXISTS request_files (
        id INT PRIMARY KEY AUTO_INCREMENT,
        request_id INT NOT NULL,
        field_name VARCHAR(100) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($query);
    
    echo "request_files table created successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 