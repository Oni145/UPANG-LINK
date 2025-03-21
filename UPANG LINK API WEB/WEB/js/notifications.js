// Log API URL for debugging
console.log("Using API_BASE_URL in notifications.js:", window.API_BASE_URL);
// Select elements
const notificationButton = document.getElementById("notificationButton");
const notificationDialog = document.getElementById("notificationDialog");
const notificationsList = document.getElementById("notificationsList");
const notificationBadge = document.getElementById("notificationBadge");
const closeBtn = document.getElementById("closeDropdown");
const markAllReadBtn = document.getElementById("markAllRead"); // "Mark All" button

// Function to get Authorization headers with token
function getAuthHeaders(token) {
    return {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Authorization": `Bearer ${token}`,
    };
}

// Function to position notification dialog
function positionNotificationDialog() {
    const rect = notificationButton.getBoundingClientRect();
    notificationDialog.style.top = `${rect.bottom + 5}px`;
    notificationDialog.style.left = `${rect.left + rect.width / 2}px`;
    notificationDialog.style.transform = "translateX(-100%)";
    notificationDialog.style.display = "block";
}

// Fetch and display only unread notifications
async function fetchNotifications() {
    const token = localStorage.getItem("token");
    if (!token) {
        console.error("No authentication token found.");
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/notifications`, {
            method: "GET",
            headers: getAuthHeaders(token),
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();

        console.log("API Response:", data); // Debugging

        if (data.status === 200) {
            notificationsList.innerHTML = "";

            let unreadCount = 0;
            const unreadNotifications = data.notifications.filter((n) => n.is_read === 0);

            unreadNotifications.forEach((notification) => {
                getUserFullName(notification.user_id, token).then((userName) => {
                    const notificationItem = document.createElement("li");
                    notificationItem.classList.add("unread");
                    notificationItem.innerHTML = `
                        <span><strong>${userName}</strong> - ${notification.message}</span>`;
                    notificationsList.appendChild(notificationItem);
                });
            });

            unreadCount = unreadNotifications.length;
            notificationBadge.style.display = unreadCount > 0 ? "block" : "none";
            notificationBadge.textContent = unreadCount;

            markAllReadBtn.style.display = unreadCount > 0 ? "block" : "none";
        } else {
            console.error("Unexpected API response structure:", data);
        }
    } catch (error) {
        console.error("Error fetching notifications:", error);
    }
}

// Function to mark all notifications as read
async function markAllNotificationsAsRead() {
    const token = localStorage.getItem("token");
    if (!token) {
        console.error("No authentication token found.");
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/notifications/mark_all_read`, {
            method: "PUT",
            headers: getAuthHeaders(token),
            body: JSON.stringify({ action: "mark_all_read" }),
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();
        
        // Debug: Log the response data to verify what's returned
        console.log("Response Data:", data);

        // If the message contains the word 'notifications marked as read' treat it as success
        if (data.status === "success" || data.message.includes("notifications marked as read")) {
            alert("All notifications marked as read successfully!");
            fetchNotifications(); // Refresh notifications list if needed
        } else {
            alert(`Error: ${data.message || 'Failed to mark notifications as read.'}`);
        }
    } catch (error) {
        alert(`Error: ${error.message || 'Failed to mark all notifications as read.'}`);
    }
}


// Check for unread notifications every 5 seconds
setInterval(() => {
    const token = localStorage.getItem("token");
    if (!token) return;

    fetch(`${API_BASE_URL}/admin/notifications`, {
        method: "GET",
        headers: getAuthHeaders(token),
    })
        .then((response) => {
            if (!response.ok) {
                if (response.status === 401) {
                    // Token expired or invalid
                    console.log("Authentication token expired. Please log in again.");
                    return;
                }
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data && data.status === 200) {
                const unreadCount = data.notifications.filter((n) => n.is_read === 0).length;
                notificationBadge.style.display = unreadCount > 0 ? "block" : "none";
                notificationBadge.textContent = unreadCount;
            }
        })
        .catch((error) => {
            console.error("Error checking notifications:", error);
        });
}, 5000);

// Function to get user's full name from user_id
async function getUserFullName(userId, token) {
    const url = `${API_BASE_URL}/auth/users/${userId}`;

    try {
        const response = await fetch(url, {
            method: "GET",
            headers: getAuthHeaders(token),
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();

        if (data.status === "success" && data.data.first_name && data.data.last_name) {
            return `${data.data.first_name} ${data.data.last_name}`;
        } else {
            return "Unknown User";
        }
    } catch (error) {
        console.error("Error fetching user:", error);
        return "Unknown User";
    }
}

// Function to close the notification dialog when clicking outside
function handleClickOutside(event) {
    if (!notificationDialog.contains(event.target) && event.target !== notificationButton) {
        notificationDialog.style.display = "none";
    }
}

// Event Listeners
document.addEventListener("click", handleClickOutside);

notificationButton.addEventListener("click", (event) => {
    event.stopPropagation();
    positionNotificationDialog();
    fetchNotifications();
});

closeBtn.addEventListener("click", () => {
    notificationDialog.style.display = "none";
});

markAllReadBtn.addEventListener("click", markAllNotificationsAsRead);
