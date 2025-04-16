            if (editBtn) editBtn.style.display = 'none';
        }
    }
    hideEditProfileIfNotOwn();

    // Initial load
    loadUserProfile();
    // loadUserDashboard();
    loadRecentActivity();
    loadUserAchievements();
    loadUserBadges();
    loadUserFriends();
});
