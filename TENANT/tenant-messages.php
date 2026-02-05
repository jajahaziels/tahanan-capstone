<?php

// ========================================
// AUTHENTICATION & SESSION
// ========================================
require_once '../session_auth.php';
require_once '../connection.php';

// Get tenant ID from session
$tenant_id = $_SESSION['tenant_id'];

// ========================================
// FETCH TENANT DATA
// ========================================
$stmt = $conn->prepare("SELECT * FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();

// Validate tenant exists
if (!$tenant) {
    session_destroy();
    header('Location: ../LOGIN/login.php');
    exit;
}

// Format tenant name
$tenant_name = trim(ucwords(strtolower(
    $tenant['firstName'] . ' ' . 
    ($tenant['middleName'] ? $tenant['middleName'] . ' ' : '') . 
    $tenant['lastName']
)));

// Get tenant's profile picture
$tenant_profile_pic = isset($tenant['profile_pic']) && !empty($tenant['profile_pic']) 
    ? $tenant['profile_pic'] 
    : 'default-avatar.png';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tenant messaging system for apartment management">
    
    <!-- FAVICON -->
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    
    <!-- MAIN STYLES -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- CHAT STYLES - IMPROVED VERSION (MAROON THEME) -->
    <link rel="stylesheet" href="../css/chat-messages.css">
    
    <title>Messages - <?php echo htmlspecialchars($tenant_name); ?> | Tahanan Apartment</title>
</head>

<body>
    <!-- ========================================
         HEADER
         ======================================== -->
    <?php include '../Components/tenant-header.php'; ?>
    
    <!-- ========================================
         MAIN CHAT INTERFACE
         ======================================== -->
    <div class="tenant-page">
        <div class="container m-auto">
            <div class="row chat-box">
                
                <!-- ====================================
                     SIDEBAR - CONVERSATIONS LIST
                     ==================================== -->
                <div class="col-lg-4 side1">
                    <!-- Search Bar -->
                    <input 
                        class="search-chats" 
                        type="text" 
                        placeholder="Search conversations..."
                        aria-label="Search conversations"
                    >
                    
                    <!-- Loading State -->
                    <div id="conversations-loading" class="loading-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Loading conversations...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="no-conversations" class="no-conversations-state">
                        <i class="fa-solid fa-comments"></i>
                        <p>No conversations yet</p>
                        <small>Contact your landlord to start chatting</small>
                    </div>
                    
                    <!-- Conversations will be loaded here via JavaScript -->
                </div>

                <!-- ====================================
                     CHAT AREA - MESSAGES
                     ==================================== -->
                <div class="col-lg-8 side2">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        Select a conversation to start chatting
                    </div>

                    <!-- Messages Container -->
                    <div class="chat-messages" role="log" aria-live="polite" aria-atomic="false">
                        <!-- Empty Chat State -->
                        <div class="empty-chat">
                            <div class="empty-chat-content">
                                <i class="fa-solid fa-comment-dots"></i>
                                <p>Choose a conversation from the sidebar</p>
                                <small>Your messages will appear here</small>
                            </div>
                        </div>
                    </div>

                    <!-- File Preview (Hidden by default) -->
                    <div class="file-preview" id="file-preview">
                        <div class="file-preview-icon">
                            <i class="fa-solid fa-file"></i>
                        </div>
                        <div class="file-preview-info">
                            <p class="file-preview-name"></p>
                            <p class="file-preview-size"></p>
                        </div>
                        <button 
                            class="file-preview-remove" 
                            onclick="removeFile()"
                            aria-label="Remove file"
                        >
                            <i class="fa-solid fa-times"></i> Remove
                        </button>
                    </div>

                    <!-- Chat Input -->
                    <div class="chat-input">
                        <!-- Hidden File Input -->
                        <input 
                            type="file" 
                            id="file-input" 
                            accept="image/*,.pdf,.doc,.docx,.txt,.xlsx,.xls"
                            aria-label="Upload file"
                        >
                        
                        <!-- File Upload Button -->
                        <button 
                            type="button" 
                            class="file-upload-btn" 
                            onclick="document.getElementById('file-input').click()"
                            aria-label="Attach file"
                            title="Attach file"
                        >
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        
                        <!-- Message Input -->
                        <input 
                            type="text" 
                            placeholder="Select a conversation first..." 
                            disabled
                            aria-label="Type message"
                        >
                        
                        <!-- Send Button -->
                        <button 
                            type="submit" 
                            disabled
                            aria-label="Send message"
                            title="Send message"
                        >
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================
         JAVASCRIPT DATA
         ======================================== -->
    <script>
        /**
         * Pass PHP variables to JavaScript
         * Available globally as window.currentUser
         */
        window.currentUser = {
            id: <?php echo json_encode((int)$tenant_id); ?>,
            type: 'tenant',
            name: <?php echo json_encode($tenant_name); ?>,
            email: <?php echo json_encode($tenant['email']); ?>,
            profilePic: <?php echo json_encode($tenant_profile_pic); ?>
        };
        
        // Debug log (remove in production)
        console.log('🔐 Current User Data:', window.currentUser);
    </script>

    <!-- ========================================
         JAVASCRIPT FILES
         ======================================== -->
    
    <!-- Bootstrap JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="../js/script.js" defer></script>
    
    <!-- Chat System JS - IMPROVED VERSION (MAROON THEME) -->
    <script src="../js/tenant-chat.js" defer></script>
    
    <!-- Scroll Reveal (Optional) -->
    <script src="https://unpkg.com/scrollreveal"></script>
    
    <script>
        // Initialize ScrollReveal for smooth animations (optional)
        if (typeof ScrollReveal !== 'undefined') {
            ScrollReveal().reveal('.chat-box', {
                duration: 800,
                origin: 'bottom',
                distance: '30px',
                easing: 'ease-out'
            });
        }
    </script>
</body>
</html>