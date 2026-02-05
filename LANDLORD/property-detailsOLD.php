<?php
require_once '../connection.php';
include '../session_auth.php';

// property id from URL
$listingID = intval($_GET['ID'] ?? 0);
if ($listingID <= 0) {
    die("Invalid property ID.");
}

// ✅ Query 1: Get Property + Landlord Info
$sqlProperty = "
    SELECT l.*, ld.firstName AS landlord_fname, ld.lastName AS landlord_lname, ld.profilePic 
    FROM listingtbl l
    JOIN landlordtbl ld ON l.landlord_id = ld.ID
    WHERE l.ID = ?
    LIMIT 1
";
$stmt = $conn->prepare($sqlProperty);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$resultProperty = $stmt->get_result();

if ($resultProperty->num_rows === 0) {
    die("Property not found.");
}
$property = $resultProperty->fetch_assoc();
$images = json_decode($property['images'], true) ?? [];
$stmt->close();

// ✅ Query 2: Get Tenant Applications
$sqlApplications = "
    SELECT r.ID as request_id, r.status, r.date,
           t.firstName, t.lastName, t.phoneNum, t.email
    FROM renttbl r
    JOIN tenanttbl t ON r.tenant_id = t.ID
    WHERE r.listing_id = ? 
      AND r.status != 'rejected'
";
$stmt2 = $conn->prepare($sqlApplications);
$stmt2->bind_param("i", $listingID);
$stmt2->execute();
$applications = $stmt2->get_result();

// PHP
$propertyImg = "../img/house1.jpeg";
if (!empty($rental['images'])) {
    $images = json_decode($rental['images'], true);
    if (!empty($images[0])) {
        $propertyImg = "../uploads/" . $images[0];
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
    <title>PROPERTIES</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }
        .carousel-inner{
            height: 400px !important;
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
    <?php include '../Components/landlord-header.php'; ?>

    <!-- PROPERTY DETAILS -->
    <div class="landlord-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Property Details</h1>
                <button class="main-button" onclick="location.href='landlord-properties.php'">Back</button>
            </div>
            <div class="row justify-content-center">
                <!-- Property Info -->
                <div class="col-lg-6 col-sm-12 mt-0">
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
                    <div class="d-flex justify-content-between">
                        <h1 class="mb-0"><?php echo htmlspecialchars($property['listingName']); ?></h1>
                        <h1 class="mb-0">₱<?php echo htmlspecialchars($property['price']); ?>.00</h1>
                    </div>
                    <p><?= htmlspecialchars($property['barangay'] ?? ''); ?>, San Pedro, Laguna</p>
                    <p><?php echo htmlspecialchars($property['listingDesc']); ?></p>

                    <p class="mb-0">Address: <?= htmlspecialchars($property['address']); ?>, <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</p>
                    <p class="mb-0">Category: <?= htmlspecialchars($property['category']); ?> </p>
                    <p>Rooms: <?= htmlspecialchars($property['rooms']); ?> Bedroom(s) </p>
                </div>

                <!-- Requests -->
                <div class="col-lg-6 col-sm-12">
                    <h1 class="text-center">Requests</h1>
                    <?php if ($applications->num_rows > 0): ?>
                        <?php while ($req = $applications->fetch_assoc()): ?>
                            <div class="p-2 border rounded mb-2">
                                <p><strong><?php echo $req['firstName'] . ' ' . $req['lastName']; ?></strong></p>
                                <p><?php echo $req['email']; ?> | <?php echo $req['phoneNum']; ?></p>
                                <p>Status: <span class="badge bg-secondary"><?php echo ucfirst($req['status']); ?></span></p>

                                <!-- Accept/Reject -->
                                <form method="post" action="update-request.php" class="d-flex gap-2">
                                    <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                    <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
                                </form>


                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No tenant requests yet.</p>
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