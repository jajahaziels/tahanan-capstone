<?php
// Use your existing authentication system
require_once '../session_auth.php';
require_once '../connection.php';

// Get tenant ID from session (your system uses tenant_id, not user_id)
$tenant_id = $_SESSION['tenant_id'];

// Fetch tenant information from tenanttbl table
$stmt = $conn->prepare("SELECT * FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();

if (!$tenant) {
    // Tenant not found, redirect to login
    session_destroy();
    header('Location: ../LOGIN/login.php');
    exit;
}

// Get tenant's full name
$tenant_name = trim($tenant['firstName'] . ' ' . ($tenant['middleName'] ? $tenant['middleName'] . ' ' : '') . $tenant['lastName']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FAVICON -->
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <!-- FA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- BS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <title>TENANT MESSAGE - <?php echo htmlspecialchars($tenant_name); ?></title>
    <link rel="stylesheet" href="../css/chat-messages.css">
</head>

<body>
    <!-- HEADER -->
<?php include '../Components/tenant-header.php' ?>
    
    <!-- TENANT MSG CONTENT -->
    <div class="tenant-page">
        <div class="container m-auto">
            <!-- <h2 class="my-4">Chats <i class="fa-solid fa-pen-to-square"></i></h2> -->
            <div class="row chat-box">
                
                <!-- Sidebar -->
                <div class="col-lg-4 side1">
                    <input class="search-chats" type="text" placeholder=" Search Chats">
                    
                    <!-- Loading state -->
                    <div id="conversations-loading" class="loading-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Loading conversations...</p>
                    </div>
                    
                    <!-- No conversations state -->
                    <div id="no-conversations" class="no-conversations-state">
                        <i class="fa-solid fa-comments"></i>
                        <p>No conversations yet</p>
                        <small>Contact your landlord to start chatting</small>
                    </div>
                </div>

                <!-- Chat area -->
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

                   <div class="chat-input">
                        <input type="file" id="file-input" accept="image/*,.pdf,.doc,.docx,.txt,.xlsx,.xls">
                        <button type="button" class="file-upload-btn" onclick="document.getElementById('file-input').click()">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <input type="text" placeholder="Select a conversation first..." disabled>
                        <button type="submit" disabled><i class="fa-solid fa-paper-plane"></i></button>
                    </div>

                    <!-- File Preview (hidden by default) -->
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
                </div>

            </div>
        </div>
    </div>

    <!-- Pass PHP variables to JavaScript -->
    <script>
        // Make PHP variables available to JavaScript
        window.currentUser = {
            id: <?php echo $tenant_id; ?>,
            type: 'tenant',
            name: '<?php echo htmlspecialchars($tenant_name); ?>',
            email: '<?php echo htmlspecialchars($tenant['email']); ?>'
        };
        
        // Debug info
        console.log('Current User:', window.currentUser);
    </script>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>

    <!-- Chat System JS -->
    <script src="../js/tenant-chat.js" defer></script>
</body>
</html>