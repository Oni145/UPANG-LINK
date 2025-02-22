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
    // Attempt the admin endpoint first.
    let endpoint = `${API_BASE_URL}/admin/users`;
    let response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
    let result = await response.json();
    if (response.ok && result.status === 'success') {
      window.currentUserRole = 'admin';
    } else {
      // Fallback to the staff endpoint.
      endpoint = `${API_BASE_URL}/staff/`;
      response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
      result = await response.json();
      if (response.ok && result.status === 'success') {
        window.currentUserRole = 'staff';
      } else {
        throw new Error("Unable to fetch user details from either endpoint.");
      }
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

// Global variables for requests and pagination
let allRequests = [];
let allUsersData = [];
let displayData = [];
let currentPage = 1;
const itemsPerPage = 10;
let totalPages = 1;

// Global variable to store the current request ID being viewed/updated
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

    // Sort requests descending by submission date.
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
 * Returns the array of requests to display (filtered by search if applicable).
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
    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">No requests to display</td></tr>`;
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
 * Opens the ticket details modal and populates it with request data.
 */
function viewRequest(requestId) {
  const request = allRequests.find(r => r.request_id == requestId);
  if (!request) {
    console.error("Request not found!");
    return;
  }
  const user = allUsersData.find(u => u.user_id == request.user_id);
  const modalTitle = `Ticket Details - Request #${request.request_id}`;
  
  // Build basic info HTML.
  let modalBodyContent = `
    <p><strong>Student Number:</strong> ${user ? user.student_number : 'N/A'}</p>
    <p><strong>Name:</strong> ${user ? user.first_name + ' ' + user.last_name : 'Unknown'}</p>
    <p><strong>Request Type:</strong> ${requestTypeNames[request.type_id] || 'Unknown'}</p>
    <p><strong>Status:</strong> <span style="padding: 2px 6px; background-color: #eee;">${request.status}</span></p>
    <p><strong>Date Submitted:</strong> ${new Date(request.submitted_at).toLocaleString()}</p>
    <p><strong>Additional Information:</strong> ${request.details || 'No additional details available.'}</p>`;
  
  // Build file links for viewing and downloading.
  let fileLinks = '';
  if (request.Clearance) {
    fileLinks += `<p><strong>Clearance:</strong> 
      <a href="${request.Clearance.file_path}" target="_blank">View</a>
      &nbsp;|&nbsp;
      <a href="${request.Clearance.file_path}" download>Download (${request.Clearance.file_name})</a>
    </p>`;
  }
  if (request.RequestLetter) {
    fileLinks += `<p><strong>Request Letter:</strong> 
      <a href="${request.RequestLetter.file_path}" target="_blank">View</a>
      &nbsp;|&nbsp;
      <a href="${request.RequestLetter.file_path}" download>Download (${request.RequestLetter.file_name})</a>
    </p>`;
  }
  if (fileLinks) {
    modalBodyContent += `<h3>Attached Files</h3>` + fileLinks;
  }
  
  // Set the current request ID for later use
  currentRequestId = request.request_id;
  
  const modalTitleEl = document.getElementById('ticketModalLabel');
  const modalBodyEl = document.getElementById('ticketModalBody');
  if (modalTitleEl && modalBodyEl) {
    modalTitleEl.innerHTML = modalTitle;
    modalBodyEl.innerHTML = modalBodyContent;
    // Optionally, set the select value to the current status if it exists among the options
    const statusSelectEl = document.getElementById('statusSelect');
    if (statusSelectEl && ['approved', 'in_progress', 'completed', 'rejected'].includes(request.status)) {
      statusSelectEl.value = request.status;
    }
    openModal();
  } else {
    console.error("Modal elements not found.");
  }
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
 * Dynamic logout function based on user role.
 */
window.auth = {
  logout: async function() {
    showLoading();
    const token = localStorage.getItem('token');
    if (token) {
      try {
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
      } catch (error) {
        console.error("Logout error:", error);
      }
    }
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
  }
};

// Initialize on DOM load.
document.addEventListener('DOMContentLoaded', () => {
  displayUserName();
  loadRequestsUsingPagination();

  // Attach search functionality.
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

  // Attach event listener for the update status button.
  const updateStatusBtn = document.getElementById("updateStatusBtn");
  if (updateStatusBtn) {
    updateStatusBtn.addEventListener("click", function() {
      const newStatus = document.getElementById("statusSelect").value;
      if (currentRequestId) {
        updateTicketStatus(currentRequestId, newStatus);
        closeModal();
      } else {
        console.error("No current request selected for update.");
      }
    });
  }
});
