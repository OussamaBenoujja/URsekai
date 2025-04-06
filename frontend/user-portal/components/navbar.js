// Navbar dynamic logic - Updated for new style and API integration

// --- Configuration ---
const API_BASE_URL = 'http://localhost:8000/api/v1'; // Adjust if your backend runs elsewhere
const TOKEN_KEY = 'ursekai_auth_token'; // Use the same key as the rest of the app

// --- Helper Functions ---
async function apiFetch(endpoint, options = {}) {
  const token = localStorage.getItem(TOKEN_KEY);
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token && { 'Authorization': `Bearer ${token}` })
  };

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers: { ...headers, ...options.headers },
    });

    if (!response.ok) {
      console.error(`API Error: ${response.status} ${response.statusText} for ${endpoint}`);
      if (response.status === 401 && endpoint !== '/auth/me') {
        // Unauthorized, likely expired token
        handleLogout(); // Log out the user
      }
      try {
        const errorData = await response.json();
        return { error: true, status: response.status, data: errorData };
      } catch (e) {
        return { error: true, status: response.status, data: { message: response.statusText } };
      }
    }

    if (response.status === 204 || response.headers.get('content-length') === '0') {
      return { error: false, data: null }; // No content
    }

    return { error: false, data: await response.json() };
  } catch (error) {
    console.error('Network or fetch error:', error);
    return { error: true, status: 500, data: { message: 'Network error' } };
  }
}

function isLoggedIn() {
  return !!localStorage.getItem(TOKEN_KEY);
}

async function getUserProfile() {
  if (!isLoggedIn()) return null;

  const { error, data } = await apiFetch('/auth/me');
  if (error) {
    console.error('Failed to fetch user profile:', data);
    // If /auth/me fails (e.g., invalid token), log out
    if (data.status === 401) handleLogout();
    return null; // Return null on error
  }
  // Use display_name or username for initials, and avatar_url for avatar
  const displayName = data.display_name || data.username || 'U';
  let avatarUrl = data.avatar_url;
  if (avatarUrl) {
    // If avatarUrl is a relative path (starts with /storage), prepend backend domain
    if (avatarUrl.startsWith('/storage')) {
      avatarUrl = `http://localhost:8000${avatarUrl}`;
    }
  } else {
    avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=6c757d&color=fff&size=38`;
  }
  return { ...data, avatar: avatarUrl, name: displayName };
}

async function handleLogout() {
  const { error } = await apiFetch('/auth/logout', { method: 'POST' });
  if (error) {
    console.error('Logout API call failed, logging out locally anyway.');
  }
  localStorage.removeItem(TOKEN_KEY);
  // Redirect to home or login page after logout
  window.location.href = '/';
}

async function validateTokenAndLog() {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) {
    console.warn('[navbar.js] No token found in localStorage. User is not logged in.');
    return false;
  }
  try {
    const { error, data } = await apiFetch('/auth/me');
    if (error) {
      console.warn('[navbar.js] Token validation failed:', data);
      return false;
    } else {
      console.log('[navbar.js] Token is valid. User info:', data);
      return true;
    }
  } catch (e) {
    console.error('[navbar.js] Error during token validation:', e);
    return false;
  }
}

// --- Notification Functions ---
async function fetchNotifications() {
  const { error, data } = await apiFetch('/notifications?per_page=10');
  if (error) {
    console.error('Failed to fetch notifications:', data);
    return [];
  }
  // Map backend fields to frontend format
  return (data.data || data).map(n => ({
    id: n.notification_id,
    message: n.title ? `${n.title}: ${n.message}` : n.message,
    isRead: n.is_read,
    createdAt: n.created_at,
    type: n.type,
    link: n.link
  }));
}

async function markNotificationAsRead(id) {
  await apiFetch(`/notifications/${id}/mark-read`, { method: 'POST' });
  // Optionally re-fetch notifications or update UI
}

async function markAllNotificationsAsRead() {
  await apiFetch('/notifications/mark-all-read', { method: 'POST' });
  // Optionally re-fetch notifications or update UI
}

function formatTimeAgo(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  
  if (diffInSeconds < 60) {
    return 'Just now';
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours} hour${hours > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} day${days > 1 ? 's' : ''} ago`;
  } else {
    // Format as MM/DD/YYYY for older notifications
    return date.toLocaleDateString();
  }
}

