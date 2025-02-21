// Base URL for the API without a trailing slash
const API_BASE_URL = 'http://localhost:8000/UPANG%20LINK/api';

// Mapping for request type IDs to names
const requestTypeNames = {
    1: 'TOR',
    2: 'ID',
    3: 'Certificate',
    4: 'Others'
};

/**
 * Returns common headers for authenticated requests.
 * Uses the provided token.
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

/**
 * Displays a loading indicator with a centered logo.
 */
function showLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    const loadingLogo = document.getElementById('loadingLogo');
    if (loadingEl && loadingLogo) {
        loadingEl.style.display = 'flex';
        loadingEl.style.justifyContent = 'center';
        loadingEl.style.alignItems = 'center';
        loadingEl.style.position = 'fixed';
        loadingEl.style.top = '0';
        loadingEl.style.left = '0';
        loadingEl.style.width = '100%';
        loadingEl.style.height = '100%';
        loadingEl.style.zIndex = '9999';
        loadingLogo.style.display = 'block';
    }
}

/**
 * Hides the loading indicator.
 */
function hideLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    if (loadingEl) {
        loadingEl.style.display = 'none';
    }
}

/**
 * Fetches and displays the logged-in user's name.
 * First, it attempts to fetch from the admin endpoint.
 * If that succeeds, the role is set to "admin".
 * Otherwise, it falls back to the staff endpoint and sets the role to "staff".
 */
async function displayUserName() {
    console.log("Displaying logged-in username...");
    const token = localStorage.getItem('token');
    if (!token) {
        console.error("No token found in localStorage.");
        return;
    }
    try {
        // Try admin endpoint first
        let endpoint = `${API_BASE_URL}/admin/users`;
        let response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
        let result = await response.json();
        if (response.ok && result.status === 'success') {
            window.currentUserRole = 'admin';
            console.log("Admin endpoint successful. Detected role: admin.");
        } else {
            // Fallback: try staff endpoint
            endpoint = `${API_BASE_URL}/staff/`;
            response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
            result = await response.json();
            if (response.ok && result.status === 'success') {
                window.currentUserRole = 'staff';
                console.log("Staff endpoint successful. Detected role: staff.");
            } else {
                throw new Error("Unable to fetch user details from either endpoint.");
            }
        }
        // Use the first record for display
        let currentUser = result.data[0];
        const userFullNameEl = document.getElementById('userFullName');
        if (userFullNameEl) {
            const displayName = currentUser.username || `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim();
            userFullNameEl.textContent = displayName;
        }
        console.log("Logged in user data:", currentUser);
    } catch (error) {
        console.error("Error fetching user details:", error);
    }
}

/**
 * Global variables to hold all requests and pagination state.
 */
let allRequests = [];
let allUsersData = [];
// Global variable for the data currently displayed (allRequests or filtered)
let displayData = [];
// Default currentPage is 1.
let currentPage = 1;
const itemsPerPage = 10;
let totalPages = 1;

/**
 * Fetches requests and users data, sorts the requests by submission date,
 * and initializes (or refreshes) pagination.
 */
async function loadRequestsUsingPagination() {
    console.log("Fetching all requests for pagination...");
    const token = localStorage.getItem('token');
    if (!token) {
        console.error("No token found in localStorage.");
        return;
    }
    try {
        showLoading();
        const [requestsResponse, usersResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/requests/`, { headers: getAuthHeaders(token) }),
            fetch(`${API_BASE_URL}/auth/users`, { headers: getAuthHeaders(token) })
        ]);

        if (!requestsResponse.ok) {
            console.error("HTTP error fetching requests:", requestsResponse.status);
            return;
        }
        if (!usersResponse.ok) {
            console.error("HTTP error fetching users:", usersResponse.status);
            return;
        }

        const requestsData = await requestsResponse.json();
        const usersData = await usersResponse.json();

        if (requestsData.status !== 'success') {
            console.error("Error in requests data:", requestsData.message);
            return;
        }
        if (usersData.status !== 'success') {
            console.error("Error in users data:", usersData.message);
            return;
        }

        // Sort requests descending by submission date
        allRequests = [...requestsData.data].sort(
            (a, b) => new Date(b.submitted_at) - new Date(a.submitted_at)
        );
        allUsersData = usersData.data;
        // Initially, displayData is the full list
        displayData = allRequests;
        totalPages = Math.ceil(displayData.length / itemsPerPage);
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        displayRequestsPage(currentPage);
        updatePaginationControls(currentPage);
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        hideLoading();
    }
}

