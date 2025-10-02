<?php
// Use your existing authentication system
require_once '../session_auth.php';
require_once '../connection.php';

// Get landlord ID from session (your system uses landlord_id, not user_id)
$landlord_id = $_SESSION['landlord_id'];

// Fetch landlord information from landlordtbl table
$stmt = $conn->prepare("SELECT * FROM landlordtbl WHERE ID = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$landlord = $stmt->get_result()->fetch_assoc();

if (!$landlord) {
    // Landlord not found, redirect to login
    session_destroy();
    header('Location: ../LOGIN/login.php');
    exit;
}

// Get landlord's full name
$landlord_name = trim($landlord['firstName'] . ' ' . ($landlord['middleName'] ? $landlord['middleName'] . ' ' : '') . $landlord['lastName']);
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
    <title>MESSAGE - <?php echo htmlspecialchars($landlord_name); ?></title>
    <link rel="stylesheet" href="../css/chat-messages.css">
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center">
            <img src="../img/logo.png" alt="">Tahanan
        </a>

        <ul class="nav-links">
            <li><a href="landlord.php">Home</a></li>
            <li><a href="landlord-properties.php">Properties</a></li>
            <li><a href="landlord-message.php" class="active">Messages</a></li>
            <li><a href="../support.php">Support</a></li>
        </ul>
        
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                <?php echo htmlspecialchars($landlord_name); ?>
                <div class="dropdown-content">
                    <a href="account.php">Account</a>
                    <a href="settings.php">Settings</a>
                    <a href="../LOGIN/logout.php">Log out</a>
                </div>
            </div>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>
    
    <!-- MESSAGES -->
    <div class="landlord-page">
        <div class="container-fluid h-100">
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
                        <small>Start a conversation with a tenant</small>
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
                        <input type="text" placeholder="Select a conversation first..." disabled>
                        <button disabled><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Pass PHP variables to JavaScript -->
    <script>
        // Make PHP variables available to JavaScript
        window.currentUser = {
            id: <?php echo $landlord_id; ?>,
            type: 'landlord',
            name: '<?php echo htmlspecialchars($landlord_name); ?>',
            email: '<?php echo htmlspecialchars($landlord['email']); ?>'
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

    <!-- Updated Chat System JS -->
    <script src="../js/chatsys.js" defer></script>

</body>
</html>