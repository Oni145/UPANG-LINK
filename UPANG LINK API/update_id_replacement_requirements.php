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
                'name' => 'affidavit_loss',
                'label' => 'Affidavit of Loss',
                'type' => 'file',
                'required' => true,
                'allowed_types' => 'pdf',
                'description' => 'Notarized affidavit of loss'
            ],
            [
                'name' => 'id_picture',
                'label' => '1x1 ID Picture',
                'type' => 'file',
                'required' => true,
                'allowed_types' => 'jpg,png',
                'description' => 'Recent 1x1 ID picture with white background'
            ]
        ],
        'instructions' => 'Please submit your Student ID number, Affidavit of Loss (PDF), and 1x1 ID Picture (JPG/PNG).'
    ];

    $sql = "UPDATE request_types SET requirements = :requirements WHERE name = 'ID Replacement'";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':requirements', json_encode($requirements), PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        echo "Successfully updated ID Replacement requirements.\n";
        
        // Verify the update
        $verify_sql = "SELECT name, requirements FROM request_types WHERE name = 'ID Replacement'";
        $verify_stmt = $db->query($verify_sql);
        $result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "\nVerified requirements for ID Replacement:\n";
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