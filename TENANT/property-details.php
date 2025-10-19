<?php
require_once '../connection.php';
include '../session_auth.php';

// Get property ID from URL (case-insensitive)
$idParam = $_GET['id'] ?? $_GET['ID'] ?? null;

if (!$idParam || !is_numeric($idParam)) {
    die("Invalid property ID.");
}

$listingID = intval($idParam);

// Fetch property and landlord info (for tenant view)
$sql = "
    SELECT 
        l.ID AS listing_id,
        l.listingName,
        l.address,
        l.barangay,
        l.category,
        l.rooms,
        l.price,
        l.listingDesc,
        l.images,
        l.latitude,
        l.longitude,
        ld.ID AS landlord_id,
        ld.firstName AS landlord_fname,
        ld.lastName AS landlord_lname,
        ld.profilePic,
        ld.phoneNum AS landlord_phone,
        ld.email AS landlord_email
    FROM listingtbl l
    JOIN landlordtbl ld ON l.landlord_id = ld.ID
    WHERE l.ID = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Property not found.");
}

$property = $result->fetch_assoc();
$images = json_decode($property['images'], true) ?? [];
$stmt->close();
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
    <title><?= htmlspecialchars($property['listingName']); ?> - Details</title>
    <style>
        .tenant-page {
            margin-top: 50px !important;
        }

        .prorperty-details {
            background-color: var(--bg-color);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .back-button {
            position: fixed;
            margin-top: 160px;
        }

        .price {
            font-size: 3rem;
        }
        .avatar {
            width: 60px !important;
            height: 60px !important;
            border-radius: 50%;
            background: var(--main-color);
            color: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .avatar img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }


        #map {
            height: 400px;
            padding: 0;
            margin: auto;
        }

        .carousel-inner {
            height: 420px !important;
        }

        #carouselExample {
            max-width: 500px !important;
            margin: 0 auto !important;
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
            <div class="d-flex justify-content-start align-items-center">
                <button class="main-button back-button" onclick="location.href='tenant.php'">Back</button>
            </div>
        </div>

        <div class="row justify-content-center align-items-center mt-5">
            <div class="col-lg-6 p-3">
                <div class="prorperty-details">
                    <!-- Bootstrap Carousel -->
                    <div id="carouselExample" class="carousel slide mb-4">
                        <div class="carousel-inner">
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $index => $img): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <div class="row justify-content-center">
                                            <div class="col-lg-12">
                                                <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                                                    class="d-block w-100"
                                                    style="max-height:400px; object-fit:cover;"
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
                                                style="max-height:400px; object-fit:cover;"
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

                    <!-- Property Info -->
                    <p class="mb-0"><?= htmlspecialchars($property['barangay'] ?? ''); ?>, San Pedro, Laguna</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 mt-0"><?= htmlspecialchars($property['listingName']); ?></h4>
                        <!-- Apply Button (triggers modal) -->
                        <button type="button" class="main-button mx-5" data-bs-toggle="modal" data-bs-target="#applyModal">
                            Apply
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">

                                    <!-- Modal Header -->
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="applyModalLabel">Apply for Rental</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <!-- Modal Body (Form) -->
                                    <div class="modal-body">
                                        <form id="applyForm" action="apply.php" method="POST">
                                            <input type="hidden" name="listing_id" value="<?= htmlspecialchars($property['listing_id']); ?>">

                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">Rental Start Date</label>
                                                <input type="date" class="form-control" name="start_date" id="start_date" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="end_date" class="form-label">Rental End Date</label>
                                                <input type="date" class="form-control" name="end_date" id="end_date" required>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Modal Footer -->
                                    <div class="modal-footer">
                                        <button type="button" class="main-button" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" form="applyForm" class="main-button">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <h2 class="price">
                        ₱ <?= number_format($property['price']); ?>.00
                        <small class="text-muted fs-5">/month</small>
                    </h2>

                    <!-- Landlord Info -->
                    <div class="d-flex align-items-center p-2 border rounded mb-4 mt-4">
                        <!-- Avatar -->
                        <div class="avatar me-3">
                            <?php if (!empty($property['profilePic'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($property['profilePic']); ?>" alt="Profile">
                            <?php else: ?>
                                <div class="landlord-info">
                                    <?= strtoupper(substr($property['landlord_fname'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Landlord Info -->
                        <div class="info flex-grow-1 mt-2">
                            <h1 class="mb-0">
                                <?= htmlspecialchars(ucwords(strtolower($property['landlord_fname'] . ' ' . $property['landlord_lname']))); ?>
                            </h1>
                            <p class="text-muted">Landlord</p>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex">
                            <button class="small-button"
                                onclick="window.location.href='landlord-profile.php?id=<?= $property['landlord_id']; ?>'">
                                <i class="fa-solid fa-user"></i>
                            </button>
                            <button class="small-button mx-3"
                                onclick="contactLandlord(<?= $property['landlord_id']; ?>, <?= $property['listing_id']; ?>, '<?= htmlspecialchars(addslashes($property['listingName'])); ?>')">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Property Description -->
                    <h3>Property Description</h3>
                    <p><?= nl2br(htmlspecialchars($property['listingDesc'] ?? "No description available.")); ?></p>
                    <ul>
                        <li><strong>Address:</strong> <?= htmlspecialchars($property['address']); ?>, <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</li>
                        <li><strong>Category:</strong> <?= htmlspecialchars($property['category']); ?></li>
                        <li><strong>Rooms:</strong> <?= htmlspecialchars($property['rooms']); ?> Bedroom(s)</li>
                    </ul>

                    <!-- Map -->
                    <div id="map"></div>
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
    <!-- LEAFLET JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="../js/contact-landlord.js"></script>
    <script>
        var lat = <?= $property['latitude'] ?: 14.3647 ?>;
        var lng = <?= $property['longitude'] ?: 121.0556 ?>;

        var map = L.map('map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map).bindPopup("<?= htmlspecialchars($property['listingName']); ?>");
    </script>

</body>