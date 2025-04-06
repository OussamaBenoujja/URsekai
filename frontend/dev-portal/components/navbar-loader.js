/**
 * URsekai Navbar Loader
 * This script loads the navbar component and initializes its functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    // Load navbar component
    fetch("/dev-portal/components/navbar.html")
        .then(response => response.text())
        .then(html => {
            // Create a temporary container
            const tempContainer = document.createElement("div");
            tempContainer.innerHTML = html;
            
            // Insert navbar at the beginning of the body, using the first element child
            const body = document.body;
            const navbarElement = tempContainer.firstElementChild;
            if (navbarElement) {
                body.insertBefore(navbarElement, body.firstChild);
                // Initialize navbar functionality AFTER inserting the element
                initNavbar(navbarElement); // Pass the element to initNavbar
                // Apply header nav layout fixes as on index.html
                fixSidebarNavigation();
                // Add notifications AFTER inserting the element
                addNotifications(navbarElement); // Call addNotifications here
            }
        })
        .catch(error => {
            console.error("Error loading navbar:", error);
        });
});

// Initialize navbar functionality
// Takes the navbar element as an argument
function initNavbar(navbarElement) {
    // Update username and avatar from local storage or API
    const usernameElement = navbarElement.querySelector("#username");
    const avatarImg = navbarElement.querySelector("#user-avatar-img");
    const token = localStorage.getItem("ursekai_auth_token");

    if (usernameElement) {
        const storedUsername = localStorage.getItem("ursekai_username");
        if (storedUsername) {
            usernameElement.textContent = storedUsername;
        }
    }
    // Avatar logic
    if (avatarImg) {
        const storedAvatar = localStorage.getItem("ursekai_avatar_url");
        if (storedAvatar && storedAvatar !== 'null') {
            avatarImg.src = storedAvatar;
        } else if (token) {
            fetch("http://localhost:8000/api/v1/auth/me", {
                headers: { Authorization: `Bearer ${token}` }
            })
            .then(res => res.json())
            .then(userData => {
                let avatarUrl = userData.avatar_url;
                if (avatarUrl && avatarUrl !== 'null') {
                    if (!avatarUrl.startsWith('http')) {
                        avatarUrl = 'http://localhost:8000' + avatarUrl;
                    }
                    avatarImg.src = avatarUrl;
                    localStorage.setItem("ursekai_avatar_url", avatarUrl);
                } else {
                    avatarImg.src = "https://via.placeholder.com/40x40?text=UR";
                }
            })
            .catch(() => {
                avatarImg.src = "https://via.placeholder.com/40x40?text=UR";
            });
        } else {
            avatarImg.src = "https://via.placeholder.com/40x40?text=UR";
        }
    }
    
    // Theme toggle functionality
    const themeToggle = navbarElement.querySelector("#theme-toggle");
    if (themeToggle) {
        themeToggle.addEventListener("click", function() {
            const currentTheme = document.documentElement.getAttribute("data-theme");
            const newTheme = currentTheme === "dark" ? "light" : "dark";
            
            document.documentElement.setAttribute("data-theme", newTheme);
            localStorage.setItem("ursekai_theme", newTheme);
            
            // Update icon
            const iconElement = themeToggle.querySelector("i");
            if (iconElement) {
                if (newTheme === "dark") {
                    iconElement.classList.remove("fa-moon");
                    iconElement.classList.add("fa-sun");
                } else {
                    iconElement.classList.remove("fa-sun");
                    iconElement.classList.add("fa-moon");
                }
            }
        });
        
        // Initialize theme icon based on saved theme
        const savedTheme = localStorage.getItem("ursekai_theme") || "light";
        document.documentElement.setAttribute("data-theme", savedTheme);
        const iconElement = themeToggle.querySelector("i");
        if (iconElement) {
            if (savedTheme === "dark") {
                iconElement.classList.remove("fa-moon");
                iconElement.classList.add("fa-sun");
            } else {
                iconElement.classList.remove("fa-sun");
                iconElement.classList.add("fa-moon");
            }
        }
    }
    
    // Logout functionality
    const logoutLink = navbarElement.querySelector("#logout-link");
    if (logoutLink) {
        logoutLink.addEventListener("click", function(e) {
            e.preventDefault();
            
            const token = localStorage.getItem("ursekai_auth_token");
            if (token) {
                fetch("http://localhost:8000/api/auth/logout", {
                    method: "POST",
                    headers: { Authorization: `Bearer ${token}` }
                }).catch(error => console.error("Logout error:", error));
            }
            
            // Clear local storage and redirect
            localStorage.removeItem("ursekai_auth_token");
            localStorage.removeItem("ursekai_username");
            window.location.href = "/dev-portal/pages/login.html";
        });
    }
}

// Ensure header nav ul displays horizontally, matching index.html
function fixSidebarNavigation() {
    const style = document.createElement('style');
    style.textContent = `
        /* Fix for header navigation layout */
        header nav ul {
            display: flex !important;
            flex-direction: row !important;
        }
    `;
    document.head.appendChild(style);
}

// Add notifications functionality (Moved from index.html)
// Takes the navbar element as an argument to ensure selectors work correctly
async function addNotifications(navbarElement) { // Make function async
    // Find the correct container for user controls
    const controlsContainer = navbarElement.querySelector(".header-controls");
    const userProfileElement = navbarElement.querySelector(".user-profile"); // Find the element to insert before
    const token = localStorage.getItem("ursekai_auth_token"); // Get token

    if (!controlsContainer) {
        console.error("Could not find '.header-controls' container within the navbar for notifications.");
        return; // Exit if the main controls container isn't found
    }
    if (!userProfileElement) {
        console.warn("Could not find '.user-profile' element. Appending notifications to the end of controls.");
        // Fallback: append to the end if user profile isn't found for some reason
    }
    if (!token) {
        console.warn("No auth token found, cannot fetch notifications.");
        // Optionally hide or disable the notification button if not logged in
        return;
    }

    // Create notifications element
    const notificationsContainer = document.createElement("div");
    notificationsContainer.className = "notifications-container";

    const notificationsButton = document.createElement("button");
    notificationsButton.className = "notifications-button";
    // Add FontAwesome bell icon and badge span (initially empty)
    notificationsButton.innerHTML = '<i class="fas fa-bell"></i><span class="badge" style="display: none;"></span>';

    const notificationsDropdown = document.createElement("div");
    notificationsDropdown.className = "notifications-dropdown";
    // Initial dropdown structure
    notificationsDropdown.innerHTML = `
        <div class="dropdown-header">Notifications</div>
        <ul class="notifications-list">
            <li>Loading...</li>
        </ul>
        <div class="dropdown-footer">
            <a href="#" id="mark-all-read-link">Mark All as Read</a> |
            <a href="/developer/notifications">View All</a>
        </div>
    `;
    // Initially hide dropdown
    notificationsDropdown.style.display = 'none';

    notificationsContainer.appendChild(notificationsButton);
    notificationsContainer.appendChild(notificationsDropdown);

    const badgeElement = notificationsButton.querySelector('.badge');
    const notificationsListElement = notificationsDropdown.querySelector('.notifications-list');

    // Function to fetch and update notifications
    const fetchNotifications = async () => {
        try {
            // Fetch unread count
            // CORRECTED URL: Added /api prefix back
            const countResponse = await fetch("http://localhost:8000/api/developer/notifications/unread-count", {
                headers: { Authorization: `Bearer ${token}`, 'Accept': 'application/json' }
            });
            if (!countResponse.ok) throw new Error(`HTTP error! status: ${countResponse.status}`);
            const countData = await countResponse.json();

            if (countData.success && countData.data.count > 0) {
                badgeElement.textContent = countData.data.count;
                badgeElement.style.display = 'inline-block';
            } else {
                badgeElement.style.display = 'none';
            }

            // Fetch recent notifications (e.g., latest 5 unread or just latest 5)
            // CORRECTED URL: Added /api prefix back
            const notificationsResponse = await fetch("http://localhost:8000/api/developer/notifications?per_page=5", { // Fetch latest 5
                headers: { Authorization: `Bearer ${token}`, 'Accept': 'application/json' }
            });
            if (!notificationsResponse.ok) throw new Error(`HTTP error! status: ${notificationsResponse.status}`);
            const notificationsData = await notificationsResponse.json();

            notificationsListElement.innerHTML = ''; // Clear loading/previous items

            if (notificationsData.success && notificationsData.data.data.length > 0) {
                notificationsData.data.data.forEach(notification => {
                    const li = document.createElement('li');
                    li.dataset.notificationId = notification.notification_id; // Store ID for potential 'mark as read'
                    li.style.fontWeight = notification.is_read ? 'normal' : 'bold'; // Style unread
                    li.innerHTML = `
                        <strong>${notification.title || 'Notification'}</strong><br>
                        ${notification.message}
                        <small style="display: block; color: grey; margin-top: 3px;">${new Date(notification.created_at).toLocaleString()}</small>
                    `;
                    // Optional: Add click listener to mark single notification as read
                    li.addEventListener('click', async () => {
                        if (!notification.is_read) {
                            // Pass the corrected base URL structure to the helper function
                            markNotificationAsRead(notification.notification_id, token, fetchNotifications);
                        }
                        // Optional: Redirect to notification link if available
                        if (notification.link) {
                           // window.location.href = notification.link; // Consider opening in new tab or handling differently
                        }
                    });
                    notificationsListElement.appendChild(li);
                });
            } else {
                notificationsListElement.innerHTML = '<li>No new notifications</li>';
            }

        } catch (error) {
            console.error("Error fetching notifications:", error);
            notificationsListElement.innerHTML = '<li>Error loading notifications</li>';
            badgeElement.style.display = 'none';
        }
    };

    // Toggle dropdown on button click
    notificationsButton.addEventListener('click', (event) => {
        event.stopPropagation(); // Prevent click from closing dropdown immediately
        const isHidden = notificationsDropdown.style.display === 'none';
        notificationsDropdown.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            fetchNotifications(); // Refresh notifications when opening
        }
    });

    // Close dropdown if clicking outside
    document.addEventListener('click', (event) => {
        if (!notificationsContainer.contains(event.target)) {
            notificationsDropdown.style.display = 'none';
        }
    });

    // Mark all as read functionality
    const markAllReadLink = notificationsDropdown.querySelector('#mark-all-read-link');
    markAllReadLink.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation(); // Prevent dropdown from closing if link is inside
        try {
            // CORRECTED URL: Added /api prefix back
            const response = await fetch("http://localhost:8000/api/developer/notifications/read-all", {
                method: 'PUT',
                headers: { Authorization: `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' }
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            if (result.success) {
                console.log("Marked all notifications as read");
                fetchNotifications(); // Refresh the list and badge
                notificationsDropdown.style.display = 'none'; // Close dropdown
            } else {
                console.error("Failed to mark all notifications as read:", result.message);
            }
        } catch (error) {
            console.error("Error marking all notifications as read:", error);
        }
    });

    // Insert the notifications container before the user profile element within the controls container
    if (userProfileElement) {
        controlsContainer.insertBefore(notificationsContainer, userProfileElement);
    } else {
        controlsContainer.appendChild(notificationsContainer); // Fallback append
    }
    console.log("Notifications added to navbar header controls.");

    // Initial fetch of notifications count for the badge
    fetchNotifications(); // Fetch count and potentially list on load

    // Add some basic CSS for the notifications (consider moving to a CSS file)
    const style = document.createElement("style");
    style.textContent = `
        /* Make sure header-controls uses flex */
        .header-controls {
            display: flex;
            align-items: center;
        }
        .notifications-container {
            position: relative;
            display: inline-block;
            /* Adjust margin to space it from the user profile */
            margin-right: 15px;
            margin-left: 15px; /* Add left margin too if needed */
        }
        .notifications-button {
            background: none;
            border: none;
            color: inherit; /* Inherit color from navbar */
            font-size: 1.2em; /* Adjust size */
            cursor: pointer;
            position: relative;
            padding: 5px;
            display: flex; /* Align badge correctly */
            align-items: center;
        }
        .notifications-button .badge {
            position: absolute;
            top: -5px;  /* Adjust position */
            right: -8px; /* Adjust position */
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 5px;
            font-size: 0.7em;
            line-height: 1;
        }
        .notifications-dropdown {
            position: absolute;
            right: 0;
            top: 100%; /* Position below the button */
            margin-top: 5px; /* Add some space */
            background-color: var(--background-color, white); /* Use theme variable */
            border: 1px solid var(--border-color, #ccc);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 4px;
            width: 250px; /* Adjust width */
            z-index: 1000;
            color: var(--text-color, black); /* Use theme variable */
        }
        .notifications-dropdown .dropdown-header {
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid var(--border-color, #ccc);
        }
        .notifications-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 300px; /* Limit height and make scrollable */
            overflow-y: auto;
        }
        .notifications-list li {
            padding: 10px;
            border-bottom: 1px solid var(--border-color-light, #eee);
            font-size: 0.9em;
            cursor: pointer; /* Indicate clickable */
        }
        .notifications-list li:hover {
            background-color: var(--background-color-hover, #f0f0f0); /* Hover effect */
        }
        .notifications-dropdown li:last-child {
            border-bottom: none;
        }
        .notifications-dropdown .dropdown-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid var(--border-color, #ccc);
            font-size: 0.9em;
        }
        .notifications-dropdown a {
            color: var(--primary-color, blue);
            text-decoration: none;
        }
    `;
    document.head.appendChild(style);
}

// Helper function to mark a single notification as read
async function markNotificationAsRead(notificationId, token, callback) {
    try {
        // CORRECTED URL: Added /api prefix back
        const response = await fetch(`http://localhost:8000/api/developer/notifications/${notificationId}/read`, {
            method: 'PUT',
            headers: { Authorization: `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' }
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        if (result.success) {
            console.log(`Marked notification ${notificationId} as read`);
            if (callback) callback(); // Refresh the list/badge via the passed function
        } else {
            console.error(`Failed to mark notification ${notificationId} as read:`, result.message);
        }
    } catch (error) {
        console.error(`Error marking notification ${notificationId} as read:`, error);
    }
}