async function renderNotifications() {
  const bellElement = document.querySelector('.navbar-notifications');
  const dropdownElement = document.getElementById('notification-dropdown');
  const badgeElement = document.getElementById('notification-badge');
  const notificationList = document.getElementById('notification-list');
  const markAllReadBtn = document.getElementById('mark-all-read');
  
  if (!bellElement || !dropdownElement || !badgeElement || !notificationList) {
    console.warn('[navbar.js] Notification elements not found');
    return;
  }
  
  // Get notifications
  const notifications = await fetchNotifications();
  
  // Update badge count (only count unread)
  const unreadCount = notifications.filter(n => !n.isRead).length;
  badgeElement.textContent = unreadCount;
  
  // If no unread notifications, hide the badge
  if (unreadCount === 0) {
    badgeElement.style.display = 'none';
  } else {
    badgeElement.style.display = 'flex';
  }
  
  // Clear and populate notification list
  notificationList.innerHTML = '';
  
  if (notifications.length === 0) {
    notificationList.innerHTML = '<li class="notification-item"><div class="notification-content"><p>No notifications at this time</p></div></li>';
  } else {
    notifications.forEach(notification => {
      const li = document.createElement('li');
      li.className = `notification-item${notification.isRead ? '' : ' unread'}`;
      li.dataset.id = notification.id;
      
      li.innerHTML = `
        <div class="notification-content">
          <p>${notification.message}</p>
          <span class="notification-time">${formatTimeAgo(notification.createdAt)}</span>
        </div>
      `;
      
      // Add click handler to mark as read
      li.addEventListener('click', () => {
        markNotificationAsRead(notification.id);
        li.classList.remove('unread');
      });
      
      notificationList.appendChild(li);
    });
  }
  
  // Toggle dropdown when bell is clicked
  bellElement.addEventListener('click', (e) => {
    e.stopPropagation(); // Prevent propagation to document
    dropdownElement.classList.toggle('active');
    
    // Hide profile dropdown if it's open
    const profileDropdown = document.getElementById('navbar-profile-dropdown');
    if (profileDropdown && profileDropdown.classList.contains('active')) {
      profileDropdown.classList.remove('active');
    }
  });
  
  // Hide dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!dropdownElement.contains(e.target) && !bellElement.contains(e.target)) {
      dropdownElement.classList.remove('active');
    }
  });
  
  // Mark all as read button
  if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      markAllNotificationsAsRead();
      
      // Update UI
      document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.classList.remove('unread');
      });
      
      badgeElement.textContent = '0';
      badgeElement.style.display = 'none';
    });
  }
}

// --- Rendering Functions ---
function renderNavLinks() {
  const linksContainer = document.getElementById('navbar-links');
  if (!linksContainer) return;
  linksContainer.innerHTML = ''; // Clear existing links

  if (isLoggedIn()) {
    const links = [
      { href: '/catalog', text: 'Games' },
      { href: '/forum-discussions', text: 'Community' },
      { href: '/leaderboard', text: 'Leaderboard' },
      // Assuming 'Achievements' are part of the profile page for now
      { href: '/profile', text: 'Achievements' }
    ];

    links.forEach(link => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = link.href;
      a.textContent = link.text;
      // Add active class if the current path matches
      if (window.location.pathname === link.href) {
        a.classList.add('active');
      }
      li.appendChild(a);
      linksContainer.appendChild(li);
    });
  }
}

