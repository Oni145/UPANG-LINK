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
  if (loadingEl) loadingEl.style.display = 'flex';
}

/**
 * Hides the loading indicator.
 */
function hideLoading() {
  const loadingEl = document.getElementById('loadingIndicator');
  if (loadingEl) loadingEl.style.display = 'none';
}

/**
 * Fetches and displays the logged-in admin's name.
 */
async function displayUserName() {
  const token = localStorage.getItem('token');
  if (!token) return console.error("No token found in localStorage.");
  try {
    const endpoint = `${API_BASE_URL}/admin/users`;
    const response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
    const result = await response.json();
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
 * Helper function to count sentences in a text.
 */
function countSentences(text) {
  const sentences = text.split(/(?<=[.!?])\s+/);
  return sentences.filter(sentence => sentence.trim().length > 0).length;
}

/**
 * Fetches requests and admin users data, sorts them, and initializes pagination.
 */
async function loadRequestsUsingPagination() {
  const token = localStorage.getItem('token');
  if (!token) return console.error("No token found in localStorage.");
  try {
    showLoading();
    const [requestsResponse, usersResponse] = await Promise.all([
      fetch(`${API_BASE_URL}/requests/`, { headers: getAuthHeaders(token) }),
      fetch(`${API_BASE_URL}/auth/users`, { headers: getAuthHeaders(token) })
    ]);
    if (!requestsResponse.ok || !usersResponse.ok)
      return console.error("Error fetching data from the API.");
    const requestsData = await requestsResponse.json();
    const usersData = await usersResponse.json();
    if (requestsData.status !== 'success' || usersData.status !== 'success')
      return console.error("Error in data response");
    allRequests = [...requestsData.data].sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
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
    'in_progress': 'status_in_progress',
    'completed': 'status-completed'
  };
  return classes[status] || 'status-secondary';
}

/**
 * Renders the list of requests into the table.
 */
function displayRequests(requests, usersData) {
  const userMap = {};
  usersData.forEach(user => { userMap[user.user_id] = user; });
  const tbody = document.getElementById('requestsTableBody');
  if (!tbody) return console.error("requestsTableBody element not found.");
  if (!requests || requests.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">No requests to display</td></tr>`;
    return;
  }
  tbody.innerHTML = requests.map(request => {
    const user = userMap[request.user_id] || { first_name: "Unknown", last_name: "" };
    return `<tr>
          <td>${user.first_name} ${user.last_name}</td>
          <td>${requestTypeNames[request.type_id] || 'Unknown'}</td>
          <td>
            <span class="badge ${getStatusClass(request.status)}">${request.status}</span>
          </td>
          <td>${new Date(request.submitted_at).toLocaleDateString()}</td>
          <td>
            <div style="display: flex; gap: 5px;">
              <button type="button" class="btn btn-primary" onclick="viewRequest(${request.request_id})">View</button>
            </div>
          </td>
        </tr>`;
  }).join('');
}

/**
 * Updates pagination controls.
 */
function updatePaginationControls(currentPage) {
  const dataToDisplay = getDisplayData();
  totalPages = Math.ceil(dataToDisplay.length / itemsPerPage);
  const paginationContainer = document.getElementById('paginationContainer');
  if (!paginationContainer) return console.error("paginationContainer element not found.");
  let controlsHtml = '';
  if (currentPage > 1) controlsHtml += `<button id="prevPage" class="btn btn-secondary">Previous</button>`;
  controlsHtml += `<span style="margin: 0 10px;">Page ${currentPage} of ${totalPages}</span>`;
  if (currentPage < totalPages) controlsHtml += `<button id="nextPage" class="btn btn-secondary">Next</button>`;
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
  return `<div class="attached-file">
        <div class="file-info">
          <i class="fas fa-file"></i>
          <span>${displayText}</span>
        </div>
        <div class="file-actions">
          <a href="${file.file_path}" target="_blank" class="btn-view" onclick="showLoading(); setTimeout(hideLoading, 2000)">View</a>
          <a href="${file.file_path}" download class="btn-download" onclick="showLoading(); setTimeout(hideLoading, 2000)">Download</a>
        </div>
      </div>`;
}

/**
 * Opens the ticket modal with inline comment editing.
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
  
  // Ticket Details Section
  let ticketDetailsHTML = `<div class="ticket-details">
        <p><strong>Name:</strong> ${user ? user.first_name + ' ' + user.last_name : 'Unknown'}</p>
        <p><strong>Request Type:</strong> ${requestTypeNames[request.type_id] || 'Unknown'}</p>
        <p><strong>Status:</strong> <span class="badge ${getStatusClass(request.status)}">${request.status}</span></p>
        <p><strong>Date Submitted:</strong> ${new Date(request.submitted_at).toLocaleString()}</p>
      </div>`;
  document.getElementById('ticketModalLabel').innerHTML = modalTitle;
  document.getElementById('ticketDetails').innerHTML = ticketDetailsHTML;
  
  // Attached Files Section
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
  const ticketFilesEl = document.getElementById('ticketFiles');
  if (fileLinks) {
    ticketFilesEl.innerHTML = `<div class="attached-files"><h3>Attached Files</h3>${fileLinks}</div>`;
  } else {
    ticketFilesEl.innerHTML = '';
  }
  
  // Inline Comment Editing Section
  const displayCommentTextEl = document.getElementById('displayCommentText');
  const editCommentTextarea = document.getElementById('editCommentTextarea');
  const editCommentBtn = document.getElementById('editCommentBtn');
  const saveTicketCommentBtn = document.getElementById('saveTicketCommentBtn');
  const updateTicketCommentBtn = document.getElementById('updateTicketCommentBtn');
  const cancelEditCommentBtn = document.getElementById('cancelEditCommentBtn');
  
  // Set the comment display based on whether a comment exists.
  if (request.note && request.note.trim() !== "") {
    displayCommentTextEl.textContent = request.note;
  } else {
    displayCommentTextEl.textContent = "No comment available.";
  }
  editCommentTextarea.value = request.note || '';
  
  // Initially show only the comment display and the "Edit Comment" button.
  displayCommentTextEl.style.display = 'block';
  editCommentTextarea.style.display = 'none';
  saveTicketCommentBtn.style.display = 'none';
  updateTicketCommentBtn.style.display = 'none';
  cancelEditCommentBtn.style.display = 'none';
  
  // Replace buttons to avoid duplicate bindings.
  const newEditBtn = editCommentBtn.cloneNode(true);
  editCommentBtn.parentNode.replaceChild(newEditBtn, editCommentBtn);
  const newSaveBtn = saveTicketCommentBtn.cloneNode(true);
  saveTicketCommentBtn.parentNode.replaceChild(newSaveBtn, saveTicketCommentBtn);
  const newUpdateBtn = updateTicketCommentBtn.cloneNode(true);
  updateTicketCommentBtn.parentNode.replaceChild(newUpdateBtn, updateTicketCommentBtn);
  const newCancelBtn = cancelEditCommentBtn.cloneNode(true);
  cancelEditCommentBtn.parentNode.replaceChild(newCancelBtn, cancelEditCommentBtn);
  
  // When "Edit Comment" is clicked, switch to edit mode.
  newEditBtn.addEventListener('click', () => {
    editCommentTextarea.value = (displayCommentTextEl.textContent.trim() === "No comment available.") ? "" : displayCommentTextEl.textContent;
    displayCommentTextEl.style.display = 'none';
    editCommentTextarea.style.display = 'block';
    newCancelBtn.style.display = 'inline-block';
    newEditBtn.style.display = 'none';
    // Show "Update" button if a comment exists, otherwise "Save".
    if (request.note && request.note.trim() !== "") {
      newUpdateBtn.style.display = 'inline-block';
      newSaveBtn.style.display = 'none';
    } else {
      newSaveBtn.style.display = 'inline-block';
      newUpdateBtn.style.display = 'none';
    }
  });
  
  // When "Cancel" is clicked, revert back to display mode.
  newCancelBtn.addEventListener('click', () => {
    editCommentTextarea.style.display = 'none';
    newSaveBtn.style.display = 'none';
    newUpdateBtn.style.display = 'none';
    newCancelBtn.style.display = 'none';
    displayCommentTextEl.style.display = 'block';
    newEditBtn.style.display = 'inline-block';
  });
  
  // When "Save Comment" is clicked (for new comments)
  newSaveBtn.addEventListener('click', async () => {
    const commentText = editCommentTextarea.value.trim();
    if (commentText === "") {
      alert("Comment cannot be empty.");
      return;
    }
    if (countSentences(commentText) > 2) {
      alert("Please limit your comment to 2 sentences.");
      return;
    }
    await createTicketComment(currentRequestId, commentText);
    displayCommentTextEl.textContent = commentText;
    // After saving, we revert to display mode but keep the buttons visible for further editing.
    displayCommentTextEl.style.display = 'block';
    editCommentTextarea.style.display = 'none';
    // newSaveBtn and newCancelBtn remain visible for further editing.
  });
  
  // When "Update Comment" is clicked (for existing comments)
  newUpdateBtn.addEventListener('click', async () => {
    const commentText = editCommentTextarea.value.trim();
    if (commentText === "") {
      alert("Comment cannot be empty.");
      return;
    }
    if (countSentences(commentText) > 2) {
      alert("Please limit your comment to 2 sentences.");
      return;
    }
    await updateTicketComment(currentRequestId, commentText);
    displayCommentTextEl.textContent = commentText;
    // Instead of hiding the update buttons after updating, we leave them visible.
    displayCommentTextEl.style.display = 'block';
    editCommentTextarea.style.display = 'block';
    // The "Update Comment" and "Cancel" buttons remain visible for additional edits.
  });
  
  currentRequestId = request.request_id;
  openModal();
  hideLoading();
}

/**
 * Opens the ticket modal.
 */
function openModal() {
  const modal = document.getElementById('ticketModal');
  if (modal) modal.classList.add('active');
}

/**
 * Closes the ticket modal.
 */
function closeModal() {
  const modal = document.getElementById('ticketModal');
  if (modal) modal.classList.remove('active');
}

/**
 * Sends a PUT request to update the ticket status and refreshes data.
 */
async function updateTicketStatus(requestId, newStatus) {
  const token = localStorage.getItem('token');
  if (!token) return console.error("No token found in localStorage.");
  showLoading();
  try {
    const response = await fetch(`${API_BASE_URL}/requests/${requestId}`, {
      method: 'PUT',
      headers: getAuthHeaders(token),
      body: JSON.stringify({ status: newStatus })
    });
    const result = await response.json();
    if (result.status === 'success') {
      if (document.getElementById('ticketModal').classList.contains('active')) {
        viewRequest(requestId);
        closeModal();
      }
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
 * Integrated comment creation function.
 */
async function createTicketComment(requestId, commentText) {
  const token = localStorage.getItem('token');
  if (!token) return console.error("No token found in localStorage.");
  showLoading();
  try {
    const response = await fetch(`${API_BASE_URL}/notes`, {
      method: 'POST',
      headers: getAuthHeaders(token),
      body: JSON.stringify({
        request_id: requestId,
        requirement_name: "Admin Comment",
        note: commentText
      })
    });
    const result = await response.json();
    if (result.status === "success" || result.message.indexOf("created") !== -1) {
      alert("Comment saved successfully.");
      const requestObj = allRequests.find(r => r.request_id == requestId);
      if (requestObj) {
        requestObj.note = commentText;
        if (result.data && result.data.note_id) requestObj.note_id = result.data.note_id;
      }
    } else {
      alert("Error saving comment: " + result.message);
    }
  } catch (error) {
    console.error("Error saving comment:", error);
    alert("Error saving comment.");
  } finally {
    hideLoading();
  }
}

/**
 * Integrated comment update function.
 * This function first fetches the latest note details and then sends a PUT request.
 */
async function updateTicketComment(requestId, commentText) {
  const token = localStorage.getItem('token');
  if (!token) return console.error("No token found in localStorage.");
  showLoading();
  try {
    const noteFetchResponse = await fetch(`${API_BASE_URL}/notes?request_id=${requestId}`, {
      headers: getAuthHeaders(token),
      cache: 'no-store'
    });
    const noteFetchResult = await noteFetchResponse.json();
    let noteData = noteFetchResult.data ? noteFetchResult.data : noteFetchResult;
    const requestObj = allRequests.find(r => r.request_id == requestId);
    if (Array.isArray(noteData)) {
      if (noteData.length > 0) {
        requestObj.note_id = noteData[0].note_id;
      } else {
        alert("No existing comment found. Please use 'Save Comment' to create a new comment.");
        hideLoading();
        return;
      }
    } else if (noteData && noteData.note_id) {
      requestObj.note_id = noteData.note_id;
    } else {
      alert("No existing comment found. Please use 'Save Comment' to create a new comment.");
      hideLoading();
      return;
    }
    const payload = {
      note_id: String(requestObj.note_id),
      note: commentText
    };
    const response = await fetch(`${API_BASE_URL}/notes`, {
      method: 'PUT',
      headers: getAuthHeaders(token),
      body: JSON.stringify(payload)
    });
    const result = await response.json();
    if (result.status === "success") {
      alert("Comment updated successfully.");
      requestObj.note = commentText;
    } else {
      alert(result.message);
    }
  } catch (error) {
    console.error("Error updating comment:", error);
    alert("Error updating comment.");
  } finally {
    hideLoading();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  displayUserName();
  loadRequestsUsingPagination();
  
  // Bind the search input event
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
          const name = user ? ((user.first_name || "") + " " + (user.last_name || "")).toLowerCase() : "";
          return status.includes(query) || date.includes(query) || type.includes(query) || name.includes(query);
        });
      }
      currentPage = 1;
      totalPages = Math.ceil(displayData.length / itemsPerPage);
      displayRequestsPage(currentPage);
      updatePaginationControls(currentPage);
    });
  }
  
  // Bind the update status button
  const updateStatusBtn = document.getElementById("updateStatusBtn");
  if (updateStatusBtn) {
    updateStatusBtn.addEventListener("click", function() {
      const newStatus = document.getElementById("statusSelect").value;
      if (currentRequestId) {
        updateTicketStatus(currentRequestId, newStatus);
      } else {
        console.error("No current request selected for update.");
      }
    });
  }
  
  // Sidebar active highlighting
  const currentPagePath = window.location.pathname.split('/').pop();
  const menuLinks = document.querySelectorAll('#sidebar .sidebar-menu li a');
  menuLinks.forEach(link => {
    if (link.getAttribute('href') === currentPagePath) {
      link.classList.add('active');
      link.parentElement.classList.add('active');
    } else {
      link.classList.remove('active');
      link.parentElement.classList.remove('active');
    }
  });
});

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
