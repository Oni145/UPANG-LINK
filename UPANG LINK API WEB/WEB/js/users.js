(function() {
  /**
   * Returns headers for authenticated requests.
   */
  function checkTokenAndRedirect() {
    const token = localStorage.getItem('token');
    if (!token) {
      window.location.href = "./login.html"; // Change to your actual login page
    }
  }
  
  /**
   * Returns common headers for authenticated requests.
   * Ensures user is authenticated before returning headers.
   * @returns {Object} The headers object.
   */
  function getAuthHeaders() {
    checkTokenAndRedirect(); // Check for token
    const token = localStorage.getItem('token'); // Get the token after the check
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    };
  }
  
  // Example usage
  const headers = getAuthHeaders();
  
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
    const token = localStorage.getItem('token'); // Get the token from local storage
  
    if (!token) {
      console.error("No token found in localStorage.");
      return;
    }
  
    try {
      // Use the "me" endpoint to get the logged-in user details
      const endpoint = `${API_BASE_URL}/admin/users/me`; 
      const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`, // Include the token in the headers
        },
      });
  
      const result = await response.json();
      console.log("API Response:", result); // Debugging
  
      if (response.ok && result.status === 'success') {
        const currentUser = result.data;
  
        console.log("Fetched user:", currentUser); // Debugging
  
        // Construct display name
        const displayName = `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim();
  
        // Display user name in the element with id "userFullName"
        const userFullNameEl = document.getElementById('userFullName');
        if (userFullNameEl) {
          userFullNameEl.textContent = displayName || 'Unknown User';
        }
      } else {
        throw new Error("Unable to fetch user details.");
      }
    } catch (error) {
      console.error("Error fetching user details:", error);
    }
  }
  /**
   * Loads all users from /auth/users and initializes pagination.
   */

// Function to load users from the API
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
   */
  function renderUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;

    if (users.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center">NO USERS TO DISPLAY</td></tr>`;
    } else {
        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.user_id || 'N/A'}</td>
                <td>${user.first_name || 'N/A'} ${user.last_name || ''}</td>
                <td>${user.email || 'N/A'}</td>
                <td>${user.role || 'N/A'}</td>
                <td>
                    <button class="view-btn" onclick="openUserModal(${user.user_id})">
                        <i class="fas fa-eye"></i> <span>VIEW</span>
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Function to open the user modal with user data
// Function to open the modal
function openUserModal(userId) {
  const user = allUsersData.find(u => u.user_id === userId); // Find user by user_id in allUsersData
  if (!user) return;

  // Populate the modal with user data
  document.getElementById('modalUserId').textContent = user.user_id || 'N/A';
  document.getElementById('modalUserName').textContent = `${user.first_name || ''} ${user.last_name || ''}`;
  document.getElementById('modalUserEmail').textContent = user.email || 'N/A';

  // New fields from your user data
  document.getElementById('modalUserStudentNumber').textContent = user.student_number || 'N/A';
  document.getElementById('modalUserBirthdate').textContent = user.birthdate || 'N/A';
  document.getElementById('modalUserEmergencyContact').textContent = user.emergency_contact || 'N/A';
  document.getElementById('modalUserCourse').textContent = user.course || 'N/A';
  document.getElementById('modalUserCurrentYear').textContent = user.current_year || 'N/A';

  // Show the modal
  document.getElementById('userModal').style.display = "flex";
}

// Function to close the modal
function closeUserModal() {
  document.getElementById('userModal').style.display = "none";
}

// Close the modal if the user clicks outside of the modal content
window.onclick = function(event) {
  const modal = document.getElementById('userModal');
  if (event.target === modal) {
    closeUserModal();
  }
}

// Make sure it's globally accessible
window.openUserModal = openUserModal;
window.closeUserModal = closeUserModal;

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
            
            displayData = query ? allUsersData.filter(user => {
                // Get role as string (since renderUsers uses user.role)
                const userRole = user.role ? user.role.toLowerCase() : "";
                
                return (
                    (user.first_name && user.first_name.toLowerCase().includes(query)) ||
                    (user.last_name && user.last_name.toLowerCase().includes(query)) ||
                    (user.email && user.email.toLowerCase().includes(query)) ||
                    (user.year_level && user.year_level.toString().toLowerCase().includes(query)) ||
                    (user.admission_year && user.admission_year.toLowerCase().includes(query)) ||
                    userRole.includes(query) // Now checks the singular 'role' field
                );
            }) : allUsersData;
            
            currentPage = 1;
            updatePaginationControls();
            displayUsersPage(currentPage);
        });
    }
});
})();


// Wait for DOM to be fully loaded before accessing elements
document.addEventListener('DOMContentLoaded', function() {
  // DOM Elements
  const adminAddModal = document.getElementById("admin-add-modal");
  const addAdminBtn = document.getElementById("addAdminBtn");
  const closeModal = document.querySelector("#admin-add-modal .close-modal");
  const adminAddForm = document.getElementById("admin-add-form");

  // Modal Functions
  function openAdminModal() {
    adminAddModal.style.display = "block";
    document.getElementById("adminEmail").focus();
  }

  function closeAdminModal() {
    adminAddModal.style.display = "none";
  }

  // Event Listeners
  addAdminBtn.addEventListener("click", openAdminModal);
  closeModal.addEventListener("click", closeAdminModal);

  window.addEventListener("click", (event) => {
    if (event.target === adminAddModal) {
      closeAdminModal();
    }
  });

  // Form submission
  adminAddForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    
    // Clear previous errors
    const emailField = document.getElementById("adminEmail");
    emailField.classList.remove('error-field');
    const existingError = document.querySelector('.email-error');
    if (existingError) existingError.remove();

    const adminData = {
        first_name: document.getElementById("adminFirstName").value,
        last_name: document.getElementById("adminLastName").value,
        email: emailField.value,
        password: document.getElementById("adminPassword").value
    };

    try {
        const response = await fetch(`${API_BASE_URL}/admin/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(adminData)
        });

        const data = await response.json();

        // First check if the response contains an error message
        if (data.message && data.message.toLowerCase().includes('already registered')) {
            // Handle duplicate email case
            showEmailError(data.message);
            return;
        }

        // Then check the response status
        if (!response.ok) {
            throw new Error(data.message || 'Registration failed');
        }

        // Only show success if we get here
        alert('Admin created successfully!');
        closeAdminModal();
        adminAddForm.reset();
        
        if (typeof fetchAdmins === 'function') {
            fetchAdmins();
        }

    } catch (error) {
        console.error('Registration Error:', error);
        showEmailError(error.message || 'Error creating admin');
    }
});

// Helper function to display email errors
function showEmailError(message) {
    const emailField = document.getElementById("adminEmail");
    emailField.classList.add('error-field');
    emailField.focus();
    
    let errorDisplay = emailField.nextElementSibling;
    if (!errorDisplay || !errorDisplay.classList.contains('email-error')) {
        errorDisplay = document.createElement('div');
        errorDisplay.className = 'email-error';
        emailField.parentNode.insertBefore(errorDisplay, emailField.nextSibling);
    }
    errorDisplay.textContent = message;
}
});