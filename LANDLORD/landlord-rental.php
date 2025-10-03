<?php
require_once '../connection.php';
include '../session_auth.php';

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    die("Invalid request ID.");
}

// Fetch the approved rental based on request ID
$stmt = $conn->prepare("
    SELECT r.*, t.firstName, t.lastName, t.email, t.phoneNum,
           l.listingName, l.address, l.images
    FROM renttbl r
    JOIN tenanttbl t ON r.tenant_id = t.ID
    JOIN listingtbl l ON r.listing_id = l.ID
    WHERE r.ID = ? AND r.status='approved'
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No approved rental found for this request.");
}

$approvedRental = $result->fetch_assoc();


// Get first property image
$images = json_decode($approvedRental['images'], true);
$propertyImg = !empty($images) && isset($images[0])
    ? '../LANDLORD/uploads/' . $images[0]
    : '../LANDLORD/uploads/placeholder.jpg';
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
    <!-- CALENDAR CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <title>LISTING</title>
    <style>
        #map {
            height: 400px;
            max-width: 800px;
            padding: 0 !important;
            margin: auto;
        }

        .property-img {
            width: 500px;
            border-radius: 10px;
        }

        /* .property-imgs{
            border: 5px solid var(--main-color);
        } */

        #calendar {
            max-width: 550px;
            height: 350px;
            margin: 40px auto;
            border: 2px solid var(--main-color);
        }

        .account-img img {
            border: 2px solid var(--main-color);
            width: 100px;
            height: 100px;
            border-radius: 10px;
        }

        .user-profile {
            border: 2px solid var(--main-color);
            padding: 20px;
            border-radius: 10px;
        }

        /* 
        .avatar {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            background: var(--main-color);
            color: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        } */
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
            <li><a href="support.php">Support</a></li>
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

    <section class="home-listing" id="home-listing">
        <div class="container m-auto">
            <h1 class="mb-4">Rental Information</h1>

            <?php if ($approvedRental): ?>
                <div class="row justify-content-center gy-4">
                    <div class="col-lg-5 col-sm-12 text-center">
                        <img src="<?= htmlspecialchars($propertyImg); ?>" alt="Property Image" class="property-img mt-3">
                    </div>
                    <div class="col-lg-5 col-sm-12">
                        <div id="calendar" class="mt-3 border p-3 rounded">
                            📅 Calendar placeholder
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center gy-4 mt-4">
                    <div class="col-lg-5 col-sm-12">
                        <h3><?= htmlspecialchars($approvedRental['listingName']); ?></h3>
                        <p><strong>Address:</strong> <?= htmlspecialchars($approvedRental['address']); ?></p>
                        <p><strong>Rental Start Date:</strong> <?= date("F j, Y", strtotime($approvedRental['date'])); ?></p>
                    </div>
                    <div class="col-lg-5 col-sm-12">
                        <h3>Tenant Information</h3>
                        <p><strong>Name:</strong> <?= htmlspecialchars($approvedRental['firstName'] . " " . $approvedRental['lastName']); ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($approvedRental['phoneNum']); ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($approvedRental['email']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-danger">No approved rental found for this property.</p>
            <?php endif; ?>

        </div>
    </section>

</body>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth'
        });

        calendar.render();
    });
</script>

</html>