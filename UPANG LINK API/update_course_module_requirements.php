<?php
require_once 'config/database.php';

// Connect to the database
try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    $requirements = [
        'fields' => [
            [
                'name' => 'student_id',
                'label' => 'Student ID Number',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter your student ID number'
            ],
            [
                'name' => 'course_code',
                'label' => 'Course Code',
                'type' => 'text',
                'required' => true,
                'description' => 'Enter the course code for the module you need'
            ],
            [
                'name' => 'year_level',
                'label' => 'Year Level',
                'type' => 'dropdown',
                'required' => true,
                'description' => 'Select your current year level',
                'options' => ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year']
            ],
            [
                'name' => 'proof_of_enrollment',
                'label' => 'Proof of Enrollment',
                'type' => 'file',
                'required' => true,
                'allowed_types' => 'pdf,jpg,png',
                'description' => 'Upload your proof of enrollment (e.g. enrollment form, registration form)'
            ]
        ],
        'instructions' => 'Please provide your student ID, course code, year level, and proof of enrollment to request course modules.'
    ];

    $sql = "UPDATE request_types SET requirements = :requirements WHERE name = 'Course Module Request'";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':requirements', json_encode($requirements), PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        echo "Successfully updated Course Module Request requirements.\n";
        
        // Verify the update
        $verify_sql = "SELECT name, requirements FROM request_types WHERE name = 'Course Module Request'";
        $verify_stmt = $db->query($verify_sql);
        $result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "\nVerified requirements for Course Module Request:\n";
            echo json_encode(json_decode($result['requirements']), JSON_PRETTY_PRINT);
        }
    } else {
        echo "Failed to update requirements.\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 