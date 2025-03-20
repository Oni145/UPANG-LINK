<?php
// Direct test script for PHPMailer
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load the configuration
$config = require_once __DIR__ . '/config/config.php';

// Test if file loading works
echo "<h1>Direct PHPMailer Test</h1>";
echo "<p>Config loaded: " . (is_array($config) ? "Yes" : "No") . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if the autoloader exists
$autoloader_path = __DIR__ . '/vendor/autoload.php';
echo "<p>Autoloader path: $autoloader_path</p>";
echo "<p>Autoloader exists: " . (file_exists($autoloader_path) ? "Yes" : "No") . "</p>";

// Try to require the autoloader
try {
    require_once $autoloader_path;
    echo "<p>Autoloader included successfully</p>";
} catch (Exception $e) {
    echo "<p>Error loading autoloader: " . $e->getMessage() . "</p>";
    die();
}

// Check if PHPMailer class exists
echo "<p>PHPMailer class exists: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? "Yes" : "No") . "</p>";

// Attempt to send email directly using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Output debugging info
    echo "<h2>Attempting to send email with these settings:</h2>";
    echo "<pre>";
    echo "Host: " . $config['email']['host'] . "\n";
    echo "Port: " . $config['email']['port'] . "\n";
    echo "Username: " . $config['email']['username'] . "\n";
    echo "SMTPSecure: " . $config['email']['smtp_secure'] . "\n";
    echo "</pre>";
    
    // Enable debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->Debugoutput = function($str, $level) {
        echo "<pre style='margin: 0; padding: 0;'>$str</pre>";
    };
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = $config['email']['host'];
    $mail->SMTPAuth = $config['email']['smtp_auth'];
    $mail->Username = $config['email']['username'];
    $mail->Password = $config['email']['password'];
    $mail->SMTPSecure = $config['email']['smtp_secure'];
    $mail->Port = $config['email']['port'];
    
    // Set SSL options if specified
    if (isset($config['email']['smtp_options'])) {
        $mail->SMTPOptions = $config['email']['smtp_options'];
    }
    
    // Recipients
    $mail->setFrom($config['email']['from_email'], $config['email']['from_name']);
    $mail->addAddress('jerickogarcia0@gmail.com'); // Test recipient
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'UPANG LINK - Test Email';
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <h2 style="color: #4a4a4a;">Test Email from UPANG LINK</h2>
            <p>This is a test email to verify that the email sending functionality is working correctly.</p>
            <p>If you received this, the system is working properly!</p>
            <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
        </div>';
    $mail->AltBody = 'This is a test email from UPANG LINK. If you received this, the system is working properly!';
    
    // Send the email
    echo "<h2>Sending email...</h2>";
    $result = $mail->send();
    echo "<h2>Result: " . ($result ? "Success" : "Failed") . "</h2>";
    echo "<p>Email has been sent!</p>";
    
} catch (Exception $e) {
    echo "<h2>Email Sending Failed</h2>";
    echo "<p>Error message: " . $mail->ErrorInfo . "</p>";
    echo "<p>Exception message: " . $e->getMessage() . "</p>";
    echo "<p>Exception trace:<pre>" . $e->getTraceAsString() . "</pre></p>";
}
?> 