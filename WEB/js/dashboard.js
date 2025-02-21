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
    this.showLoading();
    this.initializeData();
    this.displayUserName();
  }

  showLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    if (loadingEl) {
      loadingEl.style.display = 'flex';
    }
  }

  hideLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    if (loadingEl) {
      loadingEl.style.display = 'none';
    }
  }

  showErrorAlert(message) {
    const errorContainer = document.getElementById('errorContainer');
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
    setTimeout(() => {
      errorContainer.style.display = 'none';
    }, 5000);
  }

  getToken() {
    return localStorage.getItem('token');
  }

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
      const currentUser = result.data[0];
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

      // Compute monthly counts for charts
      this.monthCounts = this.requestsData.reduce((acc, request) => {
        const date = new Date(request.submitted_at);
        if (!isNaN(date)) {
          acc[date.getMonth()]++;
        } else {
          console.warn("Invalid date in request:", request);
        }
        return acc;
      }, Array(12).fill(0));

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
    document.getElementById('totalUsers').textContent = totalUsers;
    document.getElementById('totalRequests').textContent = totalRequests;
    document.getElementById('pendingRequests').textContent = stats.pending;
    document.getElementById('completedRequests').textContent = stats.completed;
  }

  loadMonthlyChartUsingData() {
    console.log("Rendering monthly chart using pre-fetched data...");
    if (!this.monthCounts) {
      console.error("No month counts available for monthly chart.");
      return;
    }
    const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
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

  loadRequestsChartUsingData() {
    console.log("Rendering dynamic requests chart using pre-fetched data...");
    if (!this.monthCounts) {
      console.error("No month counts available for dynamic requests chart.");
      return;
    }
    const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
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

  loadRecentRequestsUsingData() {
    console.log("Rendering recent requests table using pre-fetched data...");
    if (!this.requestsData) {
      console.error("No requests data for recent requests.");
      return;
    }
    // Sort requests by submission date and take the most recent 5
    const sortedRequests = [...this.requestsData].sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
    const limitedRequests = sortedRequests.slice(0, 5);
    console.log("Recent requests:", limitedRequests);
    this.displayRequests(limitedRequests);
  }

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
      tbody.innerHTML = `<tr><td colspan="5" class="text-center">No recent requests</td></tr>`;
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
            <span class="badge status-${request.status}">
              ${request.status}
            </span>
          </td>
          <td>${new Date(request.submitted_at).toLocaleDateString()}</td>
        </tr>
      `;
    }).join('');
    console.log("Requests table updated.");
  }
}

// Initialize Dashboard after DOM loads
document.addEventListener('DOMContentLoaded', () => {
  console.log("DOM fully loaded, initializing Dashboard.");
  window.dashboardInstance = new Dashboard();
  
  // Sidebar active highlighting: add "active" class to the current page's sidebar link.
  const currentPage = window.location.pathname.split('/').pop();
  const menuLinks = document.querySelectorAll('#sidebar .sidebar-menu li a');
  menuLinks.forEach(link => {
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
      link.parentElement.classList.add('active');
    } else {
      link.classList.remove('active');
      link.parentElement.classList.remove('active');
    }
  });
});
