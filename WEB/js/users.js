(function() {
  // Base URL for API calls
  const API_BASE_URL = 'http://localhost:8000/UPANG%20LINK';

  /**
   * Returns headers for authenticated requests.
   */
  function getAuthHeaders(token) {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    };
  }

  /**
   * Displays a loading indicator.
   */
  function showLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    const loadingLogo = document.getElementById('loadingLogo');
    if (loadingEl && loadingLogo) {
      loadingEl.style.display = 'flex';
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
   * Displays a global error alert.
   */
  function showErrorAlert(message) {
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
   * Retrieves the token from localStorage.
   */
  function getToken() {
    return localStorage.getItem('token');
  }

  // Global variables for users and pagination.
  let allUsersData = [];
  let displayData = [];
  let currentPage = 1;
  const itemsPerPage = 10;
  let totalPages = 1;

  /**
   * Fetches the logged-in admin's info from /auth/users and displays the username.
   */
  async function displayUserName() {
    const token = getToken();
    if (!token) {
      console.error("No token found.");
      return;
    }
    const endpoint = `${API_BASE_URL}/auth/users`;
    try {
      const response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
      const result = await response.json();
      if (response.ok && result.status === 'success' && result.data.length > 0) {
        // Use the first user record for admin info.
        const currentUser = result.data[0];
        const nameEl = document.getElementById('userFullName');
        if (nameEl) {
          // Prefer username if available.
          // Note: Removed course and block. Only first name is used now.
          const displayName = currentUser.username || `${currentUser.first_name || ''}`.trim();
          nameEl.textContent = displayName;
        }
      } else {
        throw new Error("Unable to fetch admin details.");
      }
    } catch (error) {
      console.error("Error fetching admin details:", error);
      showErrorAlert(error.message);
    }
  }

  /**
   * Loads all users from /auth/users and initializes pagination.
   */
  async function loadUsers() {
    const token = getToken();
    if (!token) {
      console.error("No token found.");
      return;
    }
    try {
      showLoading();
      const response = await fetch(`${API_BASE_URL}/auth/users`, { headers: getAuthHeaders(token) });
      if (!response.ok) {
        throw new Error(`HTTP error fetching users: ${response.status}`);
      }
      const usersData = await response.json();
      if (usersData.status !== 'success') {
        throw new Error("Error in users data: " + usersData.message);
      }
      allUsersData = usersData.data;
      displayData = allUsersData;
      currentPage = 1;
      updatePaginationControls();
      displayUsersPage(currentPage);
    } catch (error) {
      console.error("Error fetching users:", error);
      showErrorAlert(error.message);
    } finally {
      hideLoading();
    }
  }

  /**
   * Displays a page of users.
   */
  function displayUsersPage(page) {
    const startIndex = (page - 1) * itemsPerPage;
    const usersToShow = displayData.slice(startIndex, startIndex + itemsPerPage);
    renderUsers(usersToShow);
  }

  /**
   * Renders users into the table body (using the existing <tbody id="usersTableBody">).
   * Only the following columns are displayed:
   * User ID, Name, Email, Year Level, Admission Year.
   */
  function renderUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    if (!users || users.length === 0) {
      // Now there are 5 columns
      tbody.innerHTML = `<tr><td colspan="5" class="text-center">No users to display</td></tr>`;
    } else {
      tbody.innerHTML = users.map(user => `
        <tr>
          <td>${user.user_id || 'N/A'}</td>
          <td>${user.first_name || 'N/A'} ${user.last_name || ''}</td>
          <td>${user.email || 'N/A'}</td>
          <td>${user.year_level || 'N/A'}</td>
          <td>${user.admission_year || 'N/A'}</td>
        </tr>
      `).join('');
    }
  }

  /**
   * Updates pagination controls in the element with ID "paginationContainer".
   */
  function updatePaginationControls() {
    totalPages = Math.ceil(displayData.length / itemsPerPage);
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) return;
    let controlsHtml = "";
    if (currentPage > 1) {
      controlsHtml += `<button id="prevPage" class="btn btn-secondary btn-sm me-2">Previous</button>`;
    }
    controlsHtml += `<span>Page ${currentPage} of ${totalPages}</span>`;
    if (currentPage < totalPages) {
      controlsHtml += `<button id="nextPage" class="btn btn-secondary btn-sm ms-2">Next</button>`;
    }
    paginationContainer.innerHTML = controlsHtml;
    if (currentPage > 1) {
      document.getElementById("prevPage").addEventListener("click", () => {
        currentPage--;
        displayUsersPage(currentPage);
        updatePaginationControls();
      });
    }
    if (currentPage < totalPages) {
      document.getElementById("nextPage").addEventListener("click", () => {
        currentPage++;
        displayUsersPage(currentPage);
        updatePaginationControls();
      });
    }
  }

  /**
   * Logout functionality using /auth/logout.
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
  
  window.auth = auth;

  document.addEventListener('DOMContentLoaded', async () => {
    await displayUserName();
    await loadUsers();
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
      searchInput.addEventListener("input", function() {
        const query = this.value.trim().toLowerCase();
        // Updated search: only first name, last name, email, year level, and admission year are checked.
        displayData = query ? allUsersData.filter(user =>
          (user.first_name && user.first_name.toLowerCase().includes(query)) ||
          (user.last_name && user.last_name.toLowerCase().includes(query)) ||
          (user.email && user.email.toLowerCase().includes(query)) ||
          (user.year_level && user.year_level.toString().toLowerCase().includes(query)) ||
          (user.admission_year && user.admission_year.toLowerCase().includes(query))
        ) : allUsersData;
        currentPage = 1;
        updatePaginationControls();
        displayUsersPage(currentPage);
      });
    }
  });
})();
