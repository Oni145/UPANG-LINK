<?php
// verify-email.php

// Include configuration and database connection files.
$config = require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

// Start session if needed.
session_start();

// Create a database connection.
$database = new Database();
$db = $database->getConnection();

// Check if the token is provided in the URL.
if (!isset($_GET['token'])) {
    echo "<html>
            <head>
              <meta charset='utf-8'>
              <title>Error</title>
              <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; text-align: center; padding: 50px; }
                .container { background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; }
                h1 { color: #D32F2F; }
                p { font-size: 16px; color: #333; }
              </style>
            </head>
            <body>
              <div class='container'>
                <h1>Error</h1>
                <p>Verification token missing.</p>
              </div>
            </body>
          </html>";
    exit;
}

$token = $_GET['token'];

// Look up the token in the database.
$stmt = $db->prepare("SELECT user_id FROM users WHERE email_verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Update the user's record to mark them as verified.
    $updateStmt = $db->prepare("UPDATE users SET is_verified = 1, email_verification_token = NULL WHERE user_id = ?");
    if ($updateStmt->execute([$user['user_id']])) {
        // Display a nicely styled HTML page confirming verification.
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Email Verified Successfully</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f5f5f5;
                    text-align: center;
                    padding: 50px;
                }
                .container {
                    background-color: #fff;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    display: inline-block;
                }
                h1 {
                    color: #4CAF50;
                }
                p {
                    font-size: 16px;
                    color: #333;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Email Verified Successfully</h1>
                <p>Your email has been successfully verified. You can now log in to your account.</p>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "<html><body><h1>Error</h1><p>Could not update verification status.</p></body></html>";
    }
} else {
    echo "<html>
            <head>
              <meta charset='utf-8'>
              <title>Error</title>
              <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; text-align: center; padding: 50px; }
                .container { background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; }
                h1 { color: #D32F2F; }
                p { font-size: 16px; color: #333; }
              </style>
            </head>
            <body>
              <div class='container'>
                <h1>Error</h1>
                <p>Invalid or expired verification token.</p>
              </div>
            </body>
          </html>";
}
?>
