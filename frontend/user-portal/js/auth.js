    function showError(element, message) {
        element.textContent = message;
        element.style.display = "block";
    }

    function clearErrors() {
        const errorElements =
            document.querySelectorAll(".error-message");
        errorElements.forEach((el) => {
            el.textContent = "";
            el.style.display = "none";
        });
        hideStatusMessage(); // Also hide general status message
    }

    function showStatusMessage(message, type = "info") {
        statusMessage.textContent = message;
        statusMessage.className = `status-message ${type}`; // Ensure base class is always present
        statusMessage.classList.remove("hidden");
    }

    function hideStatusMessage() {
        statusMessage.classList.add("hidden");
        statusMessage.textContent = "";
        statusMessage.className = "status-message hidden"; // Reset classes
