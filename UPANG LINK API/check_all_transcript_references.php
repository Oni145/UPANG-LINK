<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Check all tables that might contain Transcript of Records requirements
    $tables = [
        'request_types',
        'requirements',
        'document_types',
        'request_templates',
        'request_configs',
        'app_settings'
    ];

    foreach ($tables as $table) {
        // Check if table exists
        $checkTableQuery = "SHOW TABLES LIKE '$table'";
        $checkTableStmt = $db->prepare($checkTableQuery);
        $checkTableStmt->execute();
        
        if ($checkTableStmt->rowCount() > 0) {
            echo "Checking table: $table\n";
            
            // Get all columns in the table
            $columnsQuery = "SHOW COLUMNS FROM $table";
            $columnsStmt = $db->prepare($columnsQuery);
            $columnsStmt->execute();
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Build a query to search for Transcript of Records in all text/json columns
            $searchConditions = [];
            foreach ($columns as $column) {
                $searchConditions[] = "$column LIKE '%Transcript of Records%'";
            }
            
            if (!empty($searchConditions)) {
                $searchQuery = "SELECT * FROM $table WHERE " . implode(" OR ", $searchConditions);
                $searchStmt = $db->prepare($searchQuery);
                $searchStmt->execute();
                $results = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($results) > 0) {
                    echo "  Found " . count($results) . " matching rows\n";
                    foreach ($results as $row) {
                        echo "  Row details:\n";
                        foreach ($row as $key => $value) {
                            if (strpos($value, 'Transcript of Records') !== false) {
                                echo "    $key: ";
                                
                                // Try to decode JSON values for better readability
                                $jsonValue = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    echo "JSON value containing Transcript of Records\n";
                                    
                                    // If it's a requirements field, check for student_id fields
                                    if (isset($jsonValue['fields'])) {
                                        echo "    Fields found:\n";
                                        foreach ($jsonValue['fields'] as $field) {
                                            echo "      - " . $field['name'] . " (" . $field['label'] . "): " . $field['type'] . "\n";
                                        }
                                    }
                                } else {
                                    echo $value . "\n";
                                }
                            }
                        }
                        echo "\n";
                    }
                } else {
                    echo "  No matching rows found\n";
                }
            }
        } else {
            echo "Table $table does not exist, skipping\n";
        }
        
        echo "\n";
    }
    
    echo "Script completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 