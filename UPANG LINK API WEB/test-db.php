<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include database class
include_once __DIR__ . '/config/Database.php';

try {
    // Create database instance
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if connection is successful
    if ($db) {
        // Try a simple query to check if tables exist
        $tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Check if admin and admin_tokens tables exist
        $adminTableExists = in_array('admins', $tables);
        $adminTokensTableExists = in_array('admin_tokens', $tables);
        
        // Return success response with tables info
        echo json_encode([
            'status' => 'success',
            'message' => 'Database connection successful',
            'database' => [
                'name' => 'upang_link',
                'tables' => $tables,
                'adminTableExists' => $adminTableExists,
                'adminTokensTableExists' => $adminTokensTableExists
            ]
        ]);
    } else {
        // Return connection error
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
    }
} catch (Exception $e) {
    // Return error if any exception occurs
    echo json_encode([
        'status' => 'error',
        'message' => 'Database test failed: ' . $e->getMessage()
    ]);
}
?> 