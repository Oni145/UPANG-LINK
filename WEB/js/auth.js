// ----- COMMON CONSTANTS AND HELPER FUNCTIONS -----
const API_BASE_URL = 'http://localhost:8000/UPANG%20LINK/api/';

/**
 * Returns common headers for authenticated requests using the provided token.
 * @param {string} token - The user token.
 * @returns {Object} The headers object.
 */
function getAuthHeaders(token) {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    };
}

// ----- LOGIN FUNCTIONALITY -----
document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault(); // Prevent default form submission

    // Get the form values
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const accountType = document.getElementById('accountType').value; // New account selector value

    // Set the API endpoint based on the selected account type
    let apiUrl = API_BASE_URL + (accountType === 'staff' ? 'staff/login' : 'admin/login');

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
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
        // Store user details (admin or staff) if provided by the API.
        if (data.admin) {
            localStorage.setItem('admin', JSON.stringify(data.admin));
        } else if (data.staff) {
            localStorage.setItem('staff', JSON.stringify(data.staff));
        }
        
        // Redirect to index.html after successful login
        window.location.href = 'index.html';

    } catch (error) {
        console.error('Login failed:', error);
        // Optionally display the error message in the UI
        document.getElementById('errorMessage').innerText = error.message;
    }
});

document.getElementById('loginForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const loginButton = document.querySelector('.login-btn');
    loginButton.classList.add('loading'); // Add the loading class to show the spinner and hide text

    // Simulate an API call with a timeout (You can replace this with your actual login process)
    setTimeout(() => {
      // After the login action (e.g., API call), you can remove the loading class
      loginButton.classList.remove('loading');
      
      // Handle login logic here, such as redirecting the user or showing an error
    }, 3000); // Simulate 3 seconds delay
  });
