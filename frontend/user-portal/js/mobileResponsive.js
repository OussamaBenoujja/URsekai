    // Handle screenshot modal for mobile
    const screenshotContainer = document.querySelector('.hero-screenshots');
    if (screenshotContainer) {
        // Improve touch interaction with screenshots
        const screenshots = screenshotContainer.querySelectorAll('img');
        screenshots.forEach(image => {
            image.addEventListener('click', function(e) {
                // Prevent default if modal exists
                if (document.querySelector('.screenshot-modal-overlay')) {
                    e.preventDefault();
                }
            });
        });
    }

    // Add touch feedback for buttons
    const allButtons = document.querySelectorAll('button');
    allButtons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.97)';
        }, { passive: true });
        
        button.addEventListener('touchend', function() {
            this.style.transform = '';
        }, { passive: true });
    });
});
