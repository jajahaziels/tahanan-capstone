<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'];

$sql = "
    SELECT ID AS listing_id, listingName, latitude, longitude 
    FROM listingtbl 
    WHERE latitude IS NOT NULL 
      AND longitude IS NOT NULL
      AND ID NOT IN (
          SELECT listing_id FROM renttbl WHERE status = 'approved'
      )
";

$result = $conn->query($sql);

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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>MAP</title>
    <style>
        .tenant-page {
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
<?php include '../Components/tenant-header.php' ?>

    <!-- HOME PAGE CONTENT -->
    <div class="tenant-page">
        <div class="container m-auto">
            <h1>Featured Map</h1>
            <p>Here are some featured properties on the map:</p>
            <div class="row">
                <div class="col-lg-12">
                    <div id="map"></div>
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
    <!-- LEAFLET JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        // Pass PHP array to JS as JSON
        var listings = <?= json_encode($listings); ?>;

        // Default center (if no data)
        var map = L.map('map').setView([14.3647, 121.0556], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        listings.forEach(function(item) {
            if (item.latitude && item.longitude) {
                // create popup content with a button
                var popupContent = `
            <button class="small-button" onclick="window.location.href='property-details.php?id=${item.listing_id}'">
                View
            </button>
        `;

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