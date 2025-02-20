(function() {
  // Base URL for all API calls
  const API_BASE_URL = 'http://localhost:8000/UPANG%20LINK/api';

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
   * Displays a loading indicator with a high z-index so it appears in front.
   */
  function showLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    const loadingLogo = document.getElementById('loadingLogo');
    if (loadingEl && loadingLogo) {
      loadingEl.style.zIndex = "9999";
      loadingLogo.style.zIndex = "10000";
      loadingEl.style.display = 'flex';
      loadingEl.style.justifyContent = 'center';
      loadingEl.style.alignItems = 'center';
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
   * Displays a global error alert to the user.
   * The alert automatically hides after 5 seconds.
   * @param {string} message - The error message to display.
   */
  function showErrorAlert(message) {
    let errorContainer = document.getElementById('errorContainer');
    if (!errorContainer) {
      errorContainer = document.createElement('div');
      errorContainer.id = 'errorContainer';
      errorContainer.className = 'alert alert-danger';
      // Basic styling for a fixed error alert
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
   * Fetches and displays the logged-in admin's username.
   */
  async function displayUserName() {
    console.log("Displaying logged-in admin username...");
    const token = localStorage.getItem('token');
    if (!token) {
      console.error("No token found in localStorage.");
      return;
    }
    try {
      const url = `${API_BASE_URL}/admin/users`;
      const response = await fetch(url, { method: 'GET', headers: getAuthHeaders(token) });
      if (!response.ok) {
        throw new Error(`HTTP error while fetching admin details: ${response.status}`);
      }
      const result = await response.json();
      if (result.status === 'success' && Array.isArray(result.data)) {
        const loggedAdminId = localStorage.getItem('loggedAdminId');
        const currentAdmin = loggedAdminId 
          ? result.data.find(admin => admin.admin_id == loggedAdminId)
          : result.data[0];
        if (currentAdmin && currentAdmin.username) {
          const userFullNameEl = document.getElementById('userFullName');
          if (userFullNameEl) {
            userFullNameEl.textContent = currentAdmin.username;
          }
          console.log("Logged in admin username:", currentAdmin.username);
        } else {
          throw new Error("No matching admin record found.");
        }
      } else {
        throw new Error("Error fetching admin details: " + result.message);
      }
    } catch (error) {
      console.error("Error fetching admin details:", error);
      showErrorAlert(error.message);
    }
  }

  /* ==================== Pagination Variables ==================== */
  let allUsersData = [];
  let displayData = [];
  let currentPage = 1;
  const itemsPerPage = 10;
  let totalPages = 1;

  /**
   * Fetches users data from the API and sets up pagination.
   */
  async function loadUsers() {
    console.log("Fetching users data...");
    const token = localStorage.getItem('token');
    if (!token) {
      console.error("No token found in localStorage.");
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
      // Place the Create User button above the table.
      placeCreateUserButton();
    } catch (error) {
      console.error("Error fetching users:", error);
      showErrorAlert(error.message);
    } finally {
      hideLoading();
    }
  }

  /**
   * Displays the users for the specified page.
   * @param {number} page - The page number to display.
   */
  function displayUsersPage(page) {
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = page * itemsPerPage;
    const usersToShow = displayData.slice(startIndex, endIndex);
    renderUsers(usersToShow);
  }

  /**
   * Renders the provided list of users into the table.
   * The table columns follow this order:
   * 1. User ID
   * 2. Student No.
   * 3. Name (First + Last)
   * 4. Email
   * 5. Role
   * 6. Course
   * 7. Year Level
   * 8. Block
   * 9. Admission Year
   * 10. Action
   * @param {Array} users - List of user objects.
   */
  function renderUsers(users) {
    console.log("Rendering users in table...");
    let tbody = document.getElementById('requestsTableBody');
    if (!tbody) {
      console.error("Table body element (requestsTableBody) not found in the DOM. Creating one dynamically.");
      const container = document.getElementById('usersTableContainer') || document.body;
      const table = document.createElement('table');
      table.className = "table";
      tbody = document.createElement('tbody');
      tbody.id = "requestsTableBody";
      table.appendChild(tbody);
      container.appendChild(table);
    }
    if (!users || users.length === 0) {
      tbody.innerHTML = `<tr><td colspan="10" class="text-center">No users to display</td></tr>`;
      console.log("No users to display.");
      return;
    }
    tbody.innerHTML = users.map(user => {
      return `
        <tr>
          <td>${user.user_id || 'N/A'}</td>
          <td>${user.student_number || 'N/A'}</td>
          <td>${user.first_name || 'N/A'} ${user.last_name || ''}</td>
          <td>${user.email || 'N/A'}</td>
          <td>${user.role || 'N/A'}</td>
          <td>${user.course || 'N/A'}</td>
          <td>${user.year_level || 'N/A'}</td>
          <td>${user.block || 'N/A'}</td>
          <td>${user.admission_year || 'N/A'}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="viewUser(${user.user_id})">View</button>
          </td>
        </tr>
      `;
    }).join('');
    console.log("Users table updated.");
  }

  /**
   * Updates the pagination controls (Previous/Next buttons and page count).
   */
  function updatePaginationControls() {
    totalPages = Math.ceil(displayData.length / itemsPerPage);

    // Dynamically create the pagination container if not present.
    let paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) {
      paginationContainer = document.createElement('div');
      paginationContainer.id = 'paginationContainer';
      paginationContainer.className = "d-flex justify-content-end my-3";
      const tableContainer = document.getElementById('usersTableContainer');
      if (tableContainer) {
        tableContainer.appendChild(paginationContainer);
      } else {
        document.body.appendChild(paginationContainer);
      }
    }
    
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
   * Inserts the "Create User" button above the table.
   */
  function placeCreateUserButton() {
    const table = document.querySelector('table.table');
    if (!table) {
      console.warn("No table with class 'table' found.");
      return;
    }
    // Check if the button is already inserted.
    if (document.getElementById('createUserBtn')) return;

    const createUserBtn = document.createElement('button');
    createUserBtn.id = 'createUserBtn';
    createUserBtn.className = "btn btn-primary mb-3"; 
    createUserBtn.textContent = "Create User";
    createUserBtn.addEventListener("click", showCreateUserModal);
    // Insert the button above the table.
    table.parentElement.insertBefore(createUserBtn, table);
  }

  /**
   * Opens a modal with the full user details in editable textboxes.
   * The "Student Number" is displayed as plain text (non-editable).
   * When "Save changes" is clicked, the updateUser function is called.
   * @param {number|string} userId - The ID of the user to view/edit.
   */
  function viewUser(userId) {
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
        </div>
      `;
      document.body.appendChild(modal);
    }
    
    // Populate the plain text for Student Number.
    const displayStudentNumber = modal.querySelector("#displayStudentNumber");
    if (displayStudentNumber) {
      displayStudentNumber.textContent = user.student_number || "";
    }
    
    // Populate the editable fields.
    modal.querySelector("#firstName").value = user.first_name || "";
    modal.querySelector("#lastName").value = user.last_name || "";
    modal.querySelector("#email").value = user.email || "";
    modal.querySelector("#role").value = user.role || "";
    modal.querySelector("#course").value = user.course || "";
    modal.querySelector("#yearLevel").value = user.year_level || "";
    modal.querySelector("#block").value = user.block || "";
    modal.querySelector("#admissionYear").value = user.admission_year || "";
    
    const saveUserBtn = modal.querySelector("#saveUserBtn");
    if (saveUserBtn) {
      saveUserBtn.onclick = function() {
        updateUser(user.user_id);
      };
    }
    
    let bootstrapModal = bootstrap.Modal.getInstance(modal);
    if (!bootstrapModal) {
      bootstrapModal = new bootstrap.Modal(modal);
    }
    bootstrapModal.show();
  }

  /**
   * Updates the user by sending a PUT request with the modified data.
   * Uses the update API endpoint: `${API_BASE_URL}/auth/users/{userId}`
   * If a field is left empty, the original value is posted.
   * @param {number|string} userId - The ID of the user to update.
   */
  async function updateUser(userId) {
    const token = localStorage.getItem('token');
    if (!token) {
      console.error("No token found in localStorage.");
      return;
    }

    const originalUser = allUsersData.find(u => u.user_id == userId);
    if (!originalUser) {
      console.error("Original user not found for ID:", userId);
      return;
    }

    const modal = document.getElementById("userModal");
    if (!modal) {
      console.error("Modal not found!");
      return;
    }

    const form = modal.querySelector("#userForm");
    if (!form) {
      console.error("User form not found!");
      return;
    }
    const formData = new FormData(form);

    // For each field, if a new value is provided, use it; otherwise, fallback to the original value.
    const updatedUser = {
      student_number: originalUser.student_number, // remains unchanged
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
      showLoading();
      const response = await fetch(`${API_BASE_URL}/auth/users/${userId}`, {
        method: 'PUT',
        headers: getAuthHeaders(token),
        body: JSON.stringify(updatedUser)
      });

      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP error ${response.status}: ${errorText}`);
      }

      const result = await response.json();
      if (result.status === 'success') {
        console.log("User updated successfully");
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) modalInstance.hide();
        loadUsers();
      } else {
        throw new Error("Error updating user: " + result.message);
      }
    } catch (error) {
      console.error("Error updating user:", error);
      showErrorAlert(error.message);
    } finally {
      hideLoading();
    }
  }

  /* ==================== Create User Functionality ==================== */
  /**
   * Opens a modal form to create a new user.
   * This modal uses the same fields as the update form, but all inputs are empty.
   * A new password field has been added.
   */
  function showCreateUserModal() {
    let modal = document.getElementById("createUserModal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "createUserModal";
      modal.className = "modal fade";
      modal.setAttribute("tabindex", "-1");
      modal.innerHTML = `
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Create User</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="createUserForm">
                <div class="mb-3">
                  <label for="studentNumberCreate" class="form-label">Student Number</label>
                  <input type="text" class="form-control" id="studentNumberCreate" name="student_number">
                  <div id="studentNumberError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="firstNameCreate" class="form-label">First Name</label>
                  <input type="text" class="form-control" id="firstNameCreate" name="first_name">
                  <div id="firstNameError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="lastNameCreate" class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="lastNameCreate" name="last_name">
                  <div id="lastNameError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="emailCreate" class="form-label">Email</label>
                  <input type="email" class="form-control" id="emailCreate" name="email">
                  <div id="emailError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="passwordCreate" class="form-label">Password</label>
                  <input type="password" class="form-control" id="passwordCreate" name="password">
                  <div id="passwordError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="roleCreate" class="form-label">Role</label>
                  <input type="text" class="form-control" id="roleCreate" name="role">
                  <div id="roleError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="courseCreate" class="form-label">Course</label>
                  <input type="text" class="form-control" id="courseCreate" name="course">
                  <div id="courseError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="yearLevelCreate" class="form-label">Year Level</label>
                  <input type="text" class="form-control" id="yearLevelCreate" name="year_level">
                  <div id="yearLevelError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="blockCreate" class="form-label">Block</label>
                  <input type="text" class="form-control" id="blockCreate" name="block">
                  <div id="blockError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                  <label for="admissionYearCreate" class="form-label">Admission Year</label>
                  <input type="text" class="form-control" id="admissionYearCreate" name="admission_year">
                  <div id="admissionYearError" class="invalid-feedback"></div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" id="saveCreateUserBtn">Create User</button>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }
    
    const createUserBtn = modal.querySelector("#saveCreateUserBtn");
    if (createUserBtn) {
      createUserBtn.onclick = function() {
        createUser();
      };
    }
    
    let bootstrapModal = bootstrap.Modal.getInstance(modal);
    if (!bootstrapModal) {
      bootstrapModal = new bootstrap.Modal(modal);
    }
    bootstrapModal.show();
  }

  /**
   * Collects the data from the create user form and sends a POST request to register the new user.
   * Performs client-side validation for each required field. If an error is found, it is displayed under
   * the corresponding textbox. Upon a successful account creation, the form is reset.
   */
  async function createUser() {
    const token = localStorage.getItem('token');
    if (!token) {
      console.error("No token found in localStorage.");
      return;
    }
    
    const modal = document.getElementById("createUserModal");
    if (!modal) {
      console.error("Create user modal not found!");
      return;
    }
    
    const form = modal.querySelector("#createUserForm");
    if (!form) {
      console.error("Create user form not found!");
      return;
    }
    
    // Gather form data
    const formData = new FormData(form);
    
    const newUser = {
      student_number: formData.get('student_number')?.trim() || "",
      first_name: formData.get('first_name')?.trim() || "",
      last_name: formData.get('last_name')?.trim() || "",
      email: formData.get('email')?.trim() || "",
      password: formData.get('password')?.trim() || "",
      role: formData.get('role')?.trim() || "",
      course: formData.get('course')?.trim() || "",
      year_level: formData.get('year_level')?.trim() || "",
      block: formData.get('block')?.trim() || "",
      admission_year: formData.get('admission_year')?.trim() || ""
    };
    
    // Clear previous errors for all fields
    const fields = ["studentNumber", "firstName", "lastName", "email", "password", "role", "course", "yearLevel", "block", "admissionYear"];
    fields.forEach(field => {
      const input = document.getElementById(field + "Create");
      const errorEl = document.getElementById(field + "Error");
      if (input && errorEl) {
        errorEl.textContent = "";
        input.classList.remove("is-invalid");
      }
    });
    
    // Validation: Check each required field
    let hasError = false;
    if (!newUser.student_number) {
      const input = document.getElementById("studentNumberCreate");
      const errorEl = document.getElementById("studentNumberError");
      errorEl.textContent = "Student number is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    // Check duplicate for student number
    if (allUsersData.some(u => u.student_number === newUser.student_number)) {
      const input = document.getElementById("studentNumberCreate");
      const errorEl = document.getElementById("studentNumberError");
      errorEl.textContent = "Student number has duplicate";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.first_name) {
      const input = document.getElementById("firstNameCreate");
      const errorEl = document.getElementById("firstNameError");
      errorEl.textContent = "First name is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.last_name) {
      const input = document.getElementById("lastNameCreate");
      const errorEl = document.getElementById("lastNameError");
      errorEl.textContent = "Last name is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.email) {
      const input = document.getElementById("emailCreate");
      const errorEl = document.getElementById("emailError");
      errorEl.textContent = "Email is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.password) {
      const input = document.getElementById("passwordCreate");
      const errorEl = document.getElementById("passwordError");
      errorEl.textContent = "Password is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.role) {
      const input = document.getElementById("roleCreate");
      const errorEl = document.getElementById("roleError");
      errorEl.textContent = "Role is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.course) {
      const input = document.getElementById("courseCreate");
      const errorEl = document.getElementById("courseError");
      errorEl.textContent = "Course is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.year_level) {
      const input = document.getElementById("yearLevelCreate");
      const errorEl = document.getElementById("yearLevelError");
      errorEl.textContent = "Year level is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.block) {
      const input = document.getElementById("blockCreate");
      const errorEl = document.getElementById("blockError");
      errorEl.textContent = "Block is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    if (!newUser.admission_year) {
      const input = document.getElementById("admissionYearCreate");
      const errorEl = document.getElementById("admissionYearError");
      errorEl.textContent = "Admission year is missing";
      input.classList.add("is-invalid");
      hasError = true;
    }
    
    // If any error exists, do not proceed with API call.
    if (hasError) return;
    
    try {
      showLoading();
      const response = await fetch(`${API_BASE_URL}/auth/register`, {
        method: 'POST',
        headers: getAuthHeaders(token),
        body: JSON.stringify(newUser)
      });
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP error ${response.status}: ${errorText}`);
      }
      const result = await response.json();
      if (result.status === 'success') {
        console.log("User created successfully");
        // Clear the form (reset all fields and errors)
        form.reset();
        fields.forEach(field => {
          const input = document.getElementById(field + "Create");
          const errorEl = document.getElementById(field + "Error");
          if (input && errorEl) {
            errorEl.textContent = "";
            input.classList.remove("is-invalid");
          }
        });
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) modalInstance.hide();
        loadUsers();
      } else {
        throw new Error("Error creating user: " + result.message);
      }
    } catch (error) {
      console.error("Error creating user:", error);
      showErrorAlert(error.message);
    } finally {
      hideLoading();
    }
  }

  /* ==================== Logout Functionality ==================== */
  const auth = {
    /**
     * Logs out the current admin by calling the logout API,
     * removes stored tokens, and redirects to the login page.
     */
    logout: async function() {
      console.log("Attempting logout...");
      showLoading();

      const token = localStorage.getItem('token');
      if (token) {
        try {
          const response = await fetch(`${API_BASE_URL}/admin/logout`, {
            method: 'POST',
            headers: getAuthHeaders(token)
          });
          const result = await response.json();
          if (result.status === 'success') {
            console.log('Logout successful:', result.message);
          } else {
            throw new Error("Logout failed: " + result.message);
          }
        } catch (error) {
          console.error('Error during logout:', error);
          showErrorAlert(error.message);
        }
      }
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = 'login.html';
    }
  };

  // Expose auth globally so that it can be accessed outside this IIFE
  window.auth = auth;

  /* ==================== DOMContentLoaded Event ==================== */
  document.addEventListener('DOMContentLoaded', () => {
    displayUserName();
    loadUsers();

    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
      searchInput.addEventListener("input", function() {
        const query = this.value.trim().toLowerCase();
        if (query === "") {
          displayData = allUsersData;
        } else {
          displayData = allUsersData.filter(user => {
            return (user.student_number && user.student_number.toLowerCase().includes(query)) ||
                   (user.first_name && user.first_name.toLowerCase().includes(query)) ||
                   (user.last_name && user.last_name.toLowerCase().includes(query)) ||
                   (user.email && user.email.toLowerCase().includes(query)) ||
                   (user.role && user.role.toLowerCase().includes(query)) ||
                   (user.course && user.course.toLowerCase().includes(query)) ||
                   (user.year_level && user.year_level.toString().toLowerCase().includes(query)) ||
                   (user.block && user.block.toLowerCase().includes(query)) ||
                   (user.admission_year && user.admission_year.toLowerCase().includes(query));
          });
        }
        currentPage = 1;
        updatePaginationControls();
        displayUsersPage(currentPage);
      });
    }
  });

  // Expose viewUser globally for inline event handlers.
  window.viewUser = viewUser;
})();
