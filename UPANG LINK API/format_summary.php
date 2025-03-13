<?php
// Simple script to show a summary of requirement formats

// Include database configuration
require_once 'config/Database.php';

// Database connection setup
try {
    // Connect to database using the connection method we know works
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all request types
    $query = "SELECT type_id, name, requirements FROM request_types ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "==========================================================\n";
    echo "REQUIREMENTS FORMAT SUMMARY\n";
    echo "==========================================================\n";
    
    $correctFormat = 0;
    $oldFormat = 0;
    $noRequirements = 0;
    
    // Check each request type
    foreach ($types as $type) {
        $name = $type['name'];
        $typeId = $type['type_id'];
        $requirements = $type['requirements'];
        
        echo "\n" . str_pad("Type ID $typeId: $name", 50, " ");
        
        // Check if requirements is valid JSON and not empty
        if (empty($requirements)) {
            echo "NO REQUIREMENTS";
            $noRequirements++;
            continue;
        }
        
        $reqJson = json_decode($requirements, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "INVALID FORMAT";
            continue;
        }
        
        // Check format
        $hasFields = isset($reqJson['fields']) && is_array($reqJson['fields']);
        $hasRequiredDocs = isset($reqJson['required_docs']) && is_array($reqJson['required_docs']);
        
        if ($hasFields) {
            $fields = $reqJson['fields'];
            $fileFields = array_filter($fields, function($field) {
                return strtolower($field['type']) === 'file';
            });
            
            $textFields = array_filter($fields, function($field) {
                return strtolower($field['type']) === 'text';
            });
            
            echo "✅ NEW FORMAT: " . count($fields) . " fields (" . 
                count($fileFields) . " file, " . count($textFields) . " text)";
            $correctFormat++;
        } else if ($hasRequiredDocs) {
            $docs = $reqJson['required_docs'];
            echo "❌ OLD FORMAT: " . count($docs) . " required docs";
            $oldFormat++;
        } else if (is_array($reqJson) && count($reqJson) === 0) {
            echo "⚠️ EMPTY OBJECT";
            $noRequirements++;
        } else {
            echo "❓ UNKNOWN FORMAT";
        }
    }
    
    // Overall summary
    echo "\n\n==========================================================\n";
    echo "TOTALS:\n";
    echo "Total request types: " . count($types) . "\n";
    echo "Using new 'fields' format: $correctFormat\n";
    echo "Using old 'required_docs' format: $oldFormat\n";
    echo "No requirements defined: $noRequirements\n";
    echo "==========================================================\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 