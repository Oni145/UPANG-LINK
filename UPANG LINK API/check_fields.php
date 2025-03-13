<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n";
    
    $query = "SELECT name, requirements FROM request_types WHERE name = 'ID Replacement'";
    $stmt = $db->prepare($query);
    
    echo "Executing query...\n";
    $stmt->execute();
    echo "Query executed.\n";
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "\nFound ID Replacement type.\n";
        $requirements = json_decode($row['requirements'], true);
        
        if (isset($requirements['fields'])) {
            echo "\nFields for ID Replacement:\n";
            echo "-------------------------\n";
            foreach ($requirements['fields'] as $index => $field) {
                echo ($index + 1) . ". " . $field['name'] . " (" . $field['label'] . ")\n";
                echo "   Type: " . $field['type'] . "\n";
                echo "   Required: " . ($field['required'] ? 'Yes' : 'No') . "\n";
                
                if (isset($field['description'])) {
                    echo "   Description: " . $field['description'] . "\n";
                }
                if (isset($field['allowed_types'])) {
                    echo "   Allowed Types: " . $field['allowed_types'] . "\n";
                }
                echo "\n";
            }
        } else {
            echo "No fields defined in requirements.\n";
            echo "Raw requirements data: " . print_r($requirements, true) . "\n";
        }
    } else {
        echo "ID Replacement type not found in database.\n";
        
        // List all request types for debugging
        echo "\nAvailable request types:\n";
        $allTypesQuery = "SELECT name FROM request_types";
        $allTypesStmt = $db->query($allTypesQuery);
        while ($type = $allTypesStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $type['name'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 