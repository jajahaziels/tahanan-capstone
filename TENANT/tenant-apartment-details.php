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
$lease_id = isset($_GET['lease_id']) ? (int) $_GET['lease_id'] : 0;

if (!$lease_id) {
    die("Invalid lease selected.");
}

// Fetch rental + listing + landlord info
$sql = "
    SELECT 
        r.ID AS rental_id,
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
    FROM leasetbl r
    JOIN listingtbl ls ON r.listing_id = ls.ID
    JOIN landlordtbl l ON ls.landlord_id = l.ID
    WHERE r.ID = ? AND r.tenant_id = ?
    LIMIT 1
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ii', $lease_id, $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $rental = $result->fetch_assoc();
    } else {
        $error = "Rental not found or you are not authorized.";
    }

    $stmt->close();
} else {
    $error = "Database error: " . $conn->error;
}

// Prepare property images
$propertyImg = "../img/house1.jpeg"; // fallback
$images = [];
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
    <title>Rental Details</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
.tenant-page {
    margin-top: 140px;
}

.rental-details {
    background-color: #fff;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
}

.carousel-inner {
    width: 100%;
    height: 100%;
}

#carouselExample img {
    width: 100%;
    height: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 20px;
}

/* Buttons */
.rental-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.rental-buttons button {
    flex: 1; /* equal width */
    min-width: 120px;
    padding: 10px 15px;
    border-radius: 8px;
    border: none;
    background-color: #8D0B41;
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s;
}

.rental-buttons button:hover {
    background-color: #6d0832;
}

/* Landlord Info Buttons */
.landlord-info button {
    width: 100%;
    margin-top: 10px;
}

/* Responsive Layout */
@media(max-width: 991px) {
    #carouselExample img {
        max-height: 300px;
    }
}

.rental-details {
    background-color: #fff;
    padding: 20px; /* nice spacing inside the box */
    border-radius: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.16);
    margin-top: 20px; /* closer to carousel/calendar */
}

</style>

</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="tenant-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between mb-3">
                <h1>Rental Info</h1>
            </div>

            <?php if ($rental): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="row gy-5">

                            <!-- Carousel -->
                            <!-- Carousel -->
<div class="col-lg-6 col-sm-12 d-flex flex-column">
    <div id="carouselExample" class="carousel slide flex-grow-1">
        <div class="carousel-inner h-100">
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $index => $img): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?> h-100">
                        <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                             class="d-block w-100 h-100" alt="Property Image" style="object-fit: cover; border-radius:20px;">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carousel-item active h-100">
                    <img src="../LANDLORD/uploads/placeholder.jpg" class="d-block w-100 h-100"
                         alt="No Image" style="object-fit: cover; border-radius:20px;">
                </div>
            <?php endif; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample"
                data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample"
                data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
    </div>
</div>

<!-- Calendar -->
<div class="col-lg-6 col-sm-12 d-flex flex-column">
    <div id="calendar" class="flex-grow-1" style="width:100%; height:100%; min-height:350px;"></div>
</div>


                        <!-- Rental Details -->
                        <div class="row mt-2 rental-details">
                            <div class="col-lg-6 col-sm-12">
                                <h2><?= htmlspecialchars($rental['listingName']); ?></h2>
                                <p><strong>Address:</strong> <?= htmlspecialchars($rental['address']); ?></p>
                                <p><strong>Start Date:</strong> <?= date("F j, Y", strtotime($rental['start_date'])); ?></p>
                                <p><strong>End Date:</strong> <?= date("F j, Y", strtotime($rental['end_date'])); ?></p>

                                <!-- Extend Rental -->
                                <form action="tenant-extend-request.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">
                                    <input type="hidden" name="listing_id" value="<?= $rental['listing_id'] ?>">
                                    <input type="date" name="new_end_date" required>
                                    <button type="submit" class="small-button">Extend</button>
                                </form>

                                <!-- Cancel Rental -->
                                <form action="tenant-cancel-request.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="rent_id" value="<?= $rental['rental_id'] ?>">
                                    <input type="hidden" name="tenant_id" value="<?= $tenant_id ?>">
                                    <input type="hidden" name="landlord_id" value="<?= $rental['landlord_id'] ?>">
                                    <input type="hidden" name="listing_id" value="<?= $rental['listing_id'] ?>">
                                    <button type="submit" class="small-button"
                                        onclick="return confirm('Request to cancel this rental?')">Cancel</button>
                                </form>
                            </div>

                            <!-- Landlord Info -->
                            <div class="col-lg-6 col-sm-12">
                                <h2>Landlord Information</h2>
                                <p><strong>Name:</strong>
                                    <?= htmlspecialchars(ucwords(strtolower($rental['landlord_firstName'] . ' ' . $rental['landlord_lastName']))); ?>
                                </p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($rental['landlord_phone']); ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($rental['landlord_email']); ?></p>
                                <button class="small-button"
                                    onclick="window.location.href='landlord-profile.php?id=<?= htmlspecialchars($rental['landlord_id']); ?>'">
                                    <i class="fa-solid fa-user"></i> Profile
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <p><?= $error; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: [
                        {
                            title: 'Rental Start',
                            start: '<?= $rental['start_date'] ?? '' ?>',
                            color: 'green'
                        },
                        {
                            title: 'Rental End',
                            start: '<?= $rental['end_date'] ?? '' ?>',
                            color: 'red'
                        }
                    ]
                });
                calendar.render();
            }
        });
    </script>

</body>

</html>