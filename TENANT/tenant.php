<?php
require_once '../connection.php';

$sql = "SELECT * FROM listingtbl";
$result = $conn->query($sql);
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
    <link rel="stylesheet" href="../css/style.css">
    <title>HOME</title>
    <style>
        .tenant-page {
            margin-top: 140px !important;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

        <ul class="nav-links">
            <li><a href="tenant.html" class="active">Home</a></li>
            <li><a href="tenant-rental.html">My Rental</a></li>
            <li><a href="tenant-favorite.html">Favorite</a></li>
            <li><a href="tenant-messages.html">Messages</a></li>
            <li><a href="../support.html">Support</a></li>
        </ul>
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                Tenant
                <div class="dropdown-content">
                    <a href="tenant-profile.html">Account</a>
                    <a href="settings.html">Settings</a>
                    <a href="logout.html">Log out</a>
                </div>
            </div>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>

    <!-- HOME PAGE CONTENT -->
    <div class="tenant-page">
        <div class="container m-auto">
            <h2>Featured Apartment</h2>
            <div class="row">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-sm-12">
                            <div class="cards mb-4">
                                <div class="position-relative">
                                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                        alt="Property Image"
                                        class="property-img">

                                    <div class="labels">
                                        <?php if (!empty($row['is_featured'])): ?>
                                            <div class="label"><i class="fa-regular fa-star"></i> Featured</div>
                                        <?php endif; ?>

                                        <?php if (!empty($row['is_special'])): ?>
                                            <div class="label secondary">Specials</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="price-tag">₱ <?php echo number_format($row['price']); ?></div>
                                </div>

                                <div class="cards-content">
                                    <h5 class="mb-2 house-name">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </h5>
                                    <div class="mb-2 location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </div>

                                    <div class="features">
                                        <div class="m-2"><i class="fas fa-bed"></i> <?php echo $row['bedrooms']; ?> Bedroom</div>
                                        <div class="m-2"><i class="fa-solid fa-building"></i> <?php echo $row['type']; ?></div>
                                    </div>

                                    <div class="divider my-3"></div>

                                    <div class="landlord-info">
                                        <div class="landlord-left">
                                            <img src="uploads/<?php echo htmlspecialchars($row['landlord_img']); ?>"
                                                alt="Landlord">
                                            <div>
                                                <div class="landlord-name"><?php echo htmlspecialchars($row['landlord_name']); ?></div>
                                                <div class="landlord-role">Landlord</div>
                                            </div>
                                        </div>
                                        <div class="landlord-actions">
                                            <div class="btn"><i class="fa-solid fa-user"></i></div>
                                            <div class="btn"><i class="fas fa-comment-dots"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No listings found.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
    </div>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
</body>

</html>