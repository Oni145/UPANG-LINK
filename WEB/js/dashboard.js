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

class Dashboard {
    constructor() {
        console.log("Dashboard initialized.");
        this.token = localStorage.getItem('token');
        if (!this.token) {
            console.error("No token found in localStorage.");
            return;
        }
        // Show loading indicator while data is being fetched.
        this.showLoading();
        // Fetch initial data and display the logged-in user's name with role detection.
        this.initializeData();
        this.displayUserName();
    }

    /**
     * Displays a loading message with the logo centered.
     */
    showLoading() {
        const loadingEl = document.getElementById('loadingIndicator');
        const loadingLogo = document.getElementById('loadingLogo');
        if (loadingEl && loadingLogo) {
            loadingEl.style.display = 'flex';
            loadingEl.style.justifyContent = 'center';
            loadingEl.style.alignItems = 'center';
            loadingLogo.style.display = 'block';
        }
    }

    /**
     * Hides the loading message.
     */
    hideLoading() {
        const loadingEl = document.getElementById('loadingIndicator');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }

    /**
     * Displays a global error alert that automatically hides after 5 seconds.
     * @param {string} message - The error message.
     */
    showErrorAlert(message) {
        let errorContainer = document.getElementById('errorContainer');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = 'errorContainer';
            errorContainer.className = 'alert alert-danger';
            errorContainer.style.position = 'fixed';
            errorContainer.style.top = '20px';
            errorContainer.style.right = '20px';
            errorContainer.style.zIndex = '10000';
            errorContainer.style.padding = '10px 20px';
            errorContainer.style.border = '1px solid red';
            errorContainer.style.backgroundColor = '#f8d7da';
            errorContainer.style.color = '#721c24';
            document.body.appendChild(errorContainer);
        }
        errorContainer.textContent = message;
        errorContainer.style.display = 'block';
        setTimeout(() => {
            errorContainer.style.display = 'none';
        }, 5000);
    }

    /**
     * Marks an input element as invalid and displays an error message.
     */
    markError(inputId, errorId, message) {
        const input = document.getElementById(inputId);
        const errorEl = document.getElementById(errorId);
        if (input && errorEl) {
            errorEl.textContent = message;
            input.classList.add("is-invalid");
        }
    }

    /**
     * Returns the token from localStorage.
     */
    getToken() {
        return localStorage.getItem('token');
    }

    /**
     * Fetches and displays the logged-in user's name.
     * It first attempts to fetch from the admin endpoint. If successful,
     * it sets the role to "admin"; otherwise, it falls back to the staff endpoint
     * and sets the role to "staff".
     */
    async displayUserName() {
        console.log("Displaying logged-in user name...");
        try {
            let endpoint = `${API_BASE_URL}/admin/users`;
            let response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(this.token) });
            let result = await response.json();
            if (response.ok && result.status === 'success') {
                window.currentUserRole = 'admin';
                console.log("Admin endpoint successful. Detected role: admin.");
            } else {
                // Fallback to staff endpoint.
                endpoint = `${API_BASE_URL}/staff/`;
                response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(this.token) });
                result = await response.json();
                if (response.ok && result.status === 'success') {
                    window.currentUserRole = 'staff';
                    console.log("Staff endpoint successful. Detected role: staff.");
                } else {
                    throw new Error("Unable to fetch user details from either endpoint.");
                }
            }
            // Use the first record from the successful endpoint.
            let currentUser = result.data[0];
            const nameEl = document.getElementById('userFullName');
            if (nameEl) {
                const displayName = currentUser.username || `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim();
                nameEl.textContent = displayName;
            }
            console.log("Logged in user data:", currentUser);
        } catch (error) {
            console.error("Error fetching user details:", error);
            this.showErrorAlert(error.message);
        }
    }

    /**
     * Fetches requests and users data concurrently from the API.
     * Computes monthly counts and triggers UI update functions.
     */
    async initializeData() {
        console.log("Fetching requests and users data...");
        try {
            const [requestsResponse, usersResponse] = await Promise.all([
                fetch(`${API_BASE_URL}/requests/`, { headers: getAuthHeaders(this.token) }),
                fetch(`${API_BASE_URL}/auth/users`, { headers: getAuthHeaders(this.token) })
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
            this.requestsData = requestsData.data;
            this.usersData = usersData.data;
            console.log("Requests data fetched:", this.requestsData);
            console.log("Users data fetched:", this.usersData);

            // Compute month counts for charts
            this.monthCounts = this.requestsData.reduce((acc, request) => {
                const date = new Date(request.submitted_at);
                if (!isNaN(date)) {
                    acc[date.getMonth()]++;
                } else {
                    console.warn("Invalid date in request:", request);
                }
                return acc;
            }, Array(12).fill(0));

            // Update various parts of the dashboard UI
            this.loadStatsUsingData();
            this.loadMonthlyChartUsingData();
            this.loadRequestsChartUsingData();
            this.loadRequestTypesChartUsingData();
            this.loadRecentRequestsUsingData();
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Updates the dashboard statistics (total requests, users, pending, and approved).
     */
    loadStatsUsingData() {
        console.log("Updating stats using pre-fetched data...");
        if (!this.requestsData || !this.usersData) {
            console.error("Missing requests or users data for stats.");
            return;
        }
        const stats = this.requestsData.reduce((acc, request) => {
            if (request.status === 'pending') acc.pending++;
            if (request.status === 'approved') acc.completed++;
            return acc;
        }, { pending: 0, completed: 0 });
        const totalRequests = this.requestsData.length;
        const totalUsers = Array.isArray(this.usersData) ? this.usersData.length : 0;
        console.log("Stats calculated:", { totalRequests, totalUsers, pendingRequests: stats.pending, completedRequests: stats.completed });
        const totalUsersEl = document.getElementById('totalUsers');
        const totalRequestsEl = document.getElementById('totalRequests');
        const pendingRequestsEl = document.getElementById('pendingRequests');
        const completedRequestsEl = document.getElementById('completedRequests');
        if (totalUsersEl) totalUsersEl.textContent = totalUsers;
        if (totalRequestsEl) totalRequestsEl.textContent = totalRequests;
        if (pendingRequestsEl) pendingRequestsEl.textContent = stats.pending;
        if (completedRequestsEl) completedRequestsEl.textContent = stats.completed;
    }

    /**
     * Renders the monthly requests chart using the computed month counts.
     */
    loadMonthlyChartUsingData() {
        console.log("Rendering monthly chart using pre-fetched data...");
        if (!this.monthCounts) {
            console.error("No month counts available for monthly chart.");
            return;
        }
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                             'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyChartEl = document.getElementById('monthlyChart');
        if (!monthlyChartEl) {
            console.error("monthlyChart element not found in the DOM.");
            return;
        }
        const monthlyCtx = monthlyChartEl.getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Monthly Requests',
                    data: this.monthCounts,
                    borderColor: '#ff6384',
                    backgroundColor: 'rgba(255,99,132,0.2)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        console.log("Monthly chart rendered.");
    }

    /**
     * Renders a dynamic line chart for requests using the same monthly counts.
     */
    loadRequestsChartUsingData() {
        console.log("Rendering dynamic requests chart using pre-fetched data...");
        if (!this.monthCounts) {
            console.error("No month counts available for dynamic requests chart.");
            return;
        }
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                             'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const requestsChartEl = document.getElementById('requestsChart');
        if (!requestsChartEl) {
            console.error("requestsChart element not found in the DOM.");
            return;
        }
        const requestsCtx = requestsChartEl.getContext('2d');
        new Chart(requestsCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Requests',
                    data: this.monthCounts,
                    borderColor: '#3699ff',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        console.log("Dynamic requests chart rendered.");
    }

    /**
     * Renders a donut chart showing counts of requests per type.
     */
    loadRequestTypesChartUsingData() {
        console.log("Rendering donut chart using pre-fetched data...");
        if (!this.requestsData) {
            console.error("No requests data for donut chart.");
            return;
        }
        const typeCounts = {};
        Object.keys(requestTypeNames).forEach(key => {
            typeCounts[key] = 0;
        });
        this.requestsData.forEach(request => {
            const type = request.type_id;
            if (typeCounts.hasOwnProperty(type)) {
                typeCounts[type]++;
            } else {
                typeCounts[4] = (typeCounts[4] || 0) + 1;
            }
        });
        console.log("Request type counts:", typeCounts);
        const labels = [];
        const data = [];
        const backgroundColors = [];
        const colorsMapping = {
            1: '#3699ff',
            2: '#1bc5bd',
            3: '#8950fc',
            4: '#ffa800'
        };
        for (const [key, count] of Object.entries(typeCounts)) {
            labels.push(requestTypeNames[key] || 'Unknown');
            data.push(count);
            backgroundColors.push(colorsMapping[key] || '#cccccc');
        }
        const donutChartEl = document.getElementById('requestTypesChart');
        if (!donutChartEl) {
            console.error("requestTypesChart element not found in the DOM.");
            return;
        }
        const ctx = donutChartEl.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        console.log("Donut chart rendered successfully.");
    }

    /**
     * Displays the five most recent requests in a table.
     */
    loadRecentRequestsUsingData() {
        console.log("Rendering recent requests table using pre-fetched data...");
        if (!this.requestsData) {
            console.error("No requests data for recent requests.");
            return;
        }
        const sortedRequests = [...this.requestsData].sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
        const limitedRequests = sortedRequests.slice(0, 5);
        console.log("Recent requests:", limitedRequests);
        this.displayRequests(limitedRequests);
    }

    /**
     * Renders the given list of requests into the requests table.
     */
    displayRequests(requests) {
        console.log("Displaying requests in table...");
        if (!this.usersData) {
            console.error("No users data available.");
            return;
        }
        const userMap = {};
        this.usersData.forEach(user => {
            userMap[user.user_id] = user;
        });
        const tbody = document.getElementById('requestsTableBody');
        if (!tbody) {
            console.error("requestsTableBody element not found in the DOM.");
            return;
        }
        if (!requests || requests.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">No recent requests</td></tr>`;
            console.log("No recent requests to display.");
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
                        <span class="badge bg-${this.getStatusColor(request.status)}">
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
    getStatusColor(status) {
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
     * Updates the status of a specific request.
     * After a successful update, refreshes the dashboard data.
     * @param {number} requestId - The ID of the request.
     * @param {string} newStatus - The new status.
     */
    async updateRequestStatus(requestId, newStatus) {
        console.log(`Updating status for request ${requestId} to ${newStatus}`);
        try {
            const response = await fetch(`${API_BASE_URL}/requests/${requestId}`, {
                method: 'PUT',
                headers: getAuthHeaders(this.token),
                body: JSON.stringify({ status: newStatus })
            });
            const result = await response.json();
            if (result.status === 'success') {
                console.log("Request status updated successfully:", result.message);
                this.initializeData(); // Refresh data
            } else {
                console.error("Failed to update request status:", result.message);
            }
        } catch (error) {
            console.error("Error updating request status:", error);
        }
    }

    /**
     * Opens a modal to view/update a user's details.
     */
    viewUser(userId) {
        const user = allUsersData.find(u => u.user_id == userId);
        if (!user) {
            console.error("User not found for ID:", userId);
            return;
        }
        let modal = document.getElementById("userModal");
        if (!modal) {
            modal = document.createElement("div");
            modal.id = "userModal";
            modal.className = "modal fade";
            modal.setAttribute("tabindex", "-1");
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">User Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Student Number</label>
                        <div class="form-control-plaintext" id="displayStudentNumber"></div>
                      </div>
                      <form id="userForm">
                        <div class="mb-3">
                          <label for="firstName" class="form-label">First Name</label>
                          <input type="text" class="form-control" id="firstName" name="first_name">
                        </div>
                        <div class="mb-3">
                          <label for="lastName" class="form-label">Last Name</label>
                          <input type="text" class="form-control" id="lastName" name="last_name">
                        </div>
                        <div class="mb-3">
                          <label for="email" class="form-label">Email</label>
                          <input type="text" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                          <label for="role" class="form-label">Role</label>
                          <input type="text" class="form-control" id="role" name="role">
                        </div>
                        <div class="mb-3">
                          <label for="course" class="form-label">Course</label>
                          <input type="text" class="form-control" id="course" name="course">
                        </div>
                        <div class="mb-3">
                          <label for="yearLevel" class="form-label">Year Level</label>
                          <input type="text" class="form-control" id="yearLevel" name="year_level">
                        </div>
                        <div class="mb-3">
                          <label for="block" class="form-label">Block</label>
                          <input type="text" class="form-control" id="block" name="block">
                        </div>
                        <div class="mb-3">
                          <label for="admissionYear" class="form-label">Admission Year</label>
                          <input type="text" class="form-control" id="admissionYear" name="admission_year">
                        </div>
                      </form>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="saveUserBtn">Save changes</button>
                    </div>
                  </div>
                </div>`;
            document.body.appendChild(modal);
        }
        modal.querySelector("#displayStudentNumber").textContent = user.student_number || "";
        modal.querySelector("#firstName").value = user.first_name || "";
        modal.querySelector("#lastName").value = user.last_name || "";
        modal.querySelector("#email").value = user.email || "";
        modal.querySelector("#role").value = user.role || "";
        modal.querySelector("#course").value = user.course || "";
        modal.querySelector("#yearLevel").value = user.year_level || "";
        modal.querySelector("#block").value = user.block || "";
        modal.querySelector("#admissionYear").value = user.admission_year || "";
        modal.querySelector("#saveUserBtn").onclick = () => this.updateUser(user.user_id);
        (bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)).show();
    }

    /**
     * Sends a PUT request to update a user's details.
     */
    async updateUser(userId) {
        const token = this.getToken();
        if (!token) {
            console.error("No token found.");
            return;
        }
        const originalUser = allUsersData.find(u => u.user_id == userId);
        if (!originalUser) {
            console.error("Original user not found for ID:", userId);
            return;
        }
        const modal = document.getElementById("userModal");
        const form = modal.querySelector("#userForm");
        const formData = new FormData(form);
        const updatedUser = {
            student_number: originalUser.student_number,
            first_name: formData.get('first_name')?.trim() || originalUser.first_name,
            last_name: formData.get('last_name')?.trim() || originalUser.last_name,
            email: formData.get('email')?.trim() || originalUser.email,
            role: formData.get('role')?.trim() || originalUser.role,
            course: formData.get('course')?.trim() || originalUser.course,
            year_level: formData.get('year_level')?.trim() || originalUser.year_level,
            block: formData.get('block')?.trim() || originalUser.block,
            admission_year: formData.get('admission_year')?.trim() || originalUser.admission_year
        };
        try {
            this.showLoading();
            const response = await fetch(`${API_BASE_URL}/auth/users/${userId}`, {
                method: 'PUT',
                headers: getAuthHeaders(token),
                body: JSON.stringify(updatedUser)
            });
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const result = await response.json();
            if (result.status === 'success') {
                bootstrap.Modal.getInstance(modal)?.hide();
                this.loadUsers();
            } else {
                throw new Error("Error updating user: " + result.message);
            }
        } catch (error) {
            console.error("Error updating user:", error);
            this.showErrorAlert(error.message);
        } finally {
            this.hideLoading();
        }
    }
}

// Initialize the Dashboard once the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded, initializing Dashboard.");
    new Dashboard();

    // Debug: Log staff_tokens to verify they are properly loaded.
    const staffTokens = localStorage.getItem('staff_tokens');
    console.log("staff_tokens:", staffTokens);
});

// Logout fix: attach a logout function to window.auth.
// It checks window.currentUserRole and uses the proper logout endpoint.
window.auth = {
    logout: async function() {
        console.log("Attempting logout...");
        // Show a loading indicator (if available)
        const loadingEl = document.getElementById('loadingIndicator');
        if (loadingEl) loadingEl.style.display = 'flex';
        const token = localStorage.getItem('token');
        if (token) {
            try {
                // Use window.currentUserRole if set, otherwise default to admin.
                const userType = window.currentUserRole || 'admin';
                const logoutEndpoint = (userType === 'staff')
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
                // Optionally, display an error alert.
            }
        }
        // Clear local storage and redirect to login.
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }
};
