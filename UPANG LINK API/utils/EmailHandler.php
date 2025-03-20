<?php
namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHandler {
    private $config;
    private $from_email;
    private $from_name;
    private $use_phpmailer = false;
    private $mailer = null;

    public function __construct() {
        $config_file = __DIR__ . '/../config/config.php';
        if (!file_exists($config_file)) {
            throw new \Exception('Configuration file not found');
        }
        
        $this->config = require $config_file;
        if (!is_array($this->config)) {
            throw new \Exception('Invalid configuration format');
        }
        
        $this->from_email = $this->config['email']['from_email'] ?? 'no-reply@example.com';
        $this->from_name = $this->config['email']['from_name'] ?? 'UPANG LINK';

        // Always try to use PHPMailer
        try {
            // Explicitly require the PHPMailer classes needed
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            // Create a test instance to make sure it's available
            $test = new PHPMailer(true);
            
            $this->use_phpmailer = true;
            $this->setupMailer();
            error_log("PHPMailer initialized successfully");
        } catch (\Exception $e) {
            error_log("Failed to initialize PHPMailer: " . $e->getMessage());
            error_log("PHP version: " . phpversion());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->use_phpmailer = false;
        }
    }

    private function setupMailer() {
        if (!$this->use_phpmailer) return;

        try {
            $this->mailer = new PHPMailer(true);
            
            // Debug mode
            if (isset($this->config['email']['smtp_debug'])) {
                $this->mailer->SMTPDebug = $this->config['email']['smtp_debug'];
                // Redirect debug output to error log
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug ($level): $str");
                };
            }
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['email']['host'] ?? 'smtp.gmail.com';
            $this->mailer->SMTPAuth = $this->config['email']['smtp_auth'] ?? true;
            $this->mailer->Username = $this->config['email']['username'] ?? '';
            $this->mailer->Password = $this->config['email']['password'] ?? '';
            $this->mailer->SMTPSecure = $this->config['email']['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['email']['port'] ?? 587;
            
            // Set sender
            $this->mailer->setFrom($this->from_email, $this->from_name);
            $this->mailer->isHTML(true);
            
            // Set SSL options if specified
            if (isset($this->config['email']['smtp_options'])) {
                $this->mailer->SMTPOptions = $this->config['email']['smtp_options'];
            }
            
            // Useful for Gmail which may need this
            $this->mailer->SMTPAutoTLS = true;
            
            error_log("PHPMailer configured successfully with: " .
                      "Host=" . $this->mailer->Host .
                      ", Port=" . $this->mailer->Port .
                      ", Username=" . $this->mailer->Username);
        } catch (\Exception $e) {
            error_log("Error setting up PHPMailer: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->use_phpmailer = false;
        }
    }

    public function sendVerificationEmail($to_email, $token) {
        try {
            $subject = "Verify your UPANG LINK account";
            $verification_link = $this->config['app']['frontend_url'] . "/verify-email?token=" . $token;
            
            $message = $this->getDefaultTemplate('verification', [
                'verification_link' => $verification_link,
                'app_name' => $this->config['app']['name'] ?? 'UPANG LINK'
            ]);

            return $this->send($to_email, $subject, $message);
        } catch (\Exception $e) {
            error_log('Error sending verification email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendResetPasswordEmail($to_email, $token) {
        try {
            $subject = "Reset your UPANG LINK password";
            $reset_link = $this->config['app']['base_url'] . "/UPANG-LINK/UPANG%20LINK%20API/pages/reset-password?token=" . $token;
            
            $message = $this->getDefaultTemplate('reset_password', [
                'reset_link' => $reset_link,
                'app_name' => $this->config['app']['name'] ?? 'UPANG LINK'
            ]);

            return $this->send($to_email, $subject, $message);
        } catch (\Exception $e) {
            error_log('Error sending reset password email: ' . $e->getMessage());
            return false;
        }
    }

    private function send($to_email, $subject, $message) {
        error_log("Attempting to send email to: $to_email");
        error_log("Subject: $subject");
        
        // Try sending via PHPMailer
        if ($this->use_phpmailer && $this->mailer !== null) {
            try {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($to_email);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $message;
                $this->mailer->AltBody = strip_tags(str_replace('<br>', "\n", $message));
                
                error_log("Sending email via PHPMailer...");
                $result = $this->mailer->send();
                
                if ($result) {
                    error_log("Email successfully sent via PHPMailer to $to_email");
                    return true;
                } else {
                    error_log("PHPMailer Error: " . $this->mailer->ErrorInfo);
                    // Let's try again with fallback options
                    return $this->sendWithFallback($to_email, $subject, $message);
                }
            } catch (\Exception $e) {
                error_log("PHPMailer Exception: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // Try fallback
                return $this->sendWithFallback($to_email, $subject, $message);
            }
        } else {
            return $this->sendWithFallback($to_email, $subject, $message);
        }
    }
    
    private function sendWithFallback($to_email, $subject, $message) {
        // Fallback: log and pretend success for development environment
        error_log("=== FALLBACK EMAIL (ATTEMPTED BUT NOT SENT) ===");
        error_log("To: $to_email");
        error_log("Subject: $subject");
        error_log("Body (truncated): " . substr($message, 0, 100) . "...");
        error_log("=======================================");
        
        // Look at config to see if we should pretend success in development
        $isDevelopment = ($this->config['app']['environment'] ?? 'development') === 'development';
        
        if ($isDevelopment) {
            error_log("Development mode: Simulating successful email delivery");
            return true;
        } else {
            error_log("Production mode: Email delivery failed");
            return false;
        }
    }

    private function getDefaultTemplate($template_name, $variables) {
        switch ($template_name) {
            case 'verification':
                return '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .button { 
                            display: inline-block; 
                            padding: 10px 20px; 
                            background-color: #007bff; 
                            color: white; 
                            text-decoration: none; 
                            border-radius: 5px; 
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Welcome to ' . ($variables['app_name'] ?? 'UPANG LINK') . '!</h2>
                        <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                        <p><a href="' . $variables['verification_link'] . '" class="button">Verify Email</a></p>
                        <p>Or copy and paste this link in your browser:</p>
                        <p>' . $variables['verification_link'] . '</p>
                        <p>If you did not create an account, please ignore this email.</p>
                    </div>
                </body>
                </html>';
            case 'reset_password':
                return '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .button { 
                            display: inline-block; 
                            padding: 10px 20px; 
                            background-color: #007bff; 
                            color: white; 
                            text-decoration: none; 
                            border-radius: 5px; 
                        }
                        .warning {
                            color: #dc3545;
                            font-weight: bold;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Reset Your ' . ($variables['app_name'] ?? 'UPANG LINK') . ' Password</h2>
                        <p>We received a request to reset your password. Click the button below to create a new password:</p>
                        <p><a href="' . $variables['reset_link'] . '" class="button">Reset Password</a></p>
                        <p>Or copy and paste this link in your browser:</p>
                        <p>' . $variables['reset_link'] . '</p>
                        <p class="warning">This link will expire in 1 hour.</p>
                        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                    </div>
                </body>
                </html>';
            default:
                return '';
        }
    }
} 