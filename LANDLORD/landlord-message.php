<?php
session_start();
require_once '../connection.php';

// Check if user is logged in (adjust this based on your login system)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header('Location: ../login.php'); // Adjust path to your login page
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch landlord information (adjust field names based on your table)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'landlord'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$landlord = $stmt->get_result()->fetch_assoc();

if (!$landlord) {
    header('Location: ../login.php');
    exit;
}
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
    <title>MESSAGE</title>
    <link rel="stylesheet" href="ll-messages.css">
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

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
                <?php echo htmlspecialchars($landlord['name'] ?? 'Landlord'); ?>
                <div class="dropdown-content">
                    <a href="account.php">Account</a>
                    <a href="settings.php">Settings</a>
                    <a href="logout.php">Log out</a>
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
                    <!-- Conversations will be loaded here by JavaScript -->
                    <div id="no-conversations" style="text-align: center; color: #666; margin-top: 50px;">
                        <i class="fa-solid fa-comments" style="font-size: 48px; opacity: 0.3;"></i>
                        <p>No conversations yet</p>
                    </div>
                </div>

                <!-- Chat area -->
                <div class="col-lg-8 side2">
                    <div class="chat-header">
                        Select a conversation to start chatting
                    </div>

                    <div class="chat-messages">
                        <div class="empty-chat">
                            <div style="text-align: center;">
                                <i class="fa-solid fa-comment-dots" style="font-size: 64px; color: #ccc;"></i>
                                <p style="color: #666; margin-top: 20px;">Choose a conversation from the sidebar</p>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input">
                        <input type="text" placeholder="Type a message..." disabled>
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
            id: <?php echo $user_id; ?>,
            type: '<?php echo $user_type; ?>',
            name: '<?php echo htmlspecialchars($landlord['name'] ?? 'Landlord'); ?>'
        };
    </script>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>

    <script src="../js/chatsys.js" defer></script>

</body>
</html>