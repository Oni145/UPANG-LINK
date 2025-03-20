<?php
// Database connection
require_once 'UPANG LINK API/config/config.php';
require_once 'UPANG LINK API/database/Database.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Token to check
$token = '405086ae80cabcccf324aa077e809d625f1717f9942548a7';

// Check the token in the user table
$query = "SELECT user_id, email, reset_token_expiry FROM users WHERE reset_password_token = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $token);
$stmt->execute();

echo "<h1>Token Check Results</h1>";
echo "<p>Checking token: " . $token . "</p>";

if($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Token found for user ID: " . $row['user_id'] . " (Email: " . $row['email'] . ")</p>";
    
    $expiry = $row['reset_token_expiry'];
    $current_time = date('Y-m-d H:i:s');
    
    echo "<p>Token expiry: " . $expiry . "</p>";
    echo "<p>Current time: " . $current_time . "</p>";
    
    if(strtotime($expiry) < time()) {
        echo "<p style='color: red;'>Token has expired!</p>";
    } else {
        echo "<p style='color: green;'>Token is still valid.</p>";
    }
} else {
    echo "<p style='color: red;'>No user found with this token!</p>";
    
    // Check if any token exists in the database
    $query = "SELECT user_id, email, reset_password_token, reset_token_expiry FROM users WHERE reset_password_token IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<h2>Available Reset Tokens:</h2>";
    
    if($stmt->rowCount() > 0) {
        echo "<ul>";
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>User ID: " . $row['user_id'] . " (Email: " . $row['email'] . ")<br>";
            echo "Token: " . $row['reset_password_token'] . "<br>";
            echo "Expires: " . $row['reset_token_expiry'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No reset tokens found in the database.</p>";
    }
}
?> 