<?php
// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$db_name = 'upang_link';
$username = 'root';
$password = '';

// Function to execute SQL file
function executeSqlFile($pdo, $file) {
    echo "<p>Executing SQL file: $file</p>";
    
    if (!file_exists($file)) {
        echo "<p style='color: red;'>Error: File $file does not exist!</p>";
        return false;
    }
    
    $sql = file_get_contents($file);
    
    // Split SQL by delimiter
    $queries = explode(';', $sql);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;
            
            // Handle DELIMITER statements for triggers
            if (stripos($query, 'DELIMITER //') !== false) {
                $triggerParts = explode('DELIMITER //', $query);
                if (isset($triggerParts[1])) {
                    $triggerBody = explode('DELIMITER ;', $triggerParts[1])[0];
                    $triggerBody = str_replace('END //', 'END', $triggerBody);
                    $pdo->exec($triggerBody);
                    echo "<p style='color: green;'>Executed trigger successfully</p>";
                }
            } else {
                $pdo->exec($query);
                echo "<p style='color: green;'>Executed query successfully</p>";
            }
        }
        
        // Commit transaction
        $pdo->commit();
        echo "<p style='color: green;'>All queries executed successfully!</p>";
        return true;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo "<p style='color: red;'>Error executing SQL: " . $e->getMessage() . "</p>";
        return false;
    }
}

// HTML header
echo "<!DOCTYPE html>
<html>
<head>
    <title>UPANG-LINK Database Integration Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>UPANG-LINK Database Integration Setup</h1>";

try {
    // Connect to MySQL server (without selecting a database)
    echo "<div class='step'>";
    echo "<h2>Step 1: Connecting to MySQL Server</h2>";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>Connected to MySQL server successfully!</p>";
    echo "</div>";
    
    // Create database if it doesn't exist
    echo "<div class='step'>";
    echo "<h2>Step 2: Creating Database</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo "<p class='success'>Database '$db_name' created or already exists.</p>";
    echo "</div>";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Execute schema.sql from mobile API if it exists
    echo "<div class='step'>";
    echo "<h2>Step 3: Setting up Mobile API Schema</h2>";
    $schemaFile = '../UPANG LINK API/database/schema.sql';
    if (file_exists($schemaFile)) {
        executeSqlFile($pdo, $schemaFile);
    } else {
        echo "<p class='error'>Warning: Mobile API schema file not found at '$schemaFile'.</p>";
    }
    echo "</div>";
    
    // Execute setup.sql for admin tables
    echo "<div class='step'>";
    echo "<h2>Step 4: Setting up Admin Tables</h2>";
    $setupFile = 'database/setup.sql';
    if (file_exists($setupFile)) {
        executeSqlFile($pdo, $setupFile);
    } else {
        echo "<p class='error'>Warning: Admin tables setup file not found at '$setupFile'.</p>";
    }
    echo "</div>";
    
    // Execute integration.sql
    echo "<div class='step'>";
    echo "<h2>Step 5: Running Integration Script</h2>";
    $integrationFile = 'database/integration.sql';
    if (file_exists($integrationFile)) {
        executeSqlFile($pdo, $integrationFile);
    } else {
        echo "<p class='error'>Error: Integration file not found at '$integrationFile'.</p>";
    }
    echo "</div>";
    
    // Check if tables exist
    echo "<div class='step'>";
    echo "<h2>Step 6: Verifying Database Tables</h2>";
    $tables = [
        'users', 'user_sessions', 'categories', 'request_types', 
        'requests', 'request_notes', 'required_documents', 
        'notifications', 'request_files', 'admins', 
        'admin_tokens', 'admin_notifications'
    ];
    
    echo "<p>Checking for required tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<li class='success'>Table '$table' exists.</li>";
        } else {
            echo "<li class='error'>Table '$table' does not exist!</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
    
    // Final message
    echo "<div class='step'>";
    echo "<h2>Setup Complete</h2>";
    echo "<p class='success'>Database integration setup is complete. You can now use the UPANG-LINK system.</p>";
    echo "<p>Next steps:</p>";
    echo "<ol>";
    echo "<li>Access the Web Dashboard: <a href='WEB/login.html'>Login Page</a></li>";
    echo "<li>Test the Mobile API endpoints</li>";
    echo "<li>Run the Mobile App</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step'>";
    echo "<h2>Error</h2>";
    echo "<p class='error'>Database Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// HTML footer
echo "</body></html>";
?> 