function contactLandlord(landlordId, propertyId, propertyName, event) {
    if (event) {
        event.preventDefault();
    }

    console.log('🚀 Starting conversation');

    const btn = event ? event.target.closest('button') : null;
    const originalHTML = btn ? btn.innerHTML : '';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }

    const formData = new FormData();
    formData.append('landlord_id', landlordId);
    formData.append('property_id', propertyId);
    formData.append('property_name', propertyName);

    fetch('../api/start_conversation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ API Response:', data);
        
        if (data.success) {
            console.log('🔄 Redirecting to conversation:', data.conversation_id);
            
            // FIXED REDIRECT - Use absolute path
            const redirectUrl = `/TAHANAN/TENANT/tenant-messages.php?conversation_id=${data.conversation_id}`;
            console.log('📍 Redirect URL:', redirectUrl);
            
            window.location.href = redirectUrl;
        } else {
            console.error('❌ API Error:', data.error);
            alert(data.error || 'Failed to start conversation');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }
    })
    .catch(error => {
        console.error('💥 Fetch Error:', error);
        alert('Network error: ' + error.message);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
}