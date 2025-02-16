// ----- LOGIN FUNCTIONALITY -----
document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault(); // Prevent default form submission

    // Get the form values
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    // IMPORTANT: Ensure the URL exactly matches your API's expected URL.
    // In this case, the API expects: /UPANG LINK/api/auth/admin/login
    const apiUrl = 'http://localhost:8000/UPANG%20LINK/api/auth/admin/login';

    try {
        const response = await fetch(apiUrl, {
            method: 'POST', // Only POST is used here
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });

        // If response is not OK, attempt to read the error message.
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        // Parse the JSON response
        const data = await response.json();
        console.log('Login successful:', data);

        // Store the token (adjust property name if different)
        localStorage.setItem('token', data.token);
        // Store the admin details if provided by the API.
        if (data.admin) {
            localStorage.setItem('admin', JSON.stringify(data.admin));
        }
        
        // Redirect to index.html after successful login
        window.location.href = 'index.html';

    } catch (error) {
        console.error('Login failed:', error);
        // Optionally display the error message in the UI
        document.getElementById('errorMessage').innerText = error.message;
    }
});

