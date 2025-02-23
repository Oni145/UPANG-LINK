<?php
// Include Composer's autoloader (adjust the path if necessary)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';      
    $mail->SMTPAuth   = true;
    $mail->Username   = 'librariansystem1@gmail.com'; 
    $mail->Password   = 'fyii qywz sobr wfks';      
    $mail->SMTPSecure = 'TLS';                  
    $mail->Port       = 587;                     

    //Recipients
    $mail->setFrom(' librariansystem1@gmail.com', 'UPANG ADMIN');
    $mail->addAddress(' librariansystem1@gmail.com', 'Oni'); // Recipient's email.

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test Email';
    $mail->Body    = '<h1>This is a test email</h1><p>If you see this, PHPMailer is working!</p>';
    $mail->AltBody = 'This is a test email.';

    $mail->send();
    echo "Test email sent successfully!";
} catch (Exception $e) {
    echo "PHPMailer Error: " . $mail->ErrorInfo;
}
?>
