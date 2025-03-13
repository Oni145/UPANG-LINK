<?php
require_once __DIR__ . '/config/Database.php';

try {
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Connected successfully.\n\n";

    // Add tracking_number column if it doesn't exist
    $addColumnQuery = "ALTER TABLE requests ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(20) UNIQUE NOT NULL DEFAULT ''";
    $db->exec($addColumnQuery);
    echo "Added tracking_number column.\n";

    // Update existing rows with a generated tracking number
    $updateQuery = "UPDATE requests SET tracking_number = CONCAT('REQ-', DATE_FORMAT(submitted_at, '%Y%m'), '-', LPAD(request_id, 4, '0')) WHERE tracking_number = ''";
    $db->exec($updateQuery);
    echo "Updated existing rows with tracking numbers.\n";

    // Remove the default value constraint
    $alterColumnQuery = "ALTER TABLE requests ALTER COLUMN tracking_number DROP DEFAULT";
    $db->exec($alterColumnQuery);
    echo "Removed default value constraint.\n";

    echo "\nScript completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 