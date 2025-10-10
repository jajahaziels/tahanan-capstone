    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- FAVICON -->
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
        <!-- FA -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
            integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- BS -->
        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <!-- MAIN CSS -->
        <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    </head>

    <body>
        <!-- HEADER -->
        <header>
            <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

            <ul class="nav-links">
                <li><a href="landlord-properties.php" class="<?= $current_page == 'landlord-properties.php' ? 'active' : '' ?>">Properties</a></li>
                <li><a href="landlord-map.php" class="<?= $current_page == 'landlord-map.php' ? 'active' : '' ?>">Map</a></li>
                <li><a href="landlord-message.php" class="<?= $current_page == 'landlord-message.php' ? 'active' : '' ?>">Messages</a></li>
                <li><a href="support.php" class="<?= $current_page == 'support.php' ? 'active' : '' ?>">Support</a></li>
            </ul>
            <!-- NAV ICON / NAME -->
            <div class="nav-icons">
                <!-- DROP DOWN -->
                <div class="dropdown">
                    <i class="fa-solid fa-user"></i>
                    <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>
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
    </body>

    </html>