document.addEventListener('DOMContentLoaded', () => {
    // Check authentication
    if (!auth.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Initialize
    requestManager.fetchRequests();

    // Event Listeners
    document.getElementById('statusFilter').addEventListener('change', (e) => {
        requestManager.fetchRequests(e.target.value);
    });

    document.getElementById('logoutBtn').addEventListener('click', () => {
        auth.logout();
    });

    document.querySelector('.close').addEventListener('click', () => {
        document.getElementById('requestModal').style.display = 'none';
    });

    document.getElementById('addNoteBtn').addEventListener('click', async () => {
        const noteText = document.getElementById('newNote').value;
        if (noteText.trim() && requestManager.currentRequest) {
            await requestManager.addNote(requestManager.currentRequest.request_id, noteText);
            document.getElementById('newNote').value = '';
        }
    });

    // Status update buttons
    document.querySelector('.approve-btn').addEventListener('click', async () => {
        if (requestManager.currentRequest) {
            await requestManager.updateRequestStatus(requestManager.currentRequest.request_id, 'approved');
            document.getElementById('requestModal').style.display = 'none';
        }
    });

    document.querySelector('.reject-btn').addEventListener('click', async () => {
        if (requestManager.currentRequest) {
            await requestManager.updateRequestStatus(requestManager.currentRequest.request_id, 'rejected');
            document.getElementById('requestModal').style.display = 'none';
        }
    });

    // Navigation
    document.querySelectorAll('.nav-links li').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.nav-links li').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            // Handle page navigation here
        });
    });
});

// Close modal when clicking outside
window.onclick = (event) => {
    const modal = document.getElementById('requestModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}; 