# Gmail SMTP Troubleshooting Guide

## Common Issues and Solutions

### 1. Authentication Failed

If you see an error like "SMTP Error: Authentication Failed" or "5.7.8 Username and Password not accepted", try these solutions:

#### Solution A: Use an App Password
If you have 2-Factor Authentication enabled on your Gmail account (recommended):

1. Go to your Google Account: https://myaccount.google.com/
2. Select "Security" from the left menu
3. Under "Signing in to Google," select "App passwords"
4. At the bottom, select "Create a new app password"
5. Choose "Other (Custom name)" and enter "UPANG LINK"
6. Click "Generate"
7. Use the generated 16-character password (without spaces) in your PHPMailer configuration
8. Update your `config.php` with this App Password

#### Solution B: Enable Less Secure Apps
If you don't have 2FA enabled (less secure and not recommended):

1. Go to https://myaccount.google.com/lesssecureapps
2. Turn on "Allow less secure apps"

### 2. Connection Issues

If you see "Connection could not be established with host smtp.gmail.com" or similar:

#### Solution:
1. Check your internet connection
2. Verify that outgoing connections to port 587 are not being blocked by a firewall
3. Try port 465 with SSL instead of port 587 with TLS:
   ```php
   $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL instead of TLS
   $mail->Port = 465; // Use port 465 instead of 587
   ```

### 3. SSL Certificate Verification Issues

If you see certificate verification errors:

#### Solution:
1. Ensure your PHP has OpenSSL support enabled
2. You can temporarily disable SSL verification (ONLY for testing, not for production):
   ```php
   $mail->SMTPOptions = [
       'ssl' => [
           'verify_peer' => false,
           'verify_peer_name' => false,
           'allow_self_signed' => true
       ]
   ];
   ```

### 4. Rate Limits and Blocks

Gmail has limits on how many emails you can send per day:

- Regular Gmail accounts: 500 emails per day
- Google Workspace accounts: 2,000 emails per day

If you exceed these limits, you may be temporarily blocked.

### 5. Testing Tools

1. Test your SMTP credentials with our test script:
   http://localhost/UPANG-LINK/UPANG%20LINK%20API/gmail-test.php

2. Check Gmail's authorization status:
   https://accounts.google.com/DisplayUnlockCaptcha

### 6. Best Practices

1. Always use App Passwords with 2FA
2. Consider using a dedicated email sending service for production (SendGrid, Mailgun, etc.)
3. Implement proper error handling and fallbacks in your code
4. Log all email sending attempts and errors for debugging

## Contact

If you're still having issues after following these steps, please contact the development team for assistance. 