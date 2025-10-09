// contact-landlord.js
function contactLandlord(landlordId, propertyId, propertyName) {
    // Show loading state
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    // Create form data
    const formData = new FormData();
    formData.append('landlord_id', landlordId);
    formData.append('property_id', propertyId);
    formData.append('property_name', propertyName);

    // Call API to create/find conversation
    fetch('../api/start_conversation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to messages page with conversation ID
            window.location.href = `tenant-messages.php?conversation_id=${data.conversation_id}`;
        } else {
            alert(data.error || 'Failed to start conversation');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}