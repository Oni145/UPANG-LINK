<?php
class EmailHandler {
    private $config;
    private $from_email;
    private $from_name;

    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/config.php';
        $this->from_email = $this->config['email']['from_email'];
        $this->from_name = $this->config['email']['from_name'];
    }

    public function sendVerificationEmail($to_email, $token) {
        $subject = "Verify your UPANG LINK account";
        $verification_link = $this->config['app']['frontend_url'] . "/verify-email?token=" . $token;
        
        $message = $this->getEmailTemplate('verification', [
            'verification_link' => $verification_link,
            'app_name' => $this->config['app']['name']
        ]);

        return $this->send($to_email, $subject, $message);
    }

    private function send($to_email, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Use SMTP if configured
        if ($this->config['email']['host']) {
            return $this->sendSMTP($to_email, $subject, $message);
        }

        // Fallback to mail()
        return mail($to_email, $subject, $message, implode("\r\n", $headers));
    }

    private function sendSMTP($to_email, $subject, $message) {
        $smtp = fsockopen(
            $this->config['email']['host'],
            $this->config['email']['port'],
            $errno,
            $errstr,
            30
        );

        if (!$smtp) {
            error_log("SMTP Connection Failed: $errstr ($errno)");
            return false;
        }

        $this->getResponse($smtp);
        fwrite($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $this->getResponse($smtp);
        
        // Start TLS if port 587
        if ($this->config['email']['port'] == 587) {
            fwrite($smtp, "STARTTLS\r\n");
            $this->getResponse($smtp);
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $this->getResponse($smtp);
        }

        // Authentication
        fwrite($smtp, "AUTH LOGIN\r\n");
        $this->getResponse($smtp);
        fwrite($smtp, base64_encode($this->config['email']['username']) . "\r\n");
        $this->getResponse($smtp);
        fwrite($smtp, base64_encode($this->config['email']['password']) . "\r\n");
        $this->getResponse($smtp);

        // Send email
        fwrite($smtp, "MAIL FROM:<" . $this->from_email . ">\r\n");
        $this->getResponse($smtp);
        fwrite($smtp, "RCPT TO:<" . $to_email . ">\r\n");
        $this->getResponse($smtp);
        fwrite($smtp, "DATA\r\n");
        $this->getResponse($smtp);

        // Email headers and content
        $email_content = "Subject: " . $subject . "\r\n";
        $email_content .= "To: " . $to_email . "\r\n";
        $email_content .= "From: " . $this->from_name . " <" . $this->from_email . ">\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-type: text/html; charset=UTF-8\r\n";
        $email_content .= "\r\n" . $message . "\r\n.\r\n";

        fwrite($smtp, $email_content);
        $this->getResponse($smtp);

        // Close connection
        fwrite($smtp, "QUIT\r\n");
        fclose($smtp);

        return true;
    }

    private function getResponse($smtp) {
        $response = '';
        while ($str = fgets($smtp, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }

    private function getEmailTemplate($template_name, $variables = []) {
        $template_path = __DIR__ . '/../templates/emails/' . $template_name . '.html';
        
        if (!file_exists($template_path)) {
            return $this->getDefaultTemplate($template_name, $variables);
        }

        $template = file_get_contents($template_path);
        
        // Replace variables in template
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
                        <h2>Welcome to ' . $variables['app_name'] . '!</h2>
                        <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                        <p><a href="' . $variables['verification_link'] . '" class="button">Verify Email</a></p>
                        <p>Or copy and paste this link in your browser:</p>
                        <p>' . $variables['verification_link'] . '</p>
                        <p>If you did not create an account, please ignore this email.</p>
                    </div>
                </body>
                </html>';
            default:
                return '';
        }
    }
} 