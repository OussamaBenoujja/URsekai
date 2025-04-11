            const result = await res.json();
            if (res.ok) {
                alert('Group created successfully!');
                formContainer.style.display = 'none';
                formContainer.innerHTML = '';
                loadGroups();
            } else {
                alert(result.message || 'Failed to create group.');
            }
        } catch (err) {
            alert('Error creating group.');
        }
    };
    document.getElementById('cancel-create-group').onclick = function() {
        formContainer.style.display = 'none';
        formContainer.innerHTML = '';
    };
}

// ... rest of the file ...
