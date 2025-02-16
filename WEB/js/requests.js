class RequestManager {
    constructor() {
        this.requestsTableBody = document.getElementById('requestsTableBody');
        this.requests = [];
        this.currentRequest = null;
    }

    async fetchRequests() {
        try {
            const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.requests}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Platform': 'web'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.status === 'success') {
                this.displayRequests(data.data);
            } else {
                console.error('Error fetching requests:', data.message);
            }
        } catch (error) {
            console.error('Error fetching requests:', error);
            // Show user-friendly error message
            this.requestsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        Failed to load requests. Please try again later.
                    </td>
                </tr>
            `;
        }
    }

    displayRequests(requests) {
        if (!requests || requests.length === 0) {
            this.requestsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">No requests found</td>
                </tr>
            `;
            return;
        }

        this.requestsTableBody.innerHTML = requests.map(request => `
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

    async updateRequestStatus(requestId, status) {
        try {
            const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.requests}/${requestId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${auth.getToken()}`
                },
                body: JSON.stringify({ status })
            });

            const data = await response.json();
            if (data.status === 'success') {
                await this.fetchRequests();
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error updating request:', error);
            return false;
        }
    }

    async addNote(requestId, note) {
        try {
            const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.requests}/${requestId}/notes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${auth.getToken()}`
                },
                body: JSON.stringify({ note })
            });

            const data = await response.json();
            if (data.status === 'success') {
                this.fetchRequestDetails(requestId);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error adding note:', error);
            return false;
        }
    }

    renderRequestsTable() {
        const tbody = document.getElementById('requestsTableBody');
        tbody.innerHTML = '';

        this.requests.forEach(request => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${request.request_id}</td>
                <td>${request.student_name}</td>
                <td>${request.request_type}</td>
                <td>${new Date(request.submitted_at).toLocaleDateString()}</td>
                <td><span class="status-badge status-${request.status.toLowerCase()}">${request.status}</span></td>
                <td>
                    <button onclick="requestManager.showRequestDetails('${request.request_id}')" class="view-btn">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async showRequestDetails(requestId) {
        try {
            const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.requests}/${requestId}`, {
                headers: {
                    'Authorization': `Bearer ${auth.getToken()}`
                }
            });

            const data = await response.json();
            if (data.status === 'success') {
                this.currentRequest = data.data;
                this.renderRequestDetails();
                document.getElementById('requestModal').style.display = 'block';
            }
        } catch (error) {
            console.error('Error fetching request details:', error);
        }
    }

    renderRequestDetails() {
        const request = this.currentRequest;
        
        // Update modal fields
        document.getElementById('studentName').textContent = request.student_name;
        document.getElementById('studentNumber').textContent = request.student_number;
        document.getElementById('studentCourse').textContent = request.course;
        document.getElementById('requestType').textContent = request.request_type;
        document.getElementById('requestStatus').textContent = request.status;
        document.getElementById('requestDate').textContent = new Date(request.submitted_at).toLocaleString();

        // Render requirements
        const requirementsList = document.getElementById('requirementsList');
        requirementsList.innerHTML = '';
        request.requirements.forEach(req => {
            const div = document.createElement('div');
            div.className = 'requirement-item';
            div.innerHTML = `
                <span class="requirement-name">${req.name}</span>
                <span class="requirement-status ${req.status}">${req.status}</span>
            `;
            requirementsList.appendChild(div);
        });

        // Render notes
        const notesList = document.getElementById('notesList');
        notesList.innerHTML = '';
        request.notes.forEach(note => {
            const div = document.createElement('div');
            div.className = 'note-item';
            div.innerHTML = `
                <p class="note-text">${note.note}</p>
                <small class="note-meta">By ${note.admin_name} on ${new Date(note.created_at).toLocaleString()}</small>
            `;
            notesList.appendChild(div);
        });
    }
}

const requestManager = new RequestManager();
// Load requests when the page loads
document.addEventListener('DOMContentLoaded', () => {
    requestManager.fetchRequests();
}); 