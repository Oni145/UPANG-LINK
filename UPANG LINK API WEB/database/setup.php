<?php
// Set content type to text/html for better readability in browser
header('Content-Type: text/html; charset=UTF-8');

// Include database class
include_once __DIR__ . '/../config/Database.php';

echo '<h1>UPANG LINK Database Setup</h1>';
echo '<div style="font-family: monospace; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; margin: 10px 0;">';

try {
    // Create database instance
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if connection is successful
    if (!$db) {
        throw new Exception("Database connection failed. Please check your database settings.");
    }
    
    echo "<p>✅ Connected to the database successfully.</p>";
    
    // Get SQL from schema.sql file
    $schemaPath = __DIR__ . '/schema.sql';
    
    if (!file_exists($schemaPath)) {
        echo "<p style='color: orange;'>⚠️ schema.sql not found in the database directory. Looking for it in parent directory...</p>";
        
        // Try to find it in the parent directory
        $schemaPath = __DIR__ . '/../database/schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception("schema.sql file not found. Please make sure it exists in the database directory.");
        }
        
        echo "<p style='color: green;'>✓ Found schema.sql in parent directory.</p>";
    }
    
    $sql = file_get_contents($schemaPath);
    
    // Execute each statement individually without transaction
    // This avoids issues with transactions being committed/rolled back unexpectedly
    $statements = array_filter(
        array_map('trim', 
            explode(';', $sql)
        ), 
        function($statement) { 
            return !empty($statement); 
        }
    );
    
    // Execute each statement
    foreach ($statements as $statement) {
        echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 100)) . (strlen($statement) > 100 ? '...' : '') . "</p>";
        try {
            $result = $db->exec($statement);
            echo "<p style='color: green;'>✓ Statement executed successfully</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Statement execution note: " . htmlspecialchars($e->getMessage()) . "</p>";
            // Continue execution - some errors like "table already exists" are acceptable
        }
    }
    
    echo "<p>✅ All SQL statements processed!</p>";
    
    // Check if tables exist
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    echo "<p>Tables in database:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Check if users table exists
    if (in_array('users', $tables)) {
        // Get admin users in the database
        $stmt = $db->query("SELECT user_id, email, first_name, last_name, role FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Admin users in database:</p>";
        if (count($admins) > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Role</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($admin['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['first_name']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No admin users found in the database.</p>";
            
            // Try to insert admin users directly
            echo "<p>Attempting to insert admin users directly...</p>";
            
            try {
                // Insert admin user
                $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, role, email_verified) 
                                    VALUES (:email, :password, :first_name, :last_name, 'admin', 1)");
                $stmt->bindValue(':email', 'jede.garcia.up@phinmaed.com');
                $stmt->bindValue(':password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
                $stmt->bindValue(':first_name', 'Jede');
                $stmt->bindValue(':last_name', 'Garcia');
                $stmt->execute();
                
                echo "<p style='color: green;'>✓ Admin user created.</p>";
                
                // Show the admins again
                $stmt = $db->query("SELECT user_id, email, first_name, last_name, role FROM users WHERE role = 'admin'");
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p>Updated admin users in database:</p>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Role</th></tr>";
                foreach ($admins as $admin) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($admin['user_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['first_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Error inserting admin users: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Error: Users table was not created successfully.</p>";
    }
    
    echo "<p>✅ Database setup process completed!</p>";
    echo "<p>You can now <a href='../WEB/login.html'>login</a> with the following credentials:</p>";
    echo "<ul>";
    echo "<li>Username: jede.garcia.up@phinmaed.com, Password: admin123</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo '</div>';
?> 