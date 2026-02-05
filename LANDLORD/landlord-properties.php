<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'];

// Fetch all landlord properties + check if they have approved rents
$sql = "
SELECT l.*,
       r.ID AS rent_id,
       r.status AS rent_status
FROM listingtbl l
LEFT JOIN renttbl r 
       ON r.listing_id = l.ID AND r.status = 'approved'
WHERE l.landlord_id = ?
GROUP BY l.ID
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>My Properties</title>
    <style>
        .landlord-page { margin-top: 140px; }
        .status-occupied { background-color: #ff0000c5; color: white; padding: 8px; border-radius: 20px; }
        .status-available { background-color: #008000d0; color: white; padding: 8px; border-radius: 20px; }
        .status { position: absolute; margin-top: -170px; padding: 0 15px; display: flex; gap: 8px; }
        .main-button { display: inline-flex; align-items: center; gap: 4px; padding: 20px 3px; white-space: nowrap; line-height: 1; border-radius: 8px; width: auto; min-width: max-content; }
        .main-button i { margin-right: 6px; }
        .price-tag { font-weight: 600; color: #007bff; position: absolute; bottom: 10px; left: 10px; background: rgba(255,255,255,0.8); padding: 5px 10px; border-radius: 5px; }
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page">
        <div class="container m-auto d-flex justify-content-between align-items-center">
            <h1>My Properties</h1>
            <button class="main-button" onclick="location.href='add-property.php'">
                <i class="bi bi-plus-lg"></i> Add Apartment
            </button>
        </div>
    </div>

    <div class="property-list mt-4">
        <div class="container m-auto">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">

                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php $isOccupied = !empty($row['rent_id']); ?>

                                <div class="col-lg-4 col-sm-12">
                                    <div class="cards mb-4">
                                        <div class="position-relative">
                                            <?php
                                            $images = json_decode($row['images'], true);
                                            $imagePath = '../LANDLORD/uploads/placeholder.jpg';
                                            if (!empty($images) && is_array($images) && isset($images[0])) {
                                                $imagePath = '../LANDLORD/uploads/' . $images[0];
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imagePath); ?>" alt="Property Image"
                                                class="property-img" style="width:100%; max-height:200px; object-fit:cover;">

                                            <div class="status">
                                                <p class="<?= $isOccupied ? 'status-occupied' : 'status-available'; ?>">
                                                    <?= $isOccupied ? "Occupied" : "Available"; ?>
                                                </p>
                                            </div>

                                            <div class="price-tag">₱ <?= number_format($row['price']); ?></div>
                                        </div>

                                        <div class="cards-content">
                                            <h5 class="mb-2 house-name"><?= htmlspecialchars($row['listingName']); ?></h5>
                                            <div class="mb-2 location">
                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['address']); ?>
                                            </div>
                                            <div class="features">
                                                <div class="m-2"><i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']); ?> Bedroom</div>
                                                <div class="m-2"><i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']); ?></div>
                                            </div>

                                            <div class="divider my-3"></div>

                                            <div class="d-flex justify-content-center align-items-center">
                                                <button class="small-button mx-2"
                                                    onclick="location.href='edit-property.php?ID=<?= $row['ID'] ?>'">Edit</button>

                                                <?php if ($isOccupied): ?>
                                                    <button class="small-button"
                                                        onclick="location.href='landlord-rental.php?rent_id=<?= $row['rent_id'] ?>'">
                                                        View
                                                    </button>
                                                <?php else: ?>
                                                    <button class="small-button"
                                                        onclick="location.href='property-details.php?ID=<?= $row['ID'] ?>'">
                                                        View
                                                    </button>
                                                <?php endif; ?>
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
    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>
</body>
</html>
