<?php
require_once '../connection.php';
include '../session_auth.php';

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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>PROPERTIES</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }

        .available {
            background-color: #0697065e;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
            font-size: 12px;
        }

        .occupied {
            background-color: #ff00006b;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
            font-size: 12px;
        }

        .status-occupied {
            background-color: #ff0000c5;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
        }

        .status-available {
            background-color: #008000d0;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
        }

        .status {
            position: absolute;
            margin-top: -170px;
            padding: 0 15px;
            display: flex;
            gap: 8px;
        }

        .status-label {
            background-color: #008000d0;
            color: var(--bg-color);
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 14px;
        }

        .edit {
            background-color: #0000ff59;
            color: var(--text-color);
            width: 45px;
            height: 45px;
            font-size: 16px;
            border-radius: 20px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

        <ul class="nav-links">
            <li><a href="landlord.php">Home</a></li>
            <li><a href="landlord-properties.php" class="active">Properties</a></li>
            <li><a href="landlord-message.php">Messages</a></li>
            <li><a href="../support.html">Support</a></li>
        </ul>
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                Landlord
                <div class="dropdown-content">
                    <a href="account.html">Account</a>
                    <a href="settings.html">Settings</a>
                    <a href="../LOGIN/logout.php">Log out</a>
                </div>
            </div>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>
    <!-- PROPERTY PAGE -->
    <div class="landlord-page">
        <div class="container m-auto d-flex justify-content-between align-items-center">
            <div class="property-title d-flex ">
                <h1>My Properties</h1>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="available mx-3">Available</p>
                    <p class="occupied">Occupied</p>
                </div>
            </div>
            <div class="d-flex">
                <button class="main-button" onclick="location.href='add-property.php'">Add Property</button>
            </div>
        </div>
    </div>
    </div>
    <!-- PROPERTY LIST -->
    <div class="property-list">
        <div class="container m-auto">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <div class="col-lg-4 col-sm-12">
                                    <div class="cards mb-4">
                                        <div class="position-relative">
                                            <?php
                                            $images = json_decode($row['images'], true);
                                            $imagePath = '../LANDLORD/uploads/placeholder.jpg';
                                            if (!empty($images) && is_array($images) && isset($images[0])) {
                                                $imagePath = '../LANDLORD/uploads/' . $images[0];
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imagePath); ?>"
                                                alt="Property Image"
                                                class="property-img"
                                                style="width:100%; max-height:200px; object-fit:cover;">

                                            <div class="status">
                                                <p class="status-label">Available</p>
                                            </div>

                                            <!-- Price -->
                                            <div class="price-tag">₱ <?= number_format($row['price']); ?></div>
                                        </div>

                                        <div class="cards-content">
                                            <!-- Property Name -->
                                            <h5 class="mb-2 house-name"><?= htmlspecialchars($row['listingName']); ?></h5>

                                            <!-- Address -->
                                            <div class="mb-2 location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($row['address']); ?>
                                            </div>

                                            <!-- Features -->
                                            <div class="features">
                                                <div class="m-2">
                                                    <i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']); ?> Bedroom
                                                </div>
                                                <div class="m-2">
                                                    <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']); ?>
                                                </div>
                                            </div>

                                            <div class="divider my-3"></div>

                                            <!-- LANDLORD ACTIONS -->
                                            <div class="d-flex justify-content-center align-items-center">
                                                <button class="main-button" onclick="location.href='edit-property.php?ID=<?= $row['ID'] ?>'">Edit</button>
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
    </div>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
</body>