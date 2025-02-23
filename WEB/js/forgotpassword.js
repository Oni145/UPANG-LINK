// forgotpassword.js

// Declare the API base URL only here.
const API_BASE_URL = 'http://localhost:8000/UPANG%20LINK/';

document.getElementById('forgotPasswordForm').addEventListener('submit', async function(event) {
    event.preventDefault();

    const email = document.getElementById('email').value.trim();
    const otpInput = document.getElementById('otp');
    const newPasswordInput = document.getElementById('newPassword');
    const errorMessageDiv = document.getElementById('errorMessage');
    const submitButton = document.querySelector('.login-btn');
    const btnText = document.getElementById('btnText');

    // Clear any previous error message
    errorMessageDiv.innerText = '';

    // Check if the reset fields are visible (i.e., Step 2) or not (Step 1)
    const isResetPhase = !document.getElementById('resetFields').classList.contains('hidden');

    // Add loading class to the button
    submitButton.classList.add('loading');

    if (!isResetPhase) {
        // Step 1: Request reset token using the provided email.
        try {
            const response = await fetch(API_BASE_URL + 'admin/forgot_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Error requesting reset token');
            }
            alert('A reset token has been sent to your email. Please check your inbox.');
            // Reveal the OTP and New Password fields
            document.getElementById('resetFields').classList.remove('hidden');
            // Change the button text for the next step
            btnText.innerText = 'Reset Password';
        } catch (error) {
            errorMessageDiv.innerText = error.message;
        } finally {
            submitButton.classList.remove('loading');
        }
    } else {
        // Step 2: Reset password using provided OTP and new password.
        const token = otpInput.value.trim();
        const newPassword = newPasswordInput.value.trim();

        if (!token || !newPassword) {
            errorMessageDiv.innerText = 'Please enter both the OTP and your new password.';
            submitButton.classList.remove('loading');
            return;
        }

        try {
            const response = await fetch(API_BASE_URL + 'admin/reset_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token, new_password: newPassword })
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Error resetting password');
            }
            alert('Your password has been reset successfully. You can now log in.');
            window.location.href = 'login.html';
        } catch (error) {
            errorMessageDiv.innerText = error.message;
        } finally {
            submitButton.classList.remove('loading');
        }
    }
});
