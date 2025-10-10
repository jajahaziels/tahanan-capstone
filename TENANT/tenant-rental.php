<?php
require_once '../connection.php';
require_once '../session_auth.php';

$rental = null;
$error = '';

if (!isset($_SESSION['tenant_id'])) {
    $error = "Unauthorized access. Please log in.";
} else {
    $tenant_id = (int) $_SESSION['tenant_id'];

    $sql = "SELECT r.ID AS rental_id, r.date, r.start_date, r.end_date,
                   l.firstName AS landlord_name, l.phoneNum AS landlord_phone, l.email AS landlord_email,
                   t.firstName AS tenant_name, t.phoneNum AS tenant_phone, t.email AS tenant_email,
                   ls.listingName, ls.address, ls.images
            FROM renttbl r
            JOIN listingtbl ls ON r.listing_id = ls.ID
            JOIN landlordtbl l ON ls.landlord_id = l.ID
            JOIN tenanttbl t ON r.tenant_id = t.ID
            WHERE r.tenant_id = ? AND r.status = 'approved'
            LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $rental = $result->fetch_assoc();
        } else {
            $error = "No approved rental info found for your account.";
        }

        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle images
$propertyImg = "../img/house1.jpeg";
if ($rental) {
    $images = json_decode($rental['images'], true);
    if (!empty($images)) {
        $propertyImg = "../LANDLORD/uploads/" . $images[0];
    }
}
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
    <!-- SWEETALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>LISTING</title>
    <style>
                .tenant-page {
            margin-top: 140px;
        }
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
            <li><a href="tenant.php">Home</a></li>
            <li><a href="tenant-rental.php" class="active">My Rental</a></li>
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
                Tenant
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

<div class="tenant-page">
    <div class="container m-auto">
        <h1 class="mb-1">Rental Info</h1>

        <?php if ($rental): ?>
            <!-- ROW 1 -->
            <div class="row justify-content-center gy-5">
                <!-- Property Image -->
                <div class="col-lg-5 col-sm-12 property-imgs">
                    <div class="d-flex justify-content-center align-items-center">
                        <img src="<?php echo htmlspecialchars($propertyImg); ?>"
                             alt="Property Image" class="property-img mt-5">
                    </div>
                </div>

                <!-- Calendar -->
                <div class="col-lg-5 col-sm-12 property-imgs">
                    <div id="calendar" class="mt-5"></div>
                </div>
            </div>

            <!-- ROW 2 -->
            <div class="row justify-content-center gy-5">
                <!-- Property Info -->
                <div class="col-lg-5 col-sm-12">
                    <h2><?php echo htmlspecialchars($rental['listingName']); ?></h2>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($rental['address']); ?></p>
                    <p><strong>Rental Start Date:</strong>
                        <?php echo date("F j, Y", strtotime($rental['start_date'])); ?>
                    </p>
                    <p><strong>Rental Due Date:</strong>
                        <?php echo date("F j, Y", strtotime($rental['end_date'])); ?>
                    </p>
                </div>
                <div>
                    <button id="success">Success</button>
                    <?php
                    if (isset($_GET['success']) && $_GET['success'] == 1) {
                        echo "
                    <script>
                    Swal.fire({
                    title: 'Success!',
                    text: 'Your rental info has been loaded successfully.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                    });
                    </script>
                    ";
                    }
                    ?>
                    
                <script>
                    document.getElementById('success').addEventListener('click', function() {
                        // ✅ Redirect to the same page with ?success=1
                        window.location.href = '?success=1';
                    });
                </script>
                </div>

                <!-- Landlord Info -->
                <div class="col-lg-5 col-sm-12">
                    <h2>Landlord Information</h2>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($rental['landlord_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($rental['landlord_phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($rental['landlord_email']); ?></p>
                </div>
            </div>

        <?php else: ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</div>

</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: 'calendar.php', // fetch Rent Start & Due Date

    });

    calendar.render();
});
</script>
</html>