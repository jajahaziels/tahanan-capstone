// ============================================
// GLOBAL NOTIFICATION INITIALIZER
// Loads user session and starts notifications on ANY page
// ============================================

(async function initGlobalNotifications() {
    try {
        console.log('🌐 Initializing global notifications...');
        
        // Fetch current user session
        const response = await fetch('/TAHANAN/API/session_check.php');
        
        if (!response.ok) {
            console.log('⚠️ Session check failed:', response.status);
            return;
        }
        
        const data = await response.json();
        
        if (data.success && data.user_id) {
            // Set global user object for notification system
            window.currentUser = {
                id: data.user_id,
                type: data.user_type,
                name: data.name || (data.user_type === 'tenant' ? 'Tenant' : 'Landlord'),
                profilePic: data.profile_pic || ''
            };
            
            console.log('✅ User session loaded:', window.currentUser.name);
            console.log('🔔 Notification system will start automatically');
        } else {
            console.log('⚠️ No active session - notifications disabled');
        }
    } catch (error) {
        console.error('❌ Failed to initialize notifications:', error);
    }
})();