<?php
// Simple Gmail SMTP Test Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load the PHPMailer classes
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$email = 'jerickogarcia0@gmail.com'; // Your Gmail address
$app_password = 'laht squw emyi ggix'; // Your Gmail App Password
$recipient = 'jerickogarcia0@gmail.com'; // Where to send the test

echo "<h1>Gmail SMTP Test</h1>";

try {
    // Create a new PHPMailer instance
    $mail = new PHPMailer();

    // Debug Mode
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "<pre>$str</pre>";
    };

    // SMTP Configuration - Gmail Specific
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth = true;
    $mail->Username = $email;
    $mail->Password = $app_password;
    
    // Disable SSL verification (for troubleshooting only)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // Set sender and recipient
    $mail->setFrom($email, 'UPANG LINK Test');
    $mail->addAddress($recipient);
    
    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Simple Gmail SMTP Test';
    $mail->Body = '<h2>This is a test email from UPANG LINK</h2><p>If you see this, Gmail SMTP is working!</p>';
    $mail->AltBody = 'This is a test email from UPANG LINK. If you see this, Gmail SMTP is working!';
    
    // Send the email
    if ($mail->send()) {
        echo "<h2 style='color:green'>Email sent successfully!</h2>";
    } else {
        echo "<h2 style='color:red'>Email could not be sent.</h2>";
        echo "<p>Error: " . $mail->ErrorInfo . "</p>";
    }
} catch (Exception $e) {
    echo "<h2 style='color:red'>An error occurred:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 