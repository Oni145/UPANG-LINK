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
    event.preventDefault(); // Prevent default form submission

    // Get the form values
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    // Display loading state
    const loginButton = document.querySelector('.login-btn');
    loginButton.classList.add('loading');
    
    // Clear previous error messages
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.innerText = '';
    }

    // Set the API endpoint to admin login only
    let apiUrl = getApiUrl('admin/login');
    
    console.log('Attempting login to:', apiUrl);
    console.log('API endpoint path:', '/admin/login');

    try {
        console.log('Sending login request with data:', { username, password: '********' });
        
        // Create a new XMLHttpRequest object
        const xhr = new XMLHttpRequest();
        xhr.open('POST', apiUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        
        // Set up a timeout
        xhr.timeout = 10000; // 10 seconds
        
        // Set up event handlers
        xhr.onload = function() {
            // Store the raw response for debugging
            const rawResponse = xhr.responseText;
            console.log('Raw response:', rawResponse);
            
            if (xhr.status >= 200 && xhr.status < 300) {
                // Success
                try {
                    const data = JSON.parse(xhr.responseText);
                    console.log('Login successful:', data);
                    
                    // Store the token (adjust property name if different)
                    localStorage.setItem('token', data.token);
                    // Store admin details if provided by the API.
                    if (data.admin) {
                        localStorage.setItem('admin', JSON.stringify(data.admin));
                    }
                    
                    // Redirect to index.html after successful login
                    window.location.href = 'index.html';
                } catch (error) {
                    console.error('Error parsing response:', error);
                    if (errorMessage) {
                        errorMessage.innerHTML = 'Error parsing server response<br><br>' +
                            '<details><summary>Raw Response (click to expand)</summary>' +
                            '<pre style="background-color: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">' + 
                            escapeHtml(rawResponse) + 
                            '</pre></details>';
                    }
                }
            } else {
                // Error
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    console.error('Error response:', errorData);
                    
                    // Log detailed debug information if available
                    if (errorData.debug) {
                        console.error('Debug info:', errorData.debug);
                    }
                    
                    if (errorMessage) {
                        if (errorData.message) {
                            errorMessage.innerText = errorData.message;
                        } else {
                            errorMessage.innerText = `HTTP error! status: ${xhr.status}`;
                        }
                        
                        // Add debug information to the error message if available
                        if (errorData.debug) {
                            const debugInfo = document.createElement('div');
                            debugInfo.style.marginTop = '10px';
                            debugInfo.style.fontSize = '12px';
                            debugInfo.style.color = '#666';
                            debugInfo.innerHTML = '<strong>Debug Info:</strong><br>' + 
                                                 'Request URI: ' + (errorData.debug.request_uri || 'N/A') + '<br>' +
                                                 'Endpoint: ' + (errorData.debug.endpoint || 'N/A');
                            errorMessage.appendChild(debugInfo);
                        }
                    }
                } catch (error) {
                    console.error('Error parsing error response:', error);
                    if (errorMessage) {
                        errorMessage.innerHTML = `HTTP error! status: ${xhr.status}<br><br>` +
                            '<details><summary>Raw Response (click to expand)</summary>' +
                            '<pre style="background-color: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">' + 
                            escapeHtml(rawResponse) + 
                            '</pre></details>';
                    }
                }
            }
            
            // Remove loading state
            loginButton.classList.remove('loading');
        };
        
        xhr.onerror = function() {
            console.error('Network error occurred');
            if (errorMessage) {
                errorMessage.innerText = 'Network error occurred. Please check your connection.';
            }
            loginButton.classList.remove('loading');
        };
        
        xhr.ontimeout = function() {
            console.error('Request timed out');
            if (errorMessage) {
                errorMessage.innerText = 'Request timed out. Please try again later.';
            }
            loginButton.classList.remove('loading');
        };
        
        // Send the request
        xhr.send(JSON.stringify({ username, password }));
        
    } catch (error) {
        console.error('Login failed:', error);
        // Optionally display the error message in the UI
        if (errorMessage) {
            errorMessage.innerText = error.message || 'Failed to connect to the server. Please check your network connection.';
        }
        loginButton.classList.remove('loading');
    }
}
