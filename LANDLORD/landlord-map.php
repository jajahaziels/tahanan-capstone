<?php
require_once '../connection.php';
include '../session_auth.php';

// Ensure landlord is logged in
$landlord_id = $_SESSION['landlord_id'] ?? 0;
if ($landlord_id <= 0) {
    die("Unauthorized access. Please log in as landlord.");
}

// Fetch only this landlord’s listings that have coordinates
$sql = "
    SELECT 
        ID AS listing_id, 
        listingName, 
        latitude, 
        longitude 
    FROM listingtbl 
    WHERE landlord_id = ? 
      AND latitude IS NOT NULL 
      AND longitude IS NOT NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

// Store listings for JavaScript
$listings = [];
while ($row = $result->fetch_assoc()) {
    $listings[] = $row;
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
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <title>Landlord <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>!</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        #map {
            height: 500px;
            padding: 0;
            margin: auto;
            border: 2px solid var(--main-color);
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page">
        <div class="container m-auto">
            <h1>Featured Map</h1>
            <p>Manage your properties and connect with tenants easily.</p>
            <div class="row">
                <div class="col-lg-12">
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
<script>
    // Pass PHP array to JS as JSON
    const listings = <?= json_encode($listings); ?>;

    // Initialize the map (default center in case there are no listings)
    const map = L.map('map').setView([14.3647, 121.0556], 13);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Add markers for each listing
    listings.forEach(item => {
        if (item.latitude && item.longitude) {
            // Create popup content with listing name and button
            const popupContent = `
                <div style="text-align:center;">
                    <strong>${item.listingName}</strong><br>
                    <button class="small-button mt-2" 
                        onclick="window.location.href='property-details.php?ID=${item.listing_id}'">
                        View
                    </button>
                </div>
            `;

            // Add marker and popup
            L.marker([item.latitude, item.longitude])
                .addTo(map)
                .bindPopup(popupContent);
        }
    });

    // Auto center map if listings exist
    if (listings.length > 0) {
        const bounds = L.latLngBounds(listings.map(item => [item.latitude, item.longitude]));
        map.fitBounds(bounds);
    }
</script>

</body>

</html>