/**
 * Returns the array of requests to display (filtered by search if applicable).
 */
function getDisplayData() {
    return displayData;
}

/**
 * Displays the requests for the given page from the current display data.
 * @param {number} page - The page number to display.
 */
function displayRequestsPage(page) {
    const dataToDisplay = getDisplayData();
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = page * itemsPerPage;
    const pageRequests = dataToDisplay.slice(startIndex, endIndex);
    displayRequests(pageRequests, allUsersData);
}

/**
 * Renders the provided list of requests into the requests table.
 * @param {Array} requests - List of request objects.
 * @param {Array} usersData - List of user objects.
 */
function displayRequests(requests, usersData) {
    console.log("Displaying requests in table...");
    // Create a lookup map for users based on user_id
    const userMap = {};
    usersData.forEach(user => {
        userMap[user.user_id] = user;
    });
    const tbody = document.getElementById('requestsTableBody');
    if (!tbody) {
        console.error("requestsTableBody element not found in the DOM.");
        return;
    }
    if (!requests || requests.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center">No requests to display</td></tr>`;
        console.log("No requests to display.");
        return;
    }
    tbody.innerHTML = requests.map(request => {
        const user = userMap[request.user_id] || { student_number: "N/A", first_name: "Unknown", last_name: "" };
        return `
            <tr>
                <td>${user.student_number}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${requestTypeNames[request.type_id] || 'Unknown'}</td>
                <td>
                    <span class="badge bg-${getStatusColor(request.status)}">
                        ${request.status}
                    </span>
                </td>
                <td>${new Date(request.submitted_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewRequest(${request.request_id})">
                        View
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    console.log("Requests table updated.");
}

/**
 * Returns a Bootstrap color class based on the request status.
 * @param {string} status - The status of the request.
 * @returns {string} The Bootstrap color class.
 */
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'in_progress': 'info',
        'completed': 'primary'
    };
    return colors[status] || 'secondary';
}

/**
 * Updates the pagination controls.
 * @param {number} currentPage - The current page number.
 */
function updatePaginationControls(currentPage) {
    const dataToDisplay = getDisplayData();
    totalPages = Math.ceil(dataToDisplay.length / itemsPerPage);
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) {
        console.error("paginationContainer element not found in the DOM.");
        return;
    }
    
    let controlsHtml = '';
    if (currentPage > 1) {
        controlsHtml += `<button id="prevPage" class="btn btn-secondary btn-sm me-2">Previous</button>`;
    }
    controlsHtml += `<span id="pageCounter">Page ${currentPage} of ${totalPages}</span>`;
    if (currentPage < totalPages) {
        controlsHtml += `<button id="nextPage" class="btn btn-secondary btn-sm ms-2">Next</button>`;
    }
    paginationContainer.innerHTML = controlsHtml;
    
    const prevPageButton = document.getElementById('prevPage');
    if (prevPageButton) {
        prevPageButton.addEventListener('click', () => {
            if (currentPage > 1) {
                showLoading();
                currentPage--;
                displayRequestsPage(currentPage);
                updatePaginationControls(currentPage);
                hideLoading();
            }
        });
    }
    const nextPageButton = document.getElementById('nextPage');
    if (nextPageButton) {
        nextPageButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                showLoading();
                currentPage++;
                displayRequestsPage(currentPage);
                updatePaginationControls(currentPage);
                hideLoading();
            }
        });
    }
}

/**
 * Opens a modal to view a ticket/request.
 * (This function assumes that the modal elements already exist in the DOM.)
 * @param {number} requestId - The ID of the request to view.
 */
function viewRequest(requestId) {
    // Find the selected request
    const request = allRequests.find(r => r.request_id == requestId);
    if (!request) {
        console.error("Request not found!");
        return;
    }
    // Find the associated user details
    const user = allUsersData.find(u => u.user_id == request.user_id);
    
    // Build modal title and content
    const modalTitle = `Ticket Details - Request #${request.request_id}`;
    const modalBodyContent = `
      <p><strong>Student Number:</strong> ${user ? user.student_number : 'N/A'}</p>
      <p><strong>Name:</strong> ${user ? user.first_name + ' ' + user.last_name : 'Unknown'}</p>
      <p><strong>Request Type:</strong> ${requestTypeNames[request.type_id] || 'Unknown'}</p>
      <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(request.status)}">${request.status}</span></p>
      <p><strong>Date Submitted:</strong> ${new Date(request.submitted_at).toLocaleString()}</p>
      <p><strong>Additional Information:</strong> ${request.details || 'No additional details available.'}</p>
    `;
    
    const modalTitleEl = document.getElementById('ticketModalLabel');
    const modalBodyEl = document.getElementById('ticketModalBody');
    if (modalTitleEl && modalBodyEl) {
         modalTitleEl.innerHTML = modalTitle;
         modalBodyEl.innerHTML = modalBodyContent;
    } else {
         console.error("Modal elements not found in the DOM.");
         return;
    }
    
    // Show the modal using Bootstrap
    const ticketModal = new bootstrap.Modal(document.getElementById('ticketModal'));
    ticketModal.show();
}

/**
 * Updates the status of a ticket by sending a PUT request.
 * After updating, refreshes the requests data.
 * @param {number} requestId - The ID of the request.
 * @param {string} newStatus - The new status to set.
 */
async function updateTicketStatus(requestId, newStatus) {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error("No token found in localStorage.");
        return;
    }
    showLoading();
    try {
        const response = await fetch(`${API_BASE_URL}/requests/${requestId}`, {
            method: 'PUT',
            headers: getAuthHeaders(token),
            body: JSON.stringify({ status: newStatus })
        });
        const result = await response.json();
        if (result.status === 'success') {
            console.log(`Ticket ${requestId} updated to ${newStatus} successfully.`);
            // Refresh the list while preserving current page and search filter
            loadRequestsUsingPagination();
        } else {
            console.error("Failed to update ticket status:", result.message);
        }
    } catch (error) {
        console.error("Error updating ticket status:", error);
    } finally {
        hideLoading();
    }
}

/**
 * Logout fix: Uses dynamic role detection for logout.
 * If window.currentUserRole is "staff", it calls the staff logout endpoint;
 * otherwise, it calls the admin logout endpoint.
 */
window.auth = {
    logout: async function() {
        console.log("Attempting logout...");
        showLoading();
        const token = localStorage.getItem('token');
        if (token) {
            try {
                // Use the detected role; default to admin if not set
                const role = window.currentUserRole || 'admin';
                const logoutEndpoint = (role === 'staff')
                    ? `${API_BASE_URL}/staff/logout`
                    : `${API_BASE_URL}/admin/logout`;
                const response = await fetch(logoutEndpoint, {
                    method: 'POST',
                    headers: getAuthHeaders(token)
                });
                const result = await response.json();
                if (result.status !== 'success') {
                    throw new Error("Logout failed: " + result.message);
                }
                console.log("Logout successful:", result.message);
            } catch (error) {
                console.error("Logout error:", error);
            }
        }
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }
};

// Initialize data on DOM load and attach search functionality.
document.addEventListener('DOMContentLoaded', () => {
    displayUserName();
    loadRequestsUsingPagination();
    
    // Attach search event listener on the search input field.
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const query = this.value.trim().toLowerCase();
            if (query === "") {
                displayData = allRequests;
            } else {
                displayData = allRequests.filter(request => {
                    const status = (request.status || "").toLowerCase();
                    const date = new Date(request.submitted_at).toLocaleDateString().toLowerCase();
                    const type = (requestTypeNames[request.type_id] || "").toLowerCase();
                    const user = allUsersData.find(u => u.user_id === request.user_id);
                    const studentNumber = user && user.student_number ? user.student_number.toLowerCase() : "";
                    const name = user ? (user.first_name + " " + user.last_name).toLowerCase() : "";
                    return status.includes(query) || date.includes(query) || type.includes(query) || studentNumber.includes(query) || name.includes(query);
                });
            }
            currentPage = 1;
            totalPages = Math.ceil(displayData.length / itemsPerPage);
            displayRequestsPage(currentPage);
            updatePaginationControls(currentPage);
        });
    }
});
