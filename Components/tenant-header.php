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
                <li><a href="tenant.php" class="<?= $current_page == 'tenant.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="tenant-rental.php" class="<?= $current_page == 'tenant-rental.php' ? 'active' : '' ?>">My Rental</a></li>
                <li><a href="tenant-map.php" class="<?= $current_page == 'tenant-map.php' ? 'active' : '' ?>">Map</a></li>
                <li><a href="tenant-messages.php" class="<?= $current_page == 'tenant-message.php' ? 'active' : '' ?>">Messages</a></li>
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

            <!-- NOTIFICATION   -->
            <div class="dropdown">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link position-relative"
                            role="button" data-bs-toggle="dropdown">

                            <span class="position-absolute top-0 start-100 translate-middle
                        badge rounded-pill bg-danger count" style="display:none;">0</span>

                            <i class="fa-solid fa-bell fs-4 bell-icon ps-5"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-0">
                            <li class="dropdown-header d-flex justify-content-between px-3 py-2">
                                <span class="fw-semibold p-3">Notifications</span>
                                <button class="btn btn-sm btn-link text-danger ps-4"
                                    id="clearNotifications">
                                    Clear all
                                </button>
                            </li>

                            <li>
                                <hr class="dropdown-divider m-0">
                            </li>

                            <div id="notificationList">
                                <li>
                                    <span class="dropdown-item text-muted text-center py-3">
                                        No notifications
                                    </span>
                                </li>
                            </div>
                        </ul>
                    </li>
                </ul>
            </div>
        </header>
    </body>

    </html>

    <script>
        const notificationList = document.getElementById("notificationList");
        const badge = document.querySelector(".count");
        const clearBtn = document.getElementById("clearNotifications");

        function updateBadge(count) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = "inline-block";
            } else {
                badge.style.display = "none";
            }
        }

        // Example: add notification
        function addNotification(message) {
            if (notificationList.innerText.includes("No notifications")) {
                notificationList.innerHTML = "";
            }

            const li = document.createElement("li");
            li.innerHTML = `
        <span class="dropdown-item notification-item">
            ${message}
        </span>
    `;
            notificationList.prepend(li);

            updateBadge(notificationList.children.length);
        }

        // Clear all notifications
        clearBtn.addEventListener("click", () => {
            notificationList.innerHTML = `
        <li>
            <span class="dropdown-item text-muted text-center py-3">
                No notifications
            </span>
        </li>
    `;
            updateBadge(0);
        });

        // DEMO
        addNotification("Aplication approved for 123 Main St.");
        addNotification("New message from landlord.");
    </script>