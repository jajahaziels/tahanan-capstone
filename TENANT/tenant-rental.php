<?php
require_once '../connection.php';
require_once '../session_auth.php';
include 'auto-expire-rental.php'; // Automatically expire old rentals

$rental = null;
$error = '';

// Ensure tenant is logged in
if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access. Please log in.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

// Fetch the tenant's current approved rental (only one at a time)
$sql = "
    SELECT 
        r.ID AS rental_id,
        r.date,
        r.start_date,
        r.end_date,
        r.listing_id,
        l.ID AS landlord_id,
        l.firstName AS landlord_firstName,
        l.lastName AS landlord_lastName,
        l.phoneNum AS landlord_phone,
        l.email AS landlord_email,
        ls.listingName,
        ls.address,
        ls.images
    FROM renttbl r
    JOIN listingtbl ls ON r.listing_id = ls.ID
    JOIN landlordtbl l ON ls.landlord_id = l.ID
    WHERE r.tenant_id = ? 
      AND r.status = 'approved'
    LIMIT 1
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $rental = $result->fetch_assoc();
    } else {
        $error = "No active rental found for your account.";
    }

    $stmt->close();
} else {
    $error = "Database error: " . $conn->error;
}

// Prepare property image
$propertyImg = "../img/house1.jpeg"; // fallback
if ($rental) {
    $images = json_decode($rental['images'], true) ?? [];
    if (!empty($images[0])) {
        $propertyImg = "../LANDLORD/uploads/" . htmlspecialchars($images[0]);
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
    <title>Rental Information</title>
    <style>
        .tenant-page {
            margin-top: 140px;
        }

        #calendar {
            max-width: 500px;
            height: 350px;
            margin: 40px auto;
        }

        .rental-details {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .carousel-inner {
            height: 300px !important;
        }

        #carouselExample {
            max-width: 500px !important;
            margin-top: 80px !important;
        }

        #carouselExample img {
            height: 400px !important;
            object-fit: cover !important;
            border-radius: 20px !important;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/tenant-header.php' ?>

    <div class="tenant-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between">
            <h1 class="mb-1">Rental Info</h1>
            <form method="post" action="cancel-rental.php">
                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($rental['listing_id']); ?>">
                <button type="submit" class="main-button">Payment</button>
                <button type="submit" class="main-button">View History</button>
            </form>
            </div>

            <?php if ($rental): ?>
                <!-- ROW 1: Image + Calendar -->
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="row justify-content-center gy-5">
                            <div class="col-lg-6 col-sm-12">
                                <!-- Bootstrap Carousel -->
                                <div id="carouselExample" class="carousel slide">
                                    <div class="carousel-inner">
                                        <?php if (!empty($images)): ?>
                                            <?php foreach ($images as $index => $img): ?>
                                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                    <div class="row justify-content-center">
                                                        <div class="col-lg-12">
                                                            <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                                                                class="d-block w-100"
                                                                style="max-height:300px; object-fit:cover;"
                                                                alt="Property Image">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="carousel-item active">
                                                <div class="row justify-content-center">
                                                    <div class="col-lg-12">
                                                        <img src="../LANDLORD/uploads/placeholder.jpg"
                                                            class="d-block w-100"
                                                            style="max-height:300px; object-fit:cover;"
                                                            alt="No Image">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Carousel Controls -->
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-6 col-sm-12">
                                <div id="calendar" class="mt-5"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Property + Tenant Info -->
                <div class="row justify-content-center">
                    <div class="col-lg-10 rental-details">
                        <div class="row justify-content-center gy-5">
                            <div class="col-lg-6 col-sm-12">
                                <h2><?php echo htmlspecialchars($rental['listingName']); ?></h2>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($rental['address']); ?></p>
                                <p><strong>Rental Start Date:</strong>
                                    <?php echo date("F j, Y", strtotime($rental['start_date'])); ?>
                                </p>
                                <p><strong>Rental Due Date:</strong>
                                    <?php echo date("F j, Y", strtotime($rental['end_date'])); ?>
                                </p>
                                <form action="tenant-extend-request.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">
                                    <input type="hidden" name="listing_id" value="<?= $rental['listing_id'] ?>">
                                    <input type="date" name="new_end_date" required>
                                    <button type="submit" class="small-button">Extend</button>
                                </form>
                                <form action="tenant-cancel-request.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="rent_id" value="<?= $rental['rental_id'] ?>">
                                    <input type="hidden" name="tenant_id" value="<?= $_SESSION['tenant_id'] ?>">
                                    <input type="hidden" name="landlord_id" value="<?= $rental['landlord_id'] ?>">
                                    <input type="hidden" name="listing_id" value="<?= $rental['listing_id'] ?>">
                                    <button type="submit" class="small-button"
                                        onclick="return confirm('Request to cancel this rental?')">Cancel</button>
                                </form>





                            </div>

                            <div class="col-lg-5 col-sm-12">
                                <h2>Landlord Information</h2>
                                <p><strong>Name: </strong><?php echo htmlspecialchars(ucwords(strtolower($rental['landlord_firstName'] . ' ' . $rental['landlord_lastName']))); ?>
                                </p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($rental['landlord_phone']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($rental['landlord_email']); ?></p>
                                <button class="small-button"
                                    onclick="window.location.href='landlord-profile.php?id=<?= htmlspecialchars($rental['landlord_id']); ?>'">
                                    <i class="fa-solid fa-user"></i>
                                </button>
                                <button class="small-button"
                                    onclick="window.location.href='tenant-messages.php?landlord_id=<?= htmlspecialchars($rental['landlord_id']); ?>'">
                                    <i class="fas fa-comment-dots"></i>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p><?php echo $error; ?></p>
            <?php endif; ?>
        </div>
    </div>

</body>
<!-- MAIN JS -->
<script src="../js/script.js" defer></script>
<!-- BS JS -->
<script src="../js/bootstrap.bundle.min.js"></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
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