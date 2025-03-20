<?php
session_start();

// Get the token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

// Load config
$config = require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UPANG LINK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .reset-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h2 {
            color: #1a73e8;
            font-size: 24px;
        }

        h4 {
            text-align: center;
            margin-bottom: 20px;
            color: #202124;
            font-size: 18px;
        }

        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }

        .alert-danger {
            background-color: #fde7e9;
            color: #93000a;
            border: 1px solid #ffa4a9;
        }

        .alert-success {
            background-color: #e6f4ea;
            color: #1e4620;
            border: 1px solid #93c4aa;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #202124;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            background-color: #1557b0;
        }

        .btn:active {
            background-color: #174ea6;
        }

        .text-center {
            text-align: center;
            margin-top: 20px;
        }

        .text-center a {
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
        }

        .text-center a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            font-size: 12px;
            color: #5f6368;
            margin-top: 5px;
        }

        #loading {
            display: none;
            text-align: center;
            margin-bottom: 15px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #1a73e8;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .password-input-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            color: #5f6368;
            padding: 5px;
        }

        .password-toggle:hover {
            color: #1a73e8;
        }

        .form-control {
            padding-right: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-form">
            <div class="logo">
                <h2>UPANG LINK</h2>
            </div>
            <h4>Reset Your Password</h4>
            
            <div id="error-message" class="alert alert-danger"></div>
            <div id="success-message" class="alert alert-success"></div>
            <div id="loading">
                <div class="spinner"></div>
                <p>Processing your request...</p>
            </div>

            <form id="resetPasswordForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <div class="password-input-container">
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-requirements">
                        Password must be at least 8 characters long
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <div class="password-input-container">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
        // Function to toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Function to validate token on page load
        function validateTokenOnLoad() {
            const token = document.querySelector('input[name="token"]').value;
            const form = document.getElementById('resetPasswordForm');
            const loading = document.getElementById('loading');
            
            loading.style.display = 'block';
            form.style.display = 'none';

            fetch('<?php echo $config['app']['api_url']; ?>/api/auth/student/validate-reset-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token: token })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.status === 'success') {
                    form.style.display = 'block';
                } else {
                    document.getElementById('error-message').textContent = 'This password reset link is invalid or has expired. Please request a new one.';
                    document.getElementById('error-message').style.display = 'block';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                document.getElementById('error-message').textContent = 'This password reset link is invalid or has expired. Please request a new one.';
                document.getElementById('error-message').style.display = 'block';
            });
        }

        // Call token validation on page load
        validateTokenOnLoad();

        // Form submission handler
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const token = document.querySelector('input[name="token"]').value;
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            
            // Reset messages
            document.getElementById('error-message').style.display = 'none';
            document.getElementById('success-message').style.display = 'none';
            
            // Validate password length
            if (password.length < 8) {
                document.getElementById('error-message').textContent = 'Password must be at least 8 characters long';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            // Validate passwords match
            if (password !== confirmPassword) {
                document.getElementById('error-message').textContent = 'Passwords do not match';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            loading.style.display = 'block';
            
            // Send reset request to API
            fetch('<?php echo $config['app']['api_url']; ?>/api/auth/student/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.status === 'success') {
                    document.getElementById('resetPasswordForm').style.display = 'none';
                    document.getElementById('success-message').innerHTML = 'Your password has been reset successfully!';
                    document.getElementById('success-message').style.display = 'block';
                } else {
                    // Handle specific error cases
                    let errorMessage = data.message;
                    if (data.message.includes('expired') || data.message.includes('invalid')) {
                        errorMessage = 'This password reset link is invalid or has expired. Please request a new one.';
                    }
                    document.getElementById('error-message').textContent = errorMessage;
                    document.getElementById('error-message').style.display = 'block';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                document.getElementById('error-message').textContent = 'An error occurred. Please try again later.';
                document.getElementById('error-message').style.display = 'block';
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
 