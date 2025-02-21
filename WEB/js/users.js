(function() {
  // Base URL for all API calls
  const API_BASE_URL = 'http://localhost:8000/UPANG%20LINK/api';

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
   * Displays a loading indicator.
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
   * Marks an input as invalid and displays an error message.
   */
  function markError(inputId, errorId, message) {
    const input = document.getElementById(inputId),
          errorEl = document.getElementById(errorId);
    if (input && errorEl) {
      errorEl.textContent = message;
      input.classList.add("is-invalid");
    }
  }

  /**
   * Returns the token stored in localStorage.
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
   * Fetches the logged-in user's data using the token.
   * It first attempts the admin endpoint.
   * If successful, sets window.currentUserRole to 'admin'.
   * Otherwise, it tries the staff endpoint and sets role to 'staff'.
   */
  async function displayUserName() {
    console.log("Displaying logged-in user name...");
    const token = getToken();
    if (!token) {
      console.error("No token found.");
      return;
    }
    let endpoint = `${API_BASE_URL}/admin/users`;
    let result;
    try {
      // Try fetching admin credentials.
      let response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
      result = await response.json();
      if (response.ok && result.status === 'success') {
        window.currentUserRole = 'admin';
        console.log("Admin endpoint successful. Detected role: admin.");
      } else {
        // Fallback: try staff endpoint.
        endpoint = `${API_BASE_URL}/staff/`;
        response = await fetch(endpoint, { method: 'GET', headers: getAuthHeaders(token) });
        result = await response.json();
        if (response.ok && result.status === 'success') {
          window.currentUserRole = 'staff';
          console.log("Staff endpoint successful. Detected role: staff.");
        } else {
          throw new Error("Unable to fetch user details from either endpoint.");
        }
      }
      // Use the first record for display.
      let currentUser = result.data[0];
      const nameEl = document.getElementById('userFullName');
      if (nameEl) {
        const displayName = currentUser.username || `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim();
        nameEl.textContent = displayName;
      }
      console.log("Logged in user data:", currentUser);
    } catch (error) {
      console.error("Error fetching user details:", error);
      showErrorAlert(error.message);
    }
  }

  /**
   * Loads all users and initializes pagination.
   */
  async function loadUsers() {
    console.log("Fetching users data...");
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
      placeCreateUserButton();
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
   * Renders users in the table.
   * The "View" button is shown only if the detected role is admin.
   */
  function renderUsers(users) {
    let tbody = document.getElementById('requestsTableBody');
    if (!tbody) {
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
      return;
    }
    tbody.innerHTML = users.map(user => {
      const viewBtn = (window.currentUserRole === 'admin')
        ? `<button class="btn btn-sm btn-primary" onclick="viewUser(${user.user_id})">View</button>`
        : '';
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
          <td>${viewBtn}</td>
        </tr>`;
    }).join('');
  }

  /**
   * Updates pagination controls.
   */
  function updatePaginationControls() {
    totalPages = Math.ceil(displayData.length / itemsPerPage);
    let paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) {
      paginationContainer = document.createElement('div');
      paginationContainer.id = 'paginationContainer';
      paginationContainer.className = "d-flex justify-content-end my-3";
      (document.getElementById('usersTableContainer') || document.body).appendChild(paginationContainer);
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
   * Inserts the "Create User" button if the detected role is admin.
   */
 
  /**
   * Opens a modal to view/update a user's details.
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
    modal.querySelector("#saveUserBtn").onclick = () => updateUser(user.user_id);
    (bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)).show();
  }

  /**
   * Sends a PUT request to update a user's details.
   */
  async function updateUser(userId) {
    const token = getToken();
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
      showLoading();
      const response = await fetch(`${API_BASE_URL}/auth/users/${userId}`, {
        method: 'PUT',
        headers: getAuthHeaders(token),
        body: JSON.stringify(updatedUser)
      });
      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      const result = await response.json();
      if (result.status === 'success') {
        bootstrap.Modal.getInstance(modal)?.hide();
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

  /**
   * Builds the modal structure for creating or viewing a user.
   */
  function createUserModalStructure(modalId, title, isCreate) {
    let modal = document.createElement("div");
    modal.id = modalId;
    modal.className = "modal fade";
    modal.setAttribute("tabindex", "-1");
    modal.innerHTML = isCreate ?
      `<div class="modal-dialog modal-lg">
         <div class="modal-content">
           <div class="modal-header">
             <h5 class="modal-title">${title}</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
           </div>
           <div class="modal-body">
             <form id="createUserForm">${modalFormFields(true)}</form>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
             <button type="button" class="btn btn-primary" id="saveCreateUserBtn">Create User</button>
           </div>
         </div>
       </div>` :
      `<div class="modal-dialog modal-lg">
         <div class="modal-content">
           <div class="modal-header">
             <h5 class="modal-title">${title}</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
           </div>
           <div class="modal-body">
             <div class="mb-3">
               <label class="form-label">Student Number</label>
               <div class="form-control-plaintext" id="displayStudentNumber"></div>
             </div>
             <form id="userForm">${modalFormFields(false)}</form>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
             <button type="button" class="btn btn-primary" id="saveUserBtn">Save changes</button>
           </div>
         </div>
       </div>`;
    document.body.appendChild(modal);
    return modal;
  }

  /**
   * Generates form fields.
   */
  function modalFormFields(isCreate) {
    const fields = [
      { id: "studentNumber", name: "student_number", label: "Student Number", type: "text", show: isCreate },
      { id: "firstName", name: "first_name", label: "First Name", type: "text", show: true },
      { id: "lastName", name: "last_name", label: "Last Name", type: "text", show: true },
      { id: "email", name: "email", label: "Email", type: isCreate ? "email" : "text", show: true },
      { id: "password", name: "password", label: "Password", type: "password", show: isCreate },
      { id: "role", name: "role", label: "Role", type: "text", show: true },
      { id: "course", name: "course", label: "Course", type: "text", show: true },
      { id: "yearLevel", name: "year_level", label: "Year Level", type: "text", show: true },
      { id: "block", name: "block", label: "Block", type: "text", show: true },
      { id: "admissionYear", name: "admission_year", label: "Admission Year", type: "text", show: true }
    ];
    return fields.filter(f => f.show).map(f => `
      <div class="mb-3">
        <label for="${f.id}${isCreate ? "Create" : ""}" class="form-label">${f.label}</label>
        <input type="${f.type}" class="form-control" id="${f.id}${isCreate ? "Create" : ""}" name="${f.name}">
        ${isCreate ? `<div id="${f.id}Error" class="invalid-feedback"></div>` : ""}
      </div>`).join('');
  }

  /**
   * Shows the modal for creating a user.
   */
  function showCreateUserModal() {
    let modal = document.getElementById("createUserModal") || createUserModalStructure("createUserModal", "Create User", true);
    modal.querySelector("#saveCreateUserBtn").onclick = createUser;
    (bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)).show();
  }

  /**
   * Sends a POST request to create a new user.
   */
  async function createUser() {
    const token = getToken();
    if (!token) {
      console.error("No token found in localStorage.");
      return;
    }
    const modal = document.getElementById("createUserModal");
    const form = modal.querySelector("#createUserForm");
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

    const fields = ["studentNumber", "firstName", "lastName", "email", "password", "role", "course", "yearLevel", "block", "admissionYear"];
    fields.forEach(field => {
      const inp = document.getElementById(field + "Create"),
            err = document.getElementById(field + "Error");
      if (inp && err) {
        err.textContent = "";
        inp.classList.remove("is-invalid");
      }
    });

    let hasError = false;
    if (!newUser.student_number) {
      markError("studentNumberCreate", "studentNumberError", "Student number is missing");
      hasError = true;
    }
    if (allUsersData.some(u => u.student_number === newUser.student_number)) {
      markError("studentNumberCreate", "studentNumberError", "Student number duplicate");
      hasError = true;
    }
    if (!newUser.first_name) {
      markError("firstNameCreate", "firstNameError", "First name is missing");
      hasError = true;
    }
    if (!newUser.last_name) {
      markError("lastNameCreate", "lastNameError", "Last name is missing");
      hasError = true;
    }
    if (!newUser.email) {
      markError("emailCreate", "emailError", "Email is missing");
      hasError = true;
    }
    if (!newUser.password) {
      markError("passwordCreate", "passwordError", "Password is missing");
      hasError = true;
    }
    if (!newUser.role) {
      markError("roleCreate", "roleError", "Role is missing");
      hasError = true;
    }
    if (!newUser.course) {
      markError("courseCreate", "courseError", "Course is missing");
      hasError = true;
    }
    if (!newUser.year_level) {
      markError("yearLevelCreate", "yearLevelError", "Year level is missing");
      hasError = true;
    }
    if (!newUser.block) {
      markError("blockCreate", "blockError", "Block is missing");
      hasError = true;
    }
    if (!newUser.admission_year) {
      markError("admissionYearCreate", "admissionYearError", "Admission year is missing");
      hasError = true;
    }
    if (hasError) return;
    
    try {
      showLoading();
      const response = await fetch(`${API_BASE_URL}/auth/register`, {
        method: 'POST',
        headers: getAuthHeaders(token),
        body: JSON.stringify(newUser)
      });
      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      const result = await response.json();
      if (result.status === 'success') {
        console.log("User created successfully");
        form.reset();
        fields.forEach(field => {
          const inp = document.getElementById(field + "Create"),
                err = document.getElementById(field + "Error");
          if (inp && err) {
            err.textContent = "";
            inp.classList.remove("is-invalid");
          }
        });
        bootstrap.Modal.getInstance(modal)?.hide();
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

  /**
   * Sends a PUT request to update a user's details.
   */
  async function updateUser(userId) {
    const token = getToken();
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
      showLoading();
      const response = await fetch(`${API_BASE_URL}/auth/users/${userId}`, {
        method: 'PUT',
        headers: getAuthHeaders(token),
        body: JSON.stringify(updatedUser)
      });
      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      const result = await response.json();
      if (result.status === 'success') {
        bootstrap.Modal.getInstance(modal)?.hide();
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

  // Logout and event listeners.
  const auth = {
    logout: async function() {
      console.log("Attempting logout...");
      showLoading();
      const token = getToken();
      if (token) {
        try {
          const userType = window.currentUserRole || 'admin';
          let logoutEndpoint = (userType === 'staff')
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
          console.log('Logout successful:', result.message);
        } catch (error) {
          console.error("Logout error:", error);
          showErrorAlert(error.message);
        }
      }
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = 'login.html';
    }
  };

  window.auth = auth;
  window.viewUser = function(userId) {
    const user = allUsersData.find(u => u.user_id == userId);
    if (!user) {
      console.error("User not found:", userId);
      return;
    }
    let modal = document.getElementById("userModal") || createUserModalStructure("userModal", "User Details", false);
    modal.querySelector("#displayStudentNumber").textContent = user.student_number || "";
    modal.querySelector("#firstName").value = user.first_name || "";
    modal.querySelector("#lastName").value = user.last_name || "";
    modal.querySelector("#email").value = user.email || "";
    modal.querySelector("#role").value = user.role || "";
    modal.querySelector("#course").value = user.course || "";
    modal.querySelector("#yearLevel").value = user.year_level || "";
    modal.querySelector("#block").value = user.block || "";
    modal.querySelector("#admissionYear").value = user.admission_year || "";
    modal.querySelector("#saveUserBtn").onclick = () => updateUser(user.user_id);
    (bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)).show();
  };

  document.addEventListener('DOMContentLoaded', async () => {
    await displayUserName();
    await loadUsers();
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
      searchInput.addEventListener("input", function() {
        const query = this.value.trim().toLowerCase();
        displayData = query ? allUsersData.filter(user =>
          (user.student_number && user.student_number.toLowerCase().includes(query)) ||
          (user.first_name && user.first_name.toLowerCase().includes(query)) ||
          (user.last_name && user.last_name.toLowerCase().includes(query)) ||
          (user.email && user.email.toLowerCase().includes(query)) ||
          (user.role && user.role.toLowerCase().includes(query)) ||
          (user.course && user.course.toLowerCase().includes(query)) ||
          (user.year_level && user.year_level.toString().toLowerCase().includes(query)) ||
          (user.block && user.block.toLowerCase().includes(query)) ||
          (user.admission_year && user.admission_year.toLowerCase().includes(query))
        ) : allUsersData;
        currentPage = 1;
        updatePaginationControls();
        displayUsersPage(currentPage);
      });
    }
  });
})();
