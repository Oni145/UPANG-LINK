<?php
require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT requirements FROM request_types WHERE name = 'Transcript of Records'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $output = "";
    if ($result) {
        $requirements = json_decode($result['requirements'], true);
        $output .= "Current Transcript of Records requirements:\n\n";
        $output .= json_encode($requirements, JSON_PRETTY_PRINT);
    } else {
        $output .= "Transcript of Records request type not found.";
    }
    
    // Write to file
    file_put_contents('transcript_requirements.txt', $output);
    echo "Requirements written to transcript_requirements.txt";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 