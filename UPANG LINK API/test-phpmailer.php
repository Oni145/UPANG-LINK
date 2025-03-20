<?php
// PHPMailer Direct Test Script
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/phpmailer-error.log');

// Load the PHPMailer classes
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load configuration
$config = require __DIR__ . '/config/config.php';

echo "<h1>Direct PHPMailer Test</h1>";
echo "<p>Testing direct PHPMailer integration without EmailHandler class</p>";

// Output system info
echo "<h2>System Information</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>OS: " . PHP_OS . "</li>";
echo "<li>Extensions: " . implode(', ', get_loaded_extensions()) . "</li>";
echo "</ul>";

// Display configuration (without password)
echo "<h2>Email Configuration</h2>";
echo "<ul>";
echo "<li>SMTP Host: " . $config['email']['host'] . "</li>";
echo "<li>SMTP Port: " . $config['email']['port'] . "</li>";
echo "<li>SMTP Auth: " . ($config['email']['smtp_auth'] ? 'Yes' : 'No') . "</li>";
echo "<li>SMTP Secure: " . $config['email']['smtp_secure'] . "</li>";
echo "<li>Username: " . $config['email']['username'] . "</li>";
echo "<li>From Email: " . $config['email']['from_email'] . "</li>";
echo "<li>From Name: " . $config['email']['from_name'] . "</li>";
echo "</ul>";

try {
    // Create a new PHPMailer instance
    echo "<h2>Initializing PHPMailer</h2>";
    
    $mail = new PHPMailer(true);
    echo "<p>PHPMailer class loaded successfully.</p>";
    
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // or SMTP::DEBUG_CONNECTION for even more detail
    $mail->Debugoutput = function($str, $level) {
        echo "<pre style='margin:0;padding:0;'>$str</pre>";
        error_log("PHPMailer Debug ($level): $str");
    };
    
    // Server settings
    echo "<h2>Configuring Server Settings</h2>";
    $mail->isSMTP();
    echo "<p>Using SMTP protocol</p>";
    
    $mail->Host = $config['email']['host'];
    $mail->SMTPAuth = $config['email']['smtp_auth'];
    $mail->Username = $config['email']['username'];
    $mail->Password = $config['email']['password'];
    $mail->SMTPSecure = $config['email']['smtp_secure'];
    $mail->Port = $config['email']['port'];
    
    // SSL Options
    if (isset($config['email']['smtp_options'])) {
        $mail->SMTPOptions = $config['email']['smtp_options'];
        echo "<p>Using custom SSL options: verify_peer=" . 
             ($config['email']['smtp_options']['ssl']['verify_peer'] ? 'true' : 'false') . 
             ", verify_peer_name=" . 
             ($config['email']['smtp_options']['ssl']['verify_peer_name'] ? 'true' : 'false') . 
             "</p>";
    }
    
    // Recipients
    echo "<h2>Setting Up Email Content</h2>";
    $mail->setFrom($config['email']['from_email'], $config['email']['from_name']);
    $mail->addAddress('jerickogarcia0@gmail.com'); // Add a recipient (use the same email for testing)
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test Email from UPANG LINK API';
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #4a4a4a;">PHPMailer Test Email</h2>
        <p>If you\'re seeing this, the PHPMailer configuration is working correctly!</p>
        <p>This test was sent at: ' . date('Y-m-d H:i:s') . '</p>
    </div>';
    $mail->AltBody = 'This is a test email from UPANG LINK. If PHPMailer is working, you\'ll receive this.';
    
    // Send the email
    echo "<h2>Attempting to Send Email...</h2>";
    $mail->send();
    echo "<p style='color:green;font-weight:bold;'>Message has been sent successfully!</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red;'>Message could not be sent</h2>";
    echo "<p>Mailer Error: " . $mail->ErrorInfo . "</p>";
    echo "<p>Exception: " . $e->getMessage() . "</p>";
    echo "<h3>Error Details:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
    // Log detailed error info
    error_log("PHPMailer Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

echo "<p><a href='test-email.php'>Go back to the simple test page</a></p>";
?> 