/**
 * Returns common headers for authenticated requests using the provided token.
 * @param {string} token - The user token.
 * @returns {Object} The headers object.
 */
/**
 * Redirects to index.html if a token exists (used on login/signup pages).
 */
function checkTokenAndRedirect() {
    const token = localStorage.getItem('token');
    if (token) {
        window.location.href = "./index.html"; // Redirect if already logged in
    }
}

/**
 * Helper function to escape HTML to prevent XSS
 * @param {string} html - String that might contain HTML
 * @returns {string} Escaped HTML string
 */
function escapeHtml(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}

/**
 * Returns common headers for authenticated requests.
 * Ensures user is authenticated before returning headers.
 * @returns {Object} The headers object.
 */
function getAuthHeaders() {
    checkAuthAndRedirect(); // Ensure user is authenticated
    const token = localStorage.getItem('token'); // Get token
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    };
}

// ---- Usage ----
// Call `checkTokenAndRedirect();` on login/signup pages.




// ----- LOGIN FUNCTIONALITY -----
document.addEventListener('DOMContentLoaded', function() {
    // Call checkTokenAndRedirect on page load to redirect if already logged in
    checkTokenAndRedirect();
    
    // Check if we're running from a file:// URL
    if (window.location.protocol === 'file:') {
        const errorMessage = document.getElementById('errorMessage');
        if (errorMessage) {
            errorMessage.innerHTML = '<strong>Error:</strong> You are opening this file directly from your file system. ' +
                'This will not work due to browser security restrictions. <br><br>' +
                'Please access this page through a web server: <br>' +
                '<code>http://localhost/UPANG-LINK/UPANG%20LINK%20API%20WEB/WEB/login.html</code>';
            errorMessage.style.display = 'block';
            errorMessage.style.color = 'red';
            errorMessage.style.backgroundColor = '#ffeeee';
            errorMessage.style.padding = '10px';
            errorMessage.style.borderRadius = '5px';
            errorMessage.style.marginBottom = '15px';   
        }
    }
    
    // Add event listener to the login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});

async function handleLogin(event) {
    event.preventDefault(); 

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    let apiUrl = getApiUrl('admin/login');
    console.log('Attempting login at:', apiUrl);

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const rawResponse = await response.text(); // Read response as text first
        console.log('Raw API response:', rawResponse); // Log raw response

        // Check if response is HTML (it shouldn't be)
        if (rawResponse.startsWith('<')) {
            throw new Error('API returned HTML instead of JSON. Possible server error.');
        }

        const data = JSON.parse(rawResponse); // Parse JSON if valid
        console.log('Parsed JSON response:', data);

        if (!data.token) {
            throw new Error('No token received from server');
        }

        localStorage.setItem('token', data.token);
        window.location.href = 'index.html'; 

    } catch (error) {
        console.error('Login failed:', error);
        document.getElementById('errorMessage').innerText = error.message || 'Failed to connect to the server.';
    }
}
