        }, true);
        // Hide modal if mouse leaves the modal overlay itself
        modalOverlay.addEventListener('mouseleave', function() {
            modalOverlay.classList.remove('active');
            modalImg.src = '';
        });
    }

    setupScreenshotModalHover();

    // --- Scroll Animation Logic (Remains) ---
    const sections = document.querySelectorAll("main > section, .recommended-developers"); // Target sections directly under main + recommended-devs
    function checkScroll() {
        sections.forEach((section) => {
            const sectionTop = section.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            if (sectionTop < windowHeight * 0.85) { // Adjust threshold slightly if needed
                section.classList.add("visible");
            }
        });
    }
    window.addEventListener("scroll", checkScroll);
    checkScroll(); // Check on initial load

});
