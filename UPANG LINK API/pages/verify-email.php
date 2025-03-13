<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/config.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Validate token and get result immediately
$result = empty($token) ? [
    'status' => 'error',
    'message' => 'Invalid verification token.'
] : $user->verifyEmail($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - UPANG LINK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .verification-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo h2 {
            color: #1a73e8;
            font-size: 24px;
        }

        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
            font-size: 16px;
            line-height: 1.5;
        }

        .success {
            background-color: #e6f4ea;
            color: #1e4620;
            border: 1px solid #93c4aa;
        }

        .error {
            background-color: #fde7e9;
            color: #93000a;
            border: 1px solid #ffa4a9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <div class="logo">
                <h2>UPANG LINK</h2>
            </div>
            <div class="message <?php echo $result['status'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo $result['message']; ?>
            </div>
        </div>
    </div>
</body>
</html> 