async function renderNavbarActions() {
  const actions = document.getElementById('navbar-actions');
  if (!actions) return;
  actions.innerHTML = '';

  if (isLoggedIn()) {
    const user = await getUserProfile();
    if (user) {
      const profileDiv = document.createElement('div');
      profileDiv.className = 'navbar-profile';
      profileDiv.innerHTML = `<img src="${user.avatar}" alt="${user.name}'s Profile" class="navbar-profile-img" id="navbar-profile-img" />`;

      // Create the dropdown as a child of .navbar-profile
      const dropdown = document.createElement('div');
      dropdown.className = 'navbar-profile-dropdown';
      dropdown.id = 'navbar-profile-dropdown';
      dropdown.innerHTML = `
        <ul>
          <li><a href="/profile/${user.username}">Profile</a></li>
          <li><a href="/library">Library</a></li>
          <li><a href="/settings">Settings</a></li>
          <li><button id="navbar-logout-btn">Logout</button></li>
        </ul>
      `;
      profileDiv.appendChild(dropdown);
      actions.appendChild(profileDiv);

      // Show dropdown on hover
      profileDiv.addEventListener('mouseenter', () => {
        dropdown.classList.add('active');
      });
      profileDiv.addEventListener('mouseleave', (e) => {
        setTimeout(() => {
          if (!dropdown.matches(':hover') && !profileDiv.matches(':hover')) {
            dropdown.classList.remove('active');
          }
        }, 100);
      });
      dropdown.addEventListener('mouseleave', () => {
        dropdown.classList.remove('active');
      });
      dropdown.addEventListener('mouseenter', () => {
        dropdown.classList.add('active');
      });

      // Logout button inside dropdown
      const logoutBtn = dropdown.querySelector('#navbar-logout-btn');
      if (logoutBtn) {
        logoutBtn.onclick = handleLogout;
      }

      // Hide dropdown on click outside
      document.addEventListener('click', (e) => {
        if (!profileDiv.contains(e.target)) {
          dropdown.classList.remove('active');
        }
      });
    } else {
      // Failed to get user, show login/signup
      renderLoggedOutActions(actions);
    }
  } else {
    renderLoggedOutActions(actions);
  }
}

function renderLoggedOutActions(actionsContainer) {
  const loginBtn = document.createElement('button');
  loginBtn.className = 'navbar-btn';
  loginBtn.textContent = 'Login';
  loginBtn.onclick = () => window.location.href = '/auth'; // Use Express route

  const signupBtn = document.createElement('button');
  signupBtn.className = 'navbar-btn';
  signupBtn.textContent = 'Sign Up';
  signupBtn.onclick = () => window.location.href = '/auth'; // Use Express route

  actionsContainer.appendChild(loginBtn);
  actionsContainer.appendChild(signupBtn);
}

function setupNavbarSearch() {
  const input = document.getElementById('navbar-search-input');
  const iconWrapper = document.getElementById('navbar-search-icon-wrapper');
  const btn = document.getElementById('navbar-search-btn'); // Still useful for Enter key

  const performSearch = (e) => {
    e.preventDefault();
    const searchTerm = input.value.trim();
    if (searchTerm) {
      // Redirect to the new search results page
      window.location.href = `/search?query=${encodeURIComponent(searchTerm)}`;
    }
  };

  if (iconWrapper) {
    iconWrapper.onclick = performSearch;
  }
  if (input) {
    input.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') performSearch(e);
    });
  }
}

// --- Initialization ---
async function initNavbar() {
  console.log('[navbar.js] initNavbar called');
  await validateTokenAndLog();
  if (!document.getElementById('navbar-actions') || !document.getElementById('navbar-links')) {
      console.warn('[navbar.js] Navbar elements not found, delaying init slightly.');
      await new Promise(resolve => setTimeout(resolve, 100));
      if (!document.getElementById('navbar-actions') || !document.getElementById('navbar-links')) {
          console.error('[navbar.js] Navbar elements still not found. Cannot initialize navbar JS.');
          return;
      }
  }
  console.log('[navbar.js] Navbar elements found, rendering nav links and actions...');
  renderNavLinks();
  await renderNavbarActions();
  setupNavbarSearch();
  
  // Initialize notifications
  if (isLoggedIn()) {
    await renderNotifications();
  }
  
  console.log('[navbar.js] Navbar initialization complete.');
}

// Run initialization logic
// Use DOMContentLoaded if navbar HTML is static
// If navbar HTML is fetched, ensure this runs *after* fetch completes
// The fetch script in home.html needs to call initNavbar() in its .then() block
// document.addEventListener('DOMContentLoaded', initNavbar);

// Export initNavbar so it can be called after fetch
export { initNavbar };
