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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['listingName']); ?> - Details</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
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
            width: 60px;
            height: 60px;
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

        .landlord-card {  
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e6e6e6;
        }

        .landlord-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #a0184c;
            color: #fff;
            font-weight: bold;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .landlord-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .role-badge {
            background: #f1f3f5;
            color: #555;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .btn-large {
            width: 30px !important;
            height: 30px !important;
            font-size: 20px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            border-radius: 8px !important;
            transition: transform 0.2s;
        }
    </style>
</head>

<body>
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
                    <!-- Carousel -->
                    <div id="carouselExample" class="carousel slide mb-4">
                        <div class="carousel-inner">
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $index => $img): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>" class="d-block w-100"
                                            alt="Property Image">
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="carousel-item active">
                                    <img src="../LANDLORD/uploads/placeholder.jpg" class="d-block w-100" alt="No Image">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>

                    <!-- Property Info & Apply -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><?= htmlspecialchars($property['listingName']); ?></h4>

                        <?php if ($requestStatus === 'pending'): ?>

                            <button type="button" class="main-button" disabled>
                                ⏳ Application Pending
                            </button>

                        <?php elseif ($requestStatus === 'approved'): ?>

                            <button type="button" class="main-button" data-bs-toggle="modal" data-bs-target="#reapplyModal">
                                Apply Again
                            </button>

                        <?php else: ?>

                            <button type="button" class="main-button" data-bs-toggle="modal"
                                data-bs-target="#applyConfirmModal">
                                Apply
                            </button>

                        <?php endif; ?>

                    </div>

                    <p><?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</p>
                    <h2 class="price">₱ <?= number_format($property['price']); ?>.00 <small
                            class="text-muted fs-5">/month</small></h2>

                    <!-- Landlord Info Card -->
                    <div class="landlord-card d-flex align-items-center justify-content-between p-3 border rounded mb-4 mt-4">

    <!-- Left: Avatar + Name -->
                <div class="d-flex align-items-center gap-3">
                <div class="landlord-avatar">
                        <?php if (!empty($property['landlord_profilePic'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($property['landlord_profilePic']); ?>">
                        <?php else: ?>
                        <?= strtoupper(substr($property['landlord_fname'], 0, 1)); ?>
                        <?php endif; ?>
                </div>

                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 fw-bold">
                            <?= htmlspecialchars($property['landlord_fname'] . ' ' . $property['landlord_lname']); ?>
                        </h6>
                        <span class="role-badge">Landlord</span>
                    </div>
                </div>
            </div>

                
            <div class="landlord-actions">
                <a href="landlord-profile.php?id=<?= $property['landlord_id']; ?>" class="btn btn-large">
                    <i class="fa-solid fa-user"></i>
                </a>
        
                <a href="tenant-messages.php?landlord_id=<?= $property['landlord_id']; ?>" class="btn btn-large">
                    <i class="fas fa-comment-dots"></i>
                </a>
            </div>
   </div>



                    <!-- Description -->
                    <h3>Property Description</h3>
                    <p><?= nl2br(htmlspecialchars($property['listingDesc'] ?? "No description available.")); ?></p>
                    <ul>
                        <li><strong>Address:</strong> <?= htmlspecialchars($property['address']); ?>,
                            <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna
                        </li>
                        <li><strong>Category:</strong> <?= htmlspecialchars($property['category']); ?></li>
                        <li><strong>Rooms:</strong> <?= htmlspecialchars($property['rooms']); ?> Bedroom(s)</li>
                    </ul>

                    <!-- Map -->
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apply Modal -->
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

    <!-- Re-Apply Modal -->
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
                    <button type="button" class="main-button" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <form action="apply.php" method="POST">
                        <input type="hidden" name="listing_id" value="<?= $property['listing_id']; ?>">
                        <button type="submit" class="main-button">
                            Yes, Apply Again
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <?php include '../Components/footer.php'; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        var lat = <?= $property['latitude'] ?: 14.3647 ?>;
        var lng = <?= $property['longitude'] ?: 121.0556 ?>;
        var map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        L.marker([lat, lng]).addTo(map).bindPopup("<?= htmlspecialchars($property['listingName']); ?>");
    </script>
</body>

</html>