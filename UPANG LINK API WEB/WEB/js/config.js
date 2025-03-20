// Define the base URL for API requests
// Handle both file:// and http:// protocols
window.API_BASE_URL = window.location.protocol === 'file:' 
    ? 'http://localhost/UPANG-LINK/UPANG%20LINK%20API%20WEB/api' 
    : window.location.origin + '/UPANG-LINK/UPANG%20LINK%20API%20WEB/api';

// Log the API base URL for debugging
console.log('API Base URL:', window.API_BASE_URL);

// Export a function to get the full API URL for a specific endpoint
window.getApiUrl = function(endpoint) {
    return `${window.API_BASE_URL}/${endpoint.replace(/^\/+/, '')}`;
};