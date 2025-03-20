<?php
// Gmail SSL Test Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h1>Gmail SSL Test (Port 465)</h1>";
echo "<p>Testing connection using SSL on port 465 instead of TLS on port 587</p>";

// Email credentials - same as in your config
$email = 'jerickogarcia0@gmail.com';
$app_password = 'laht squw emyi ggix'; // This should be an App Password from Google
$recipient = $email; // Sending to yourself for testing

try {
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "<pre style='margin:0;padding:0;'>$str</pre>";
    };
    
    // Server settings - using SSL instead of TLS
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $email;
    $mail->Password = $app_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Using SSL instead of TLS
    $mail->Port = 465; // Port for SSL
    
    // Disable certificate verification for testing
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    // Set timeout to 30 seconds
    $mail->Timeout = 30;
    
    // Recipients
    $mail->setFrom($email, 'UPANG LINK Test');
    $mail->addAddress($recipient);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Gmail SSL Test';
    $mail->Body = '<h2>This is a test email using SSL on port 465</h2><p>If you see this, Gmail SSL connection is working!</p>';
    
    // Send the email
    if ($mail->send()) {
        echo "<h2 style='color:green'>Email sent successfully using SSL!</h2>";
    } else {
        echo "<h2 style='color:red'>Email could not be sent.</h2>";
        echo "<p>Error: " . $mail->ErrorInfo . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>An error occurred:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

// Display helpful information
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If this test was successful, update your config.php to use SSL on port 465</li>";
echo "<li>If it failed, check our <a href='gmail-troubleshooting.md'>Gmail Troubleshooting Guide</a></li>";
echo "<li><a href='test-phpmailer.php'>Go back to the main test page</a></li>";
echo "</ol>";
?> 