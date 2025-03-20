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

// Function to mark a single notification as read
async function markNotificationAsRead(notificationId) {
    const token = localStorage.getItem("token");
    if (!token) {
        console.error("No authentication token found.");
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/notification/${notificationId}`, {
            method: "PATCH",
            headers: getAuthHeaders(token),
            body: JSON.stringify({ is_read: 1 }), // Ensure correct request body
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();
        console.log("API Response:", data);

        if (data.status === "success") {
            fetchNotifications(); // Refresh notifications
        } else {
            console.error("Error marking notification as read:", data.message);
        }
    } catch (error) {
        console.error("Error marking notification as read:", error);
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
        const response = await fetch(`${API_BASE_URL}/admin/notifications/mark_all_as_read`, {
            method: "POST",
            headers: getAuthHeaders(token),
            body: JSON.stringify({ is_read: 1 }), // Include necessary data
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();
        if (data.status === "success") {
            alert("All notifications marked as read successfully");
            fetchNotifications();
        } else {
            alert("Failed to mark all notifications as read");
        }
    } catch (error) {
        alert("Error marking all notifications as read: " + error.message);
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

notificationsList.addEventListener("click", (event) => {
    if (event.target.classList.contains("mark-as-read")) {
        const notificationId = event.target.getAttribute("data-id");
        markNotificationAsRead(notificationId);
    }
});

markAllReadBtn.addEventListener("click", markAllNotificationsAsRead);
