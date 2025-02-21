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
        // Fetch initial data and display the logged-in admin name.
        this.initializeData();
        this.displayUserName();
    }

    /**
     * Displays a loading message on the screen with the logo centered.
     */
    showLoading() {
        const loadingEl = document.getElementById('loadingIndicator');
        const loadingLogo = document.getElementById('loadingLogo');
        
        if (loadingEl && loadingLogo) {
            loadingEl.style.display = 'flex'; // Use 'flex' to center the logo
            loadingEl.style.justifyContent = 'center';
            loadingEl.style.alignItems = 'center';
            loadingLogo.style.display = 'block'; // Ensure the logo is visible
        }
    }

    /**
     * Hides the loading message from the screen.
     */
    hideLoading() {
        const loadingEl = document.getElementById('loadingIndicator');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }

    /**
     * Fetches requests and users data concurrently from the API.
     * Computes monthly counts and triggers various UI update functions.
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

            // Compute month counts once for use in multiple charts
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
            // Hide loading indicator regardless of success or failure
            this.hideLoading();
        }
    }

    /**
     * Updates the dashboard statistics (total requests, users, pending, and approved requests).
     * It uses the pre-fetched requests and users data.
     */
    loadStatsUsingData() {
        console.log("Updating stats using pre-fetched data...");
        if (!this.requestsData || !this.usersData) {
            console.error("Missing requests or users data for stats.");
            return;
        }
        // Calculate stats with a single pass over requestsData
        const stats = this.requestsData.reduce((acc, request) => {
            if (request.status === 'pending') acc.pending++;
            if (request.status === 'approved') acc.completed++;
            return acc;
        }, { pending: 0, completed: 0 });
        const totalRequests = this.requestsData.length;
        const totalUsers = Array.isArray(this.usersData) ? this.usersData.length : 0;
        console.log("Stats calculated:", { totalRequests, totalUsers, pendingRequests: stats.pending, completedRequests: stats.completed });
        
        // Update DOM elements with the calculated stats
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
     * Renders the monthly requests chart using pre-computed month counts.
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
     * Renders a dynamic requests chart using the same monthly counts data.
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
     * Renders a donut chart that shows the count of requests per type.
     */
    loadRequestTypesChartUsingData() {
        console.log("Rendering donut (doughnut) chart using pre-fetched data...");
        if (!this.requestsData) {
            console.error("No requests data for donut chart.");
            return;
        }
        // Initialize counts for each type based on the requestTypeNames mapping
        const typeCounts = {};
        Object.keys(requestTypeNames).forEach(key => {
            typeCounts[key] = 0;
        });
        this.requestsData.forEach(request => {
            const type = request.type_id;
            if (typeCounts.hasOwnProperty(type)) {
                typeCounts[type]++;
            } else {
                // Count any unexpected type as 'Others'
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
     * Sorts the requests by submission date and displays the five most recent in a table.
     */
    loadRecentRequestsUsingData() {
        console.log("Rendering recent requests table using pre-fetched data...");
        if (!this.requestsData) {
            console.error("No requests data for recent requests.");
            return;
        }
        // Sort requests descending by submission date and take the top 5
        const sortedRequests = [...this.requestsData].sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
        const limitedRequests = sortedRequests.slice(0, 5);
        console.log("Recent requests:", limitedRequests);
        this.displayRequests(limitedRequests);
    }

    /**
     * Renders the given list of requests into the requests table.
     * Maps each request to its corresponding user data for display.
     * @param {Array} requests - List of request objects to display.
     */
    displayRequests(requests) {
        console.log("Displaying requests in table...");
        if (!this.usersData) {
            console.error("No users data available.");
            return;
        }
        // Create a lookup map for users based on user_id for fast access
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
     * Updates the status of a specific request by sending a PUT request to the API.
     * After a successful update, it refreshes the dashboard data.
     * @param {number} requestId - The ID of the request to update.
     * @param {string} newStatus - The new status to set.
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
                this.initializeData(); // Refresh all data
            } else {
                console.error("Failed to update request status:", result.message);
            }
        } catch (error) {
            console.error("Error updating request status:", error);
        }
    }

    /**
     * Fetches and displays the username of the logged-in admin.
     */
    async displayUserName() {
        console.log("Displaying logged-in admin username...");
        try {
            const url = `${API_BASE_URL}/admin/users`;
            const response = await fetch(url, { method: 'GET', headers: getAuthHeaders(this.token) });
            if (!response.ok) {
                console.error("HTTP error while fetching admin details:", response.status);
                return;
            }
            const result = await response.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                // Use the stored loggedAdminId if available; otherwise, use the first admin record
                const loggedAdminId = localStorage.getItem('loggedAdminId');
                let currentAdmin = loggedAdminId ? result.data.find(admin => admin.admin_id == loggedAdminId) : result.data[0];
                if (currentAdmin && currentAdmin.username) {
                    const userFullNameEl = document.getElementById('userFullName');
                    if (userFullNameEl) {
                        userFullNameEl.textContent = currentAdmin.username;
                    }
                    console.log("Logged in admin username:", currentAdmin.username);
                } else {
                    console.error("No matching admin record found.");
                }
            } else {
                console.error("Error fetching admin details:", result.message);
            }
        } catch (error) {
            console.error("Error fetching admin details:", error);
        }
    }
}

// Initialize the Dashboard once the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded, initializing Dashboard.");
    new Dashboard();

    // Debug: Log the staff_tokens to the console to verify it's being properly loaded.
    const staffTokens = localStorage.getItem('staff_tokens');
    console.log("staff_tokens:", staffTokens);
});

