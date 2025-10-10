<?php
require_once '../connection.php';
require_once '../session_auth.php';

$rental = null;
$error = '';

// Check landlord session
if (!isset($_SESSION['landlord_id'])) {
    $error = "Unauthorized access. Please log in.";
} else {
    $landlord_id = (int) $_SESSION['landlord_id'];

    // Get request_id from URL
    $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;

    if ($request_id <= 0) {
        $error = "No rental selected.";
    } else {
        // Fetch the selected rental info
        $sql = "
    SELECT 
        r.ID AS rental_id, 
        r.listing_id,
        r.start_date, 
        r.end_date,
        t.firstName AS tenant_name, 
        t.phoneNum AS tenant_phone, 
        t.email AS tenant_email,
        ls.listingName, 
        ls.address, 
        ls.images,
        l.firstName AS landlord_name, 
        l.phoneNum AS landlord_phone, 
        l.email AS landlord_email
    FROM renttbl r
    JOIN listingtbl ls ON r.listing_id = ls.ID
    JOIN tenanttbl t ON r.tenant_id = t.ID
    JOIN landlordtbl l ON ls.landlord_id = l.ID
    WHERE r.ID = ? 
      AND ls.landlord_id = ? 
      AND r.status = 'approved'
    LIMIT 1
";


        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ii', $request_id, $landlord_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $rental = $result->fetch_assoc();
            } else {
                $error = "No approved rental found for this selection.";
            }

            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Default property image
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
    <title>LANDLORD RENTAL</title>
    <style>
        .landlord-page {
            margin-top: 140px;
        }

        #map {
            height: 400px;
            max-width: 800px;
            padding: 0 !important;
            margin: auto;
        }

        #calendar {
            max-width: 500px;
            height: 350px;
            margin: 40px auto;
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

        #carouselExample .carousel-inner{
            height: 400px;
        }
        #carouselExample{
            margin-top: 60px;
        }
        .rental-details{
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }
        
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>


    <div class="landlord-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between">
                <h1 class="mb-1">Rental Info</h1>
                <form method="post" action="cancel-rental.php" onsubmit="return confirm('Are you sure you want to cancel this rental?');">
                    <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                    <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($rental['listing_id']); ?>">
                    <button type="submit" class="main-button">Cancel Rental</button>
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
                            </div>

                            <div class="col-lg-5 col-sm-12">
                                <h2>Tenant Information</h2>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($rental['tenant_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($rental['tenant_phone']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($rental['tenant_email']); ?></p>
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
<script src="../js/script.js"></script>
<!-- BS JS -->
<script src="../js/bootstrap.bundle.min.js?v=<?php echo time(); ?>" defer></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
<!-- LEAFLET JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: [{
                    title: 'Rent Start',
                    start: '<?php echo $rental ? $rental['start_date'] : ''; ?>',
                    color: 'green'
                },
                {
                    title: 'Rent Due',
                    start: '<?php echo $rental ? $rental['end_date'] : ''; ?>',
                    color: 'red'
                }
            ]
        });

        calendar.render();
    });
</script>


</html>