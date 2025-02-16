// dashboard.js

class Dashboard {
    constructor() {
        this.loadStats();
        this.initCharts();
        this.loadRecentRequests();
        this.displayUserName();  // Fetch the current admin's username on initialization
    }

    // Retrieve requests and update count-based stats.
    async loadStats() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`${API_BASE_URL}/requests/`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });
            const data = await response.json();
            if (data.status === 'success') {
                // Assuming data.data is an array of request objects.
                const requests = data.data;
                const totalRequests = requests.length;
                // Count distinct users by their user_id.
                const totalUsers = [...new Set(requests.map(r => r.user_id))].length;
                // Filter requests by status.
                const pendingRequests = requests.filter(r => r.status === 'pending').length;
                // For your case, we assume a completed request is marked "approved"
                const completedRequests = requests.filter(r => r.status === 'approved').length;
                
                console.log("Total Requests:", totalRequests);
                console.log("Total Users:", totalUsers);
                console.log("Pending Requests:", pendingRequests);
                console.log("Completed Requests:", completedRequests);
                console.log("Stats loaded successfully:", data);
                
                // Update the DOM elements with the counts.
                document.getElementById('totalUsers').textContent = totalUsers;
                document.getElementById('totalRequests').textContent = totalRequests;
                document.getElementById('pendingRequests').textContent = pendingRequests;
                document.getElementById('completedRequests').textContent = completedRequests;
            } else {
                console.error("Error loading stats:", data.message);
            }
        } catch (error) {
            console.error("Error loading stats:", error);
        }
    }

    // Initialize sample charts.
    initCharts() {
        // Requests Chart
        const requestsCtx = document.getElementById('requestsChart').getContext('2d');
        new Chart(requestsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Requests',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#3699ff',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Request Types Chart
        const typesCtx = document.getElementById('requestTypesChart').getContext('2d');
        new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: ['TOR', 'ID', 'Certificate', 'Others'],
                datasets: [{
                    data: [12, 19, 3, 5],
                    backgroundColor: ['#3699ff', '#1bc5bd', '#8950fc', '#ffa800']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Retrieve recent requests and update the table.
    async loadRecentRequests() {
        try {
            const response = await fetch(`${API_BASE_URL}/web/requests/recent`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Platform': 'web'
                }
            });
            const data = await response.json();
            if (data.status === 'success') {
                this.displayRequests(data.data);
            }
        } catch (error) {
            console.error("Error loading recent requests:", error);
        }
    }

    // Populate the requests table.
    displayRequests(requests) {
        const tbody = document.getElementById('requestsTableBody');
        if (!requests || requests.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">No recent requests</td>
                </tr>
            `;
            return;
        }
        tbody.innerHTML = requests.map(request => `
            <tr>
                <td>${request.student.student_number}</td>
                <td>${request.student.name}</td>
                <td>${request.type.name}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(request.status)}">
                        ${request.status}
                    </span>
                </td>
                <td>${new Date(request.submitted_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewRequest(${request.id})">
                        View
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Helper for setting badge colors.
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
    
    // Update a request's status and then refresh stats.
    async updateRequestStatus(requestId, newStatus) {
        const token = localStorage.getItem('token');
        try {
            const response = await fetch(`${API_BASE_URL}/requests/${requestId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ status: newStatus })
            });
            const result = await response.json();
            if (result.status === 'success') {
                console.log("Request status updated successfully:", result.message);
                // Refresh stats so that the count for completed requests goes up.
                this.loadStats();
            } else {
                console.error("Failed to update request status:", result.message);
            }
        } catch (error) {
            console.error("Error updating request status:", error);
        }
    }
    
    // Fetch the current admin's profile using the token to dynamically build the URL with the admin's id.
    async displayUserName() {
        const token = localStorage.getItem('token');
        if (!token) {
            console.error("No token found in localStorage.");
            return;
        }
        // First, fetch the current admin's profile details (which include the admin_id)
        const profileUrl = `${API_BASE_URL}/auth/admin/users/`;
        console.log("Fetching admin profile from:", profileUrl);
        try {
            const profileResponse = await fetch(profileUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });
            if (!profileResponse.ok) {
                console.error("HTTP error while fetching admin profile:", profileResponse.status);
                return;
            }
            
            const profileResult = await profileResponse.json();
            console.log("Admin profile result:", profileResult);
            if (profileResult.status === 'success' && profileResult.data && profileResult.data.admin_id) {
                const adminId = profileResult.data.admin_id;
                // Now, build the URL dynamically using the adminId.
                const url = `${API_BASE_URL}/auth/admin/users/${admin_id}`;
                console.log("Fetching admin details from:", url);
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                if (!response.ok) {
                    console.error("HTTP error while fetching admin details:", response.status);
                    return;
                }
                const result = await response.json();
                console.log("Admin details result:", result);
                if (result.status === 'success') {
                    // Update the username element with the returned username.
                    document.getElementById('userFullName').textContent = result.data.username;
                } else {
                    console.error("Error fetching admin details:", result.message);
                }
            } else {
                console.error("Error fetching admin profile:", profileResult.message);
            }
        } catch (error) {
            console.error("Error fetching admin profile:", error);
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new Dashboard();
});

// ----- LOGOUT FUNCTIONALITY -----
// Global auth object with a logout method that calls the logout API endpoint.
// The endpoint should delete the token from your admin_tokens table on the server side.
const auth = {
    logout: async function() {
        const token = localStorage.getItem('token');
        if (token) {
            try {
                const response = await fetch(`${API_BASE_URL}/auth/admin/logout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                const result = await response.json();
                if (result.status === 'success') {
                    console.log('Logout successful:', result.message);
                } else {
                    console.error('Logout failed:', result.message);
                }
            } catch (error) {
                console.error('Error calling logout API:', error);
            }
        }
        // Clear stored token and user data on the client side
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        // Redirect to the login page
        window.location.href = 'login.html';
    }
};
