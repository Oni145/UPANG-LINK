// ----- LOGIN FUNCTIONALITY -----
document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault(); // Prevent default form submission

    // Get the form values
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    // IMPORTANT: Ensure the URL exactly matches your API's expected URL.
    // In this case, the API expects: /UPANG LINK/api/auth/admin/login
    const apiUrl = 'http://localhost:8000/UPANG%20LINK/api/admin/login';

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

const auth = {
    /**
     * Logs out the current admin by calling the logout API,
     * removes stored tokens, and redirects to the login page.
     */
    logout: async function() {
        console.log("Attempting logout...");
        // Show the loading screen before proceeding with the logout
        const dashboard = new Dashboard(); // Create a new Dashboard instance to access the showLoading method
        dashboard.showLoading();

        if (localStorage.getItem('token')) {
            try {
                const response = await fetch(`${API_BASE_URL}/admin/logout`, {
                    method: 'POST',
                    headers: getAuthHeaders(localStorage.getItem('token'))
                });
                const result = await response.json();
                if (result.status === 'success') {
                    console.log('Logout successful:', result.message);
                } else {
                    console.error('Logout failed:', result.message);
                }
            } catch (error) {
                console.error('Error during logout:', error);
            }
        }
        // Remove the stored token and user data
        localStorage.removeItem('token');
        localStorage.removeItem('user');

        // After the logout is processed, redirect to the login page
        window.location.href = 'login.html';
    }
};
