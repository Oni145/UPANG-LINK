// Base URL for the API without a trailing slash
const API_BASE_URL = 'http://localhost:8000';

// Mapping for request type IDs to names
const requestTypeNames = {
  1: 'TOR',
  2: 'ID',
  3: 'Certificate',
  4: 'Others',
  7: 'Course Module Request'
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

/**
 * Displays the loading indicator.
 */
function showLoading() {
  const loadingEl = document.getElementById('loadingIndicator');
  if (loadingEl) {
    loadingEl.style.display = 'flex';
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
 */
async function displayUserName() {
  const token = localStorage.getItem('token');
  if (!token) {
    console.error("No token found in localStorage.");
    return;
  }
  try {
    let endpoint = `${API_BASE_URL}/admin/users`;
    let response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
    let result = await response.json();
    if (response.ok && result.status === 'success') {
      window.currentUserRole = 'admin';
    } else {
      throw new Error("Unable to fetch admin user details.");
    }
    const currentUser = result.data[0];
    const userFullNameEl = document.getElementById('userFullName');
    if (userFullNameEl) {
      const displayName = currentUser.username || `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim();
      userFullNameEl.textContent = displayName;
    }
  } catch (error) {
    console.error("Error fetching user details:", error);
  }
}

let allRequests = [];
let allUsersData = [];
let displayData = [];
let currentPage = 1;
const itemsPerPage = 10;
let totalPages = 1;
let currentRequestId = null;

/**
 * Fetches requests and users data, sorts them, and initializes pagination.
 */
async function loadRequestsUsingPagination() {
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

    if (!requestsResponse.ok || !usersResponse.ok) {
      console.error("Error fetching data from the API.");
      return;
    }

    const requestsData = await requestsResponse.json();
    const usersData = await usersResponse.json();

    if (requestsData.status !== 'success' || usersData.status !== 'success') {
      console.error("Error in data response");
      return;
    }

    allRequests = [...requestsData.data].sort(
      (a, b) => new Date(b.submitted_at) - new Date(a.submitted_at)
    );
    allUsersData = usersData.data;
    displayData = allRequests;
    totalPages = Math.ceil(displayData.length / itemsPerPage);
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);
    displayRequestsPage(currentPage);
    updatePaginationControls(currentPage);
  } catch (error) {
    console.error("Error fetching data:", error);
  } finally {
    hideLoading();
  }
}

/**
 * Returns the array of requests to display.
 */
function getDisplayData() {
  return displayData;
}

/**
 * Displays the requests for the given page.
 */
function displayRequestsPage(page) {
  const dataToDisplay = getDisplayData();
  const startIndex = (page - 1) * itemsPerPage;
  const pageRequests = dataToDisplay.slice(startIndex, startIndex + itemsPerPage);
  displayRequests(pageRequests, allUsersData);
}

/**
 * Maps a request status to a custom CSS class.
 */
function getStatusClass(status) {
  const classes = {
    'pending': 'status-pending',
    'approved': 'status-approved',
    'rejected': 'status-rejected',
    'in_progress': 'status-in_progress',
    'completed': 'status-completed'
  };
  return classes[status] || 'status-secondary';
}

/**
 * Renders the list of requests into the table.
 */
function displayRequests(requests, usersData) {
  const userMap = {};
  usersData.forEach(user => {
    userMap[user.user_id] = user;
  });
  const tbody = document.getElementById('requestsTableBody');
  if (!tbody) {
    console.error("requestsTableBody element not found.");
    return;
  }
  if (!requests || requests.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">No requests to display</td></tr>`;
    return;
  }
  tbody.innerHTML = requests.map(request => {
    const user = userMap[request.user_id] || { first_name: "Unknown", last_name: "" };
    return `
      <tr>
        <td>${user.first_name} ${user.last_name}</td>
        <td>${requestTypeNames[request.type_id] || 'Unknown'}</td>
        <td>
          <span class="badge ${getStatusClass(request.status)}">
            ${request.status}
          </span>
        </td>
        <td>${new Date(request.submitted_at).toLocaleDateString()}</td>
        <td>
          <button class="btn btn-primary" onclick="viewRequest(${request.request_id})">
            View
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * Updates pagination controls.
 */
function updatePaginationControls(currentPage) {
  const dataToDisplay = getDisplayData();
  totalPages = Math.ceil(dataToDisplay.length / itemsPerPage);
  const paginationContainer = document.getElementById('paginationContainer');
  if (!paginationContainer) {
    console.error("paginationContainer element not found.");
    return;
  }
  
  let controlsHtml = '';
  if (currentPage > 1) {
    controlsHtml += `<button id="prevPage" class="btn btn-secondary">Previous</button>`;
  }
  controlsHtml += `<span style="margin: 0 10px;">Page ${currentPage} of ${totalPages}</span>`;
  if (currentPage < totalPages) {
    controlsHtml += `<button id="nextPage" class="btn btn-secondary">Next</button>`;
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
 * Helper function to build a styled attached file element.
 */
function buildFileLink(file, label) {
  const displayText = label || file.file_name;
  return `
    <div class="attached-file">
      <div class="file-info">
        <i class="fas fa-file"></i>
        <span>${displayText}</span>
      </div>
      <div class="file-actions">
        <a href="${file.file_path}" target="_blank" class="btn-view" onclick="showLoading(); setTimeout(hideLoading, 2000)">View</a>
        <a href="${file.file_path}" download class="btn-download" onclick="showLoading(); setTimeout(hideLoading, 2000)">Download</a>
      </div>
    </div>
  `;
}

/**
 * Opens the ticket details modal and populates it with request data.
 */
function viewRequest(requestId) {
  showLoading();
  const request = allRequests.find(r => r.request_id == requestId);
  if (!request) {
    console.error("Request not found!");
    hideLoading();
    return;
  }
  const user = allUsersData.find(u => u.user_id == request.user_id);
  const modalTitle = `Ticket Details - Request #${request.request_id}`;
  
  let modalBodyContent = `
    <p><strong>Name:</strong> ${user ? user.first_name + ' ' + user.last_name : 'Unknown'}</p>
    <p><strong>Request Type:</strong> ${requestTypeNames[request.type_id] || 'Unknown'}</p>
    <p><strong>Status:</strong> <span class="badge ${getStatusClass(request.status)}">${request.status}</span></p>
    <p><strong>Date Submitted:</strong> ${new Date(request.submitted_at).toLocaleString()}</p>
    <p><strong>Additional Information:</strong> ${request.details || 'No additional details available.'}</p>`;
  
  // Mapping of file keys to display labels
  const fileLabels = {
    "Clearance": "Clearance Form",
    "RequestLetter": "Request Letter",
    "StudentID": "Student ID",
    "1x1_id_picture_(white_background,_formal_attire)": "1x1 ID Picture",
    "RegistrationForm": "Registration Form",
    "IDPicture": "ID Picture",
    "ProfessorApproval": "Professor Approval"
  };

  let fileLinks = '';
  for (const key in fileLabels) {
    if (request[key] && Array.isArray(request[key]) && request[key].length > 0) {
      request[key].forEach(file => {
        fileLinks += buildFileLink(file, fileLabels[key]);
      });
    }
  }
  if (fileLinks) {
    modalBodyContent += `<div class="attached-files"><h3>Attached Files</h3>${fileLinks}</div>`;
  }
  
  currentRequestId = request.request_id;
  
  const modalTitleEl = document.getElementById('ticketModalLabel');
  const modalBodyEl = document.getElementById('ticketModalBody');
  if (modalTitleEl && modalBodyEl) {
    modalTitleEl.innerHTML = modalTitle;
    modalBodyEl.innerHTML = modalBodyContent;
    const statusSelectEl = document.getElementById('statusSelect');
    if (statusSelectEl && ['approved', 'in_progress', 'completed', 'rejected'].includes(request.status)) {
      statusSelectEl.value = request.status;
    }
    openModal();
  } else {
    console.error("Modal elements not found.");
  }
  hideLoading();
}

/**
 * Opens the custom modal.
 */
function openModal() {
  const modal = document.getElementById('ticketModal');
  if (modal) {
    modal.classList.add('active');
  }
}

/**
 * Closes the custom modal.
 */
function closeModal() {
  const modal = document.getElementById('ticketModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

/**
 * Sends a PUT request to update the ticket status and refreshes data.
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
 * Dynamic logout function always using the admin endpoint.
 */
window.auth = {
  logout: async function() {
    showLoading();
    const token = localStorage.getItem('token');
    if (!token) {
      console.error("No token found in localStorage.");
      window.location.href = 'login.html';
      return;
    }
    try {
      const logoutEndpoint = `${API_BASE_URL}/admin/logout`;
      const response = await fetch(logoutEndpoint, {
        method: 'POST',
        headers: getAuthHeaders(token)
      });
      const result = await response.json();
      if (result.status !== 'success') {
        throw new Error("Logout failed: " + result.message);
      }
    } catch (error) {
      console.error("Logout error:", error);
    }
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
    hideLoading();
  }
};

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
        throw new Error("Unable to fetch admin user details.");
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
      if (request.status === 'completed') acc.completed++;
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
      tbody.innerHTML = `<tr><td colspan="4" class="text-center">No recent requests</td></tr>`;
      console.log("No recent requests to display.");
      return;
    }
    tbody.innerHTML = requests.map(request => {
      const user = userMap[request.user_id] || { first_name: "Unknown", last_name: "" };
      return `
        <tr>
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

/**
 * Dynamic logout function always using the admin endpoint.
 */
window.auth = {
  logout: async function() {
    showLoading();
    const token = localStorage.getItem('token');
    if (!token) {
      console.error("No token found in localStorage.");
      window.location.href = 'login.html';
      return;
    }
    try {
      const logoutEndpoint = `${API_BASE_URL}/admin/logout`;
      const response = await fetch(logoutEndpoint, {
        method: 'POST',
        headers: getAuthHeaders(token)
      });
      const result = await response.json();
      if (result.status !== 'success') {
        throw new Error("Logout failed: " + result.message);
      }
    } catch (error) {
      console.error("Logout error:", error);
    }
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
    hideLoading();
  }
};
