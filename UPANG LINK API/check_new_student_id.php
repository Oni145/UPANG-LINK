<?php
require_once 'config/Database.php';

// Open a file for writing
$outputFile = fopen('new_student_id_check.txt', 'w');

function writeOutput($message) {
    global $outputFile;
    fwrite($outputFile, $message . "\n");
    echo $message . "\n";
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    writeOutput("Connecting to database...");
    
    // Query to get the current requirements for New Student ID
    $query = "SELECT name, requirements FROM request_types WHERE name = 'New Student ID'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        writeOutput("\nCurrent Requirements for New Student ID:");
        writeOutput("-------------------------");
        writeOutput("Request Type: " . $row['name']);
        
        // Print raw requirements JSON
        writeOutput("\nRaw Requirements JSON:");
        writeOutput($row['requirements']);
        
        // Pretty print the requirements array
        writeOutput("\nParsed Requirements Array:");
        $requirements = json_decode($row['requirements'], true);
        ob_start();
        print_r($requirements);
        $output = ob_get_clean();
        writeOutput($output);
    } else {
        writeOutput("No request type found with name 'New Student ID'.");
    }
    
} catch (Exception $e) {
    writeOutput("Error: " . $e->getMessage());
}

writeOutput("\nDone!");

// Close the file
fclose($outputFile);
writeOutput("Output written to new_student_id_check.txt");
?> 