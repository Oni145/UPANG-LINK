<?php
session_start();

// Get the token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UPANG LINK</title>
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

            <form id="resetPasswordForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>

            <div class="text-center">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const token = document.querySelector('input[name="token"]').value;
            
            // Reset messages
            document.getElementById('error-message').style.display = 'none';
            document.getElementById('success-message').style.display = 'none';
            
            // Validate passwords match
            if (password !== confirmPassword) {
                document.getElementById('error-message').textContent = 'Passwords do not match';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            // Send reset request to API
            fetch('http://192.168.1.7/UPANG-LINK/UPANG%20LINK%20API/api/auth/student/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    password: password
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.status === 'success') {
                    document.getElementById('resetPasswordForm').style.display = 'none';
                    document.getElementById('success-message').innerHTML = 'Password has been reset successfully! <br><br>' +
                        '<a href="login.php" class="btn">Go to Login</a>';
                    document.getElementById('success-message').style.display = 'block';
                } else {
                    throw new Error(data.message || 'Failed to reset password');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('error-message').textContent = error.message;
                document.getElementById('error-message').style.display = 'block';
            });
        });
    </script>
</body>
</html>
 