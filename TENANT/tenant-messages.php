<?php
require_once '../session_auth.php';
require_once '../connection.php';

$tenant_id = $_SESSION['tenant_id'];

$stmt = $conn->prepare("SELECT * FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();

if (!$tenant) {
    session_destroy();
    header('Location: ../LOGIN/login.php');
    exit;
}

$tenant_name = trim(ucwords(strtolower(
    $tenant['firstName'] . ' ' . 
    ($tenant['middleName'] ? $tenant['middleName'] . ' ' : '') . 
    $tenant['lastName']
)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/chat-messages.css">
    <title>Messages - <?php echo htmlspecialchars($tenant_name); ?></title>
</head>
<body>
    <?php include '../Components/tenant-header.php'; ?>
    
    <div class="tenant-page">
        <div class="container m-auto">
            <div class="row chat-box">
                
                <div class="col-lg-4 side1">
                    <input class="search-chats" type="text" placeholder="Search conversations...">
                    
                    <div id="conversations-loading" class="loading-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Loading conversations...</p>
                    </div>
                    
                    <div id="no-conversations" class="no-conversations-state" style="display:none;">
                        <i class="fa-solid fa-comments"></i>
                        <p>No conversations yet</p>
                        <small>Contact your landlord to start chatting</small>
                    </div>
                </div>

                <div class="col-lg-8 side2">
                    <div class="chat-header">
                        Select a conversation to start chatting
                    </div>

                    <div class="chat-messages">
                        <div class="empty-chat">
                            <div class="empty-chat-content">
                                <i class="fa-solid fa-comment-dots"></i>
                                <p>Choose a conversation from the sidebar</p>
                                <small>Your messages will appear here</small>
                            </div>
                        </div>
                    </div>

                    <div class="file-preview" id="file-preview">
                        <div class="file-preview-icon">
                            <i class="fa-solid fa-file"></i>
                        </div>
                        <div class="file-preview-info">
                            <p class="file-preview-name"></p>
                            <p class="file-preview-size"></p>
                        </div>
                        <button class="file-preview-remove" onclick="removeFile()">
                            <i class="fa-solid fa-times"></i> Remove
                        </button>
                    </div>

                    <div class="chat-input">
                        <input type="file" id="file-input" accept="image/*,.pdf,.doc,.docx,.txt,.xlsx,.xls">
                        <button type="button" class="file-upload-btn" onclick="document.getElementById('file-input').click()">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <input type="text" placeholder="Select a conversation first..." disabled>
                        <button type="submit" disabled><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        window.currentUser = {
            id: <?php echo json_encode((int)$tenant_id); ?>,
            type: 'tenant',
            name: <?php echo json_encode($tenant_name); ?>,
            email: <?php echo json_encode($tenant['email']); ?>,
            profilePic: <?php echo json_encode($tenant['profilePic'] ?? ''); ?>
        };
    </script>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <!-- NOTIFICATION SYSTEM -->
    <script src="../js/chat-notifications.js?v=<?= time() ?>"></script>
    <script src="../js/tenant-chat.js?v=<?= time() ?>"></script>
    
    <script>
        // Connect notification system to chat
        if (window.chatNotifications && typeof openConversation === 'function') {
            window.chatNotifications.openConversation = function(conversationId) {
                openConversation(conversationId);
            };
        }
    </script>
</body>
</html>