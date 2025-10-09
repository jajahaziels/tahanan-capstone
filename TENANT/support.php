<?php
require_once '../connection.php';
include '../session_auth.php';

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
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title>SUPPORT</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        form {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border: 2px solid var(--main-color) !important;
            background: var(--bg-alt-color) !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .form-control {
            background: var(--bg-alt-color);
        }

        textarea {
            height: 80px;
        }

        .grid-icons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, auto);
            gap: 20px;
            justify-items: center;
            align-items: center;
            font-size: 24px;
        }

        #map {
            height: 300px;
            padding: 0;
            margin: auto;
            border-radius: 20px;
        }
    </style>
</head>

    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>
        <ul class="nav-links">
            <li><a href="tenant.php" class="active">Home</a></li>
            <li><a href="tenant-rental.php">My Rental</a></li>
            <li><a href="tenant-favorite.php">Favorite</a></li>
            <li><a href="tenant-map.php">Map</a></li>
            <li><a href="tenant-messages.php">Messages</a></li>
            <li><a href="support.php">Support</a></li>
        </ul>
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>
                <div class="dropdown-content">
                    <a href="tenant-profile.php">Account</a>
                    <a href="settings.php">Settings</a>
                    <a href="../LOGIN/logout.php">Log out</a>
                </div>
            </div>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>

<div class="landlord-page">
    <div class="container m-auto">
        <h1>Customer Support</h1>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row justify-content-center support-container mt-4">
                    <div class="col-lg-6 p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <h3>Contact Us</h3>
                            <div class="row mb-3">
                                <div class="col">
                                    <input type="text" name="listing_name" class="form-control" placeholder="Name" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <input type="text" name="address" class="form-control" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <input type="text" name="address" class="form-control" placeholder="Subject" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <textarea name="description" class="form-control" rows="5" placeholder="Message"></textarea>
                                </div>
                            </div>

                            <div class="grid">
                                <button type="submit" class="main-button mx-2">Send</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6 p-4">
                        <h1>Lorem ipsum dolor sit amet.</h1>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Dicta quas nihil doloribus ut sint non iure dolorem ipsum culpa numquam!</p>
                        <div class="grid-icons">
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-phone"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6">0912315782</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-envelope"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6">0912315782</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-location-dot"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6">0912315782</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-clock"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6">0912315782</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div id="map" class="mt-2"></div>
        </div>
    </div>
</div>


<!-- MAIN JS -->
<script src="script.js" defer></script>
<!-- BS JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
<!-- LEAFLET JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    // Initialize map centered at Colegio de San Pedro
    var map = L.map('map').setView([14.3476602, 121.0594527], 17); // zoomed in closer

    // Tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Fixed marker at Colegio de San Pedro
    var marker = L.marker([14.3476602, 121.0594527]).addTo(map)
        .bindPopup("<b>Colegio de San Pedro</b>").openPopup();
</script>

</html>