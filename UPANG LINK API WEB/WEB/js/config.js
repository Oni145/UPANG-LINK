// Define the base URL for API requests
// Handle both file:// and http:// protocols
let API_BASE_URL;

// Check if we're running from a file:// URL or from a web server
if (window.location.protocol === 'file:') {
    // If running from file://, use a hardcoded localhost URL
    API_BASE_URL = 'http://localhost/UPANG-LINK/UPANG%20LINK%20API%20WEB/api';
} else {
    // If running from a web server, use the origin
    API_BASE_URL = window.location.origin + '/UPANG-LINK/UPANG%20LINK%20API%20WEB/api';
}

// Log the API base URL for debugging
console.log('API Base URL:', API_BASE_URL);

// Export a function to get the full API URL for a specific endpoint
function getApiUrl(endpoint) {
    // Remove leading slash if present
    if (endpoint.startsWith('/')) {
        endpoint = endpoint.substring(1);
    }
    return `${API_BASE_URL}/${endpoint}`;
}
