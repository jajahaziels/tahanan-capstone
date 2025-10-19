<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'];

// Fetch available listings with landlord info
$sql = "
    SELECT l.*, lt.firstName, lt.lastName, lt.profilePic
    FROM listingtbl AS l
    JOIN landlordtbl AS lt ON l.landlord_id = lt.ID
    LEFT JOIN renttbl AS r 
        ON l.ID = r.listing_id AND r.status = 'approved'
    WHERE r.ID IS NULL
    ORDER BY l.listingDate DESC
";
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
    <title>Tenant <?= htmlspecialchars(ucwords(strtolower($_SESSION['username'])));?>!</title>
    <style>
        .tenant-page {
            margin-top: 140px !important;
        }

    </style>
</head>

<body>
    <!-- HEADER -->
<?php include '../Components/tenant-header.php' ?>

    <!-- HOME PAGE CONTENT -->
    <div class="tenant-page">
        <div class="container m-auto">
            <h1>Welcome, Tenant <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>!</h1>
            <p>Here are some featured properties</p>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <div class="col-lg-4 col-sm-12">
                                    <div class="cards mb-4" onclick="window.location='property-details.php?ID=<?= $row['ID']; ?>'">
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

                                            <div class="labels">
                                                <div class="label"><i class="fa-regular fa-star"></i> Featured</div>
                                                <div class="label">Specials</div>
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

                                            <!-- Landlord Info -->
                                            <div class="landlord-info">
                                                <div class="landlord-left d-flex align-items-center">
                                                    <?php if (!empty($row['profilePic'])): ?>
                                                        <img src="../LANDLORD/uploads/<?= htmlspecialchars($row['profilePic']); ?>"
                                                            alt="Landlord"
                                                            style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="avatar">
                                                            <?= ucwords(substr($row['firstName'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="ms-2">
                                                        <div class="landlord-name">
                                                            <?= ucwords(htmlspecialchars($row['firstName'] . ' ' . $row['lastName'])); ?>
                                                        </div>
                                                        <div class="landlord-role">Landlord</div>
                                                    </div>
                                                </div>

                                                <div class="landlord-actions">
                                                    <div class="btn"><i class="fa-solid fa-user"></i></div>
                                                    <div class="btn"><i class="fas fa-comment-dots"></i></div>
                                                </div>
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
    <?php include '../Components/footer.php'; ?>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
</body>

</html>