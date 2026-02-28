<?php
require_once '../connection.php';
include '../session_auth.php';

$listingID = intval($_GET['id'] ?? $_GET['ID'] ?? 0);
if ($listingID <= 0)
    die("Invalid property ID.");

/* 📌 Fetch listing */
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
    ld.profilePic AS landlord_profilePic
FROM listingtbl l
JOIN landlordtbl ld ON l.landlord_id = ld.ID
WHERE l.ID = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0)
    die("Property not found.");
$property = $res->fetch_assoc();
$stmt->close();

$images = json_decode($property['images'], true) ?? [];

/* 🔍 Check tenant request status */
$tenant_id = $_SESSION['tenant_id'] ?? 0;
$requestStatus = null;

if ($tenant_id > 0) {
    $checkSql = "
        SELECT status
        FROM requesttbl
        WHERE tenant_id = ? AND listing_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ii", $tenant_id, $listingID);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($row = $r->fetch_assoc()) {
        $requestStatus = $row['status']; // pending | approved | rejected
    }
    $stmt->close();
}

// Check if tenant already has an active rent
$checkSql = "
    SELECT COUNT(*) AS rent_count
    FROM renttbl
    WHERE tenant_id = ?
    AND status = 'active'
";

$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $tenant_id);
$checkStmt->execute();
$result = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($result['rent_count'] > 0) {
    die("You already have an active apartment. You cannot apply for another one.");
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
     
        <div class="modal fade" id="applyConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Confirm Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                Are you sure you want to apply for
                <strong><?= htmlspecialchars($property['listingName']); ?></strong>?
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="main-button" data-bs-dismiss="modal">Cancel</button>

                <form action="apply.php" method="POST">
                    <input type="hidden" name="listing_id" value="<?= $property['listing_id']; ?>">
                    <button type="submit" class="main-button">Yes, Apply</button>
                </form>
            </div>

        </div>
    </div>
</div>

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
                        <?php if ($requestStatus === 'pending'): ?>
                        
                            <button class="main-button mx-5" disabled>
                                ⏳ Application Pending
                            </button>
                        
                        <?php elseif ($requestStatus === 'approved'): ?>
                        
                            <button class="main-button mx-5" data-bs-toggle="modal" data-bs-target="#reapplyModal">
                                Apply Again
                            </button>
                        
                        <?php else: ?>
                        
                            <button class="main-button mx-5" data-bs-toggle="modal" data-bs-target="#applyConfirmModal">
                                Apply
                            </button>
                        
                        <?php endif; ?>


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

                        <div class="modal fade" id="reapplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Apply Again?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                You already had an <strong>approved</strong> request for this property.<br>
                Do you want to apply again?
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="main-button" data-bs-dismiss="modal">Cancel</button>

                <form action="apply.php" method="POST">
                    <input type="hidden" name="listing_id" value="<?= $property['listing_id']; ?>">
                                            <button type="submit" class="main-button">Yes, Apply Again</button>
                                        </form>
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
                                onclick="window.location.href='landlord-profile.php?landlord_id=<?= $property['landlord_id']; ?>'">
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
        <?php include '../Components/footer.php' ?>



    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="../js/contact-landlord.js"></script>
    <script 
        src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap" 
        async 
        defer>
    </script>

   <script>
function initMap() {
    const lat = <?= $property['latitude'] ?: 14.3647 ?>;
        const lng = <?= $property['longitude'] ?: 121.0556 ?>;

        const apartmentLocation = { lat: lat, lng: lng };

        const map = new google.maps.Map(document.getElementById("map"), {
            center: apartmentLocation,
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        /* 🏠 APARTMENT MARKER */
        new google.maps.Marker({
            position: apartmentLocation,
            map: map,
            title: "<?= htmlspecialchars($property['listingName']); ?>",
            icon: "https://maps.google.com/mapfiles/ms/icons/red-dot.png"
        });

        const service = new google.maps.places.PlacesService(map);
        const infoWindow = new google.maps.InfoWindow();

        /* 📏 DISTANCE FUNCTION */
        function getDistanceKM(lat1, lng1, lat2, lng2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;

            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) *
                Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);

            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;

            return distance < 1
                ? `${Math.round(distance * 1000)} meters`
                : `${distance.toFixed(2)} km`;
        }

        /* 🏥 HOSPITALS */
        service.nearbySearch({
            location: apartmentLocation,
            radius: 3000,
            type: "hospital"
        }, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                results.forEach(place => {
                    const distance = getDistanceKM(
                        lat, lng,
                        place.geometry.location.lat(),
                        place.geometry.location.lng()
                    );

                    const marker = new google.maps.Marker({
                        position: place.geometry.location,
                        map: map,
                        title: place.name,
                        icon: "https://maps.google.com/mapfiles/ms/icons/hospitals.png"
                    });

                    marker.addListener("click", () => {
                        infoWindow.setContent(`
                        <strong>🏥 ${place.name}</strong><br>
                        Distance: ${distance}
                    `);
                        infoWindow.open(map, marker);
                    });
                });
            }
        });

        /* 🚨 EVACUATION CENTERS */
        service.nearbySearch({
            location: apartmentLocation,
            radius: 5000,
            keyword: "evacuation center"
        }, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                results.forEach(place => {
                    const distance = getDistanceKM(
                        lat, lng,
                        place.geometry.location.lat(),
                        place.geometry.location.lng()
                    );

                    const marker = new google.maps.Marker({
                        position: place.geometry.location,
                        map: map,
                        title: place.name,
                        icon: "https://maps.google.com/mapfiles/ms/icons/caution.png"
                    });

                    marker.addListener("click", () => {
                        infoWindow.setContent(`
                        <strong>🚨 ${place.name}</strong><br>
                        Distance: ${distance}
                    `);
                        infoWindow.open(map, marker);
                    });
                });
            }
        });
    }
</script>



</body>