/**
 * URSEKAI Navbar Loader & Mobile Responsive Functions
 * Handles navbar initialization, mobile menu functionality and responsive features
 */

document.addEventListener('DOMContentLoaded', function() {
  // Load navbar if there's a mount point
  const navbarMount = document.getElementById('navbar-mount');
  if (navbarMount) {
    loadNavbar();
  }

  // Function to load navbar from HTML template
  function loadNavbar() {
    fetch('/components/navbar.html')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(html => {
        // Insert the navbar HTML
        document.getElementById('navbar-mount').innerHTML = html;
        
        // After navbar is inserted, initialize all navbar functionality
        initializeNavbar();
      })
      .catch(error => {
        console.error('Error loading navbar:', error);
      });
  }

  // Initialize navbar functionality after HTML is loaded
  function initializeNavbar() {
    setupMobileMenu();
    setupDropdowns();
    setupSearchFunctionality();
    addMenuOverlay();
  }

  // Setup mobile menu toggle functionality
  function setupMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const navbarLinks = document.getElementById('navbar-links');
    
    if (mobileMenuToggle && navbarLinks) {
      mobileMenuToggle.addEventListener('click', function() {
        mobileMenuToggle.classList.toggle('active');
        navbarLinks.classList.toggle('active');
        
        // Toggle menu overlay
        const overlay = document.querySelector('.menu-overlay');
        if (overlay) {
          overlay.classList.toggle('active');
        }
      });
    }
  }

  // Add menu overlay for mobile
  function addMenuOverlay() {
    // Create overlay div if it doesn't exist
    if (!document.querySelector('.menu-overlay')) {
      const overlay = document.createElement('div');
      overlay.className = 'menu-overlay';
      document.getElementById('navbar-mount').appendChild(overlay);
      
      // Close menu when clicking overlay
      overlay.addEventListener('click', function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const navbarLinks = document.getElementById('navbar-links');
        
        if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
        if (navbarLinks) navbarLinks.classList.remove('active');
        overlay.classList.remove('active');
      });
    }
  }

  // Setup profile and notification dropdowns
  function setupDropdowns() {
    // Profile dropdown toggle
    const profileElement = document.querySelector('.navbar-profile');
    const profileDropdown = document.getElementById('navbar-profile-dropdown');
    
    if (profileElement && profileDropdown) {
      profileElement.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('active');
        
        // Close notification dropdown if open
        const notificationDropdown = document.getElementById('notification-dropdown');
        if (notificationDropdown && notificationDropdown.classList.contains('active')) {
          notificationDropdown.classList.remove('active');
        }
      });
    }
    
    // Notification dropdown toggle
    const notificationBell = document.querySelector('.notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    if (notificationBell && notificationDropdown) {
      notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('active');
        
        // Close profile dropdown if open
        if (profileDropdown && profileDropdown.classList.contains('active')) {
          profileDropdown.classList.remove('active');
        }
      });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
      if (profileDropdown && profileDropdown.classList.contains('active')) {
        profileDropdown.classList.remove('active');
      }
      
      if (notificationDropdown && notificationDropdown.classList.contains('active')) {
        notificationDropdown.classList.remove('active');
      }
    });
    
    // Prevent dropdown closing when clicking inside dropdown
    if (profileDropdown) {
      profileDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }
    
    if (notificationDropdown) {
      notificationDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }
  }

  // Setup search functionality for mobile
  function setupSearchFunctionality() {
    const navbarRight = document.querySelector('.navbar-right');
    const searchContainer = document.querySelector('.navbar-search');
    
    // Only for mobile - add search toggle button at smaller widths
    if (window.innerWidth <= 576 && navbarRight && searchContainer) {
      // Create mobile search toggle button if it doesn't exist
      if (!document.querySelector('.mobile-search-toggle')) {
        const searchToggle = document.createElement('button');
        searchToggle.className = 'mobile-search-toggle';
        searchToggle.setAttribute('aria-label', 'Toggle search');
        searchToggle.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        `;
        
        // Insert before notifications
        const notificationsElement = document.querySelector('.navbar-notifications');
        if (notificationsElement) {
          navbarRight.insertBefore(searchToggle, notificationsElement);
        } else {
          navbarRight.prepend(searchToggle);
        }
        
        // Toggle expanded search on mobile
        searchToggle.addEventListener('click', function() {
          searchContainer.classList.toggle('mobile-expanded');
          if (searchContainer.classList.contains('mobile-expanded')) {
            document.getElementById('navbar-search-input').focus();
          }
        });
      }
    }
    
    // Handle search input events for all screen sizes
    const searchInput = document.getElementById('navbar-search-input');
    const searchButton = document.getElementById('navbar-search-btn');
    const searchIconWrapper = document.getElementById('navbar-search-icon-wrapper');
    
    if (searchInput && searchButton) {
      // Submit search on Enter key
      searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          searchButton.click();
        }
      });
      
      // Also trigger search on icon click
      if (searchIconWrapper) {
        searchIconWrapper.addEventListener('click', function() {
          if (searchInput.value.trim() !== '') {
            searchButton.click();
          } else {
            searchInput.focus();
          }
        });
      }
    }
  }

  // Update responsive elements on window resize
  window.addEventListener('resize', function() {
    // Handle navbar changes on resize
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      // Close mobile menu if open and screen size increases
      if (window.innerWidth > 768) {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const navbarLinks = document.getElementById('navbar-links');
        const overlay = document.querySelector('.menu-overlay');
        
        if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
        if (navbarLinks) navbarLinks.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
      }
      
      // Handle search toggle visibility based on screen size
      const searchContainer = document.querySelector('.navbar-search');
      const searchToggle = document.querySelector('.mobile-search-toggle');
      
      if (searchContainer && searchToggle) {
        if (window.innerWidth > 576) {
          searchToggle.style.display = 'none';
          searchContainer.classList.remove('mobile-expanded');
          searchContainer.style.display = 'flex';
        } else {
          searchToggle.style.display = 'flex';
          searchContainer.style.display = searchContainer.classList.contains('mobile-expanded') ? 'flex' : 'none';
        }
      }
    }
  });
});
