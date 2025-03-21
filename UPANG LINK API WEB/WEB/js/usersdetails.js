// Function to open the modal with user data
function openUserModal(userId) {
    const user = allUsersData.find(u => u.user_id == userId); // Find user
    if (!user) return;

    // Populate modal with user data
    document.getElementById('modalUserId').textContent = user.user_id || 'N/A';
    document.getElementById('modalUserName').textContent = `${user.first_name || ''} ${user.last_name || ''}`;
    document.getElementById('modalUserEmail').textContent = user.email || 'N/A';
    document.getElementById('modalUserStudentNumber').textContent = user.student_number || 'N/A';
    document.getElementById('modalUserBirthdate').textContent = user.birthdate || 'N/A';
    document.getElementById('modalUserEmergencyContact').textContent = user.emergency_contact || 'N/A';
    document.getElementById('modalUserCourse').textContent = user.course || 'N/A';
    document.getElementById('modalUserCurrentYear').textContent = user.current_year || 'N/A';

    // Show the modal
    document.getElementById('user_modal').style.display = "flex";
}

// Function to close the modal
function closeUserModal() {
    document.getElementById('user_modal').style.display = "none";
}

// Attach click event using delegation (for dynamically added buttons)
document.addEventListener('click', function(event) {
    if (event.target.closest('.view-btn')) { 
        const userId = event.target.closest('.view-btn').getAttribute('data-user-id');
        openUserModal(userId);
    }
});

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    const modal = document.getElementById('user_modal');
    if (event.target === modal) {
        closeUserModal();
    }
};
