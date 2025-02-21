<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class EmailHandler {
    private $config;
    private $mailer;
    private $from_email;
    private $from_name;

    public function __construct() {
        $config_file = __DIR__ . '/../config/config.php';
        if (!file_exists($config_file)) {
            throw new Exception('Configuration file not found');
        }
        
        $this->config = require $config_file;
        if (!is_array($this->config)) {
            throw new Exception('Invalid configuration format');
        }
        
        $this->from_email = $this->config['email']['from_email'] ?? '';
        $this->from_name = $this->config['email']['from_name'] ?? '';
        
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    private function setupMailer() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['email']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['email']['username'];
            $this->mailer->Password = $this->config['email']['password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['email']['port'];
            $this->mailer->setFrom($this->from_email, $this->from_name);
            $this->mailer->isHTML(true);
            
            // Enable debug output in development
            if (isset($this->config['app']['debug']) && $this->config['app']['debug']) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
        } catch (Exception $e) {
            error_log('Error setting up mailer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendVerificationEmail($to_email, $token) {
        try {
            $subject = "Verify your UPANG LINK account";
            $verification_link = $this->config['app']['frontend_url'] . "/verify-email?token=" . $token;
            
            $message = $this->getEmailTemplate('verification', [
                'verification_link' => $verification_link,
                'app_name' => $this->config['app']['name'] ?? 'UPANG LINK'
            ]);

            return $this->send($to_email, $subject, $message);
        } catch (Exception $e) {
            error_log('Error sending verification email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendResetPasswordEmail($to_email, $token) {
        try {
            $subject = "Reset your UPANG LINK password";
            $reset_link = $this->config['app']['frontend_url'] . "/reset-password?token=" . $token;
            
            $message = $this->getEmailTemplate('reset_password', [
                'reset_link' => $reset_link,
                'app_name' => $this->config['app']['name'] ?? 'UPANG LINK'
            ]);

            return $this->send($to_email, $subject, $message);
        } catch (Exception $e) {
            error_log('Error sending reset password email: ' . $e->getMessage());
            return false;
        }
    }

    private function send($to_email, $subject, $message) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to_email);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Error sending email: ' . $e->getMessage());
            return false;
        }
    }

    private function getEmailTemplate($template_name, $variables = []) {
        $template_path = __DIR__ . '/../templates/emails/' . $template_name . '.html';
        
        if (!file_exists($template_path)) {
            return $this->getDefaultTemplate($template_name, $variables);
        }

        $template = file_get_contents($template_path);
        
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
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