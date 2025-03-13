<?php
require_once 'config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Define the requirements in the format expected by the Android app
    $requirements = json_encode([
        'fields' => [
            [
                'name' => 'year_level',
                'label' => 'Year Level',
                'type' => 'dropdown',
                'required' => true,
                'description' => 'Select your year level',
                'options' => ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year']
            ],
            [
                'name' => 'supporting_document',
                'label' => 'Supporting Document',
                'type' => 'file',
                'required' => true,
                'allowed_types' => 'pdf,jpg,png',
                'description' => 'Upload your original receipt of enrollment or schedule sheet'
            ],
            [
                'name' => 'course_code',
                'label' => 'Course Code',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter the code of the course for which you need the module'
            ]
        ],
        'instructions' => 'Please select your year level, upload a supporting document (enrollment receipt or schedule sheet), and enter the course code for the module request.'
    ]);

    $query = "UPDATE request_types SET requirements = ? WHERE name = 'Course Module Request'";
    $stmt = $db->prepare($query);
    $stmt->execute([$requirements]);

    echo "Updated Course Module Request requirements successfully!\n";
    
    // Verify the update
    $verify = $db->query("SELECT requirements FROM request_types WHERE name = 'Course Module Request'");
    $result = $verify->fetch(PDO::FETCH_ASSOC);
    echo "\nNew requirements:\n";
    echo $result['requirements'];

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 