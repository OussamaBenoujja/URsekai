    fetch(`http://localhost:8000/api/v1/platform-updates/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => {
        if (res.ok) {
            loadPlatformUpdates(token);
            resetUpdateForm();
        }
    });
}

function resetUpdateForm() {
    document.getElementById('update-id').value = '';
    document.getElementById('update-title').value = '';
    document.getElementById('update-content').value = '';
    document.getElementById('update-image-url').value = '';
    document.getElementById('update-is-active').checked = true;
    document.getElementById('update-cancel-btn').classList.add('hidden');
    document.getElementById('update-form-status').textContent = '';